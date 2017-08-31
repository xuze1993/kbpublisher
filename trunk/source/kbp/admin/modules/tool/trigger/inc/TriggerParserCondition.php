<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class TriggerParserCondition extends TriggerParser
{
    
    var $id_readroot = 'condition_readroot';
    var $id_writeroot = 'condition_writeroot';
    var $id_pref = 'more_condition_';
    var $id_pref_populate = 'more_condition_populate_';
    var $counter = 1;
    var $condition_name = 'cond';
    
    var $etype;
    var $items = array();
    var $msg = array();
    
    
    static function factory($type) {
        $class = 'TriggerParserCondition_' . $type;
        $file  = 'TriggerParserCondition_' . $type . '.php';
        
        //require_once $file;
        return new $class;
    }
    
    
    function &getRuleOption($item) {

        $file = sprintf('trigger_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;
        
        $rule = array('empty' => '');
        if(isset($items[$item])) {
            $item_rule = $items[$item];
            $rule = $rules[$item_rule['r']];            
        }
        
        // mesage if any change from item_to_rule
        if(isset($rule['msg']) && isset($item_rule['msg'])) {
            $rule['msg']['value'] = $item_rule['msg'];
        }
        
        return $rule;
    }
    
    
    function &getRuleMsg() {
        return $this->msg['trigger_rule'];
    }    
    
    
    function getItemSelect($selected = false) {   
        $file = sprintf('trigger_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;
        return $this->_getItemSelect(array_keys($items), $this->msg['trigger_item'], $selected);
    }
    
    
    function &getDateTimeSelect($counter, $timestamp) {
        
        require_once 'eleontev/HTML/DatePicker.php';
        
        // dates
        $picker = new DatePicker();
        $picker->setFormName('trigger_form');        // set form name
        $picker->setFormMethod($_POST);                // set form method
        $picker->setSelectName($this->condition_name.'['.($counter).'][rule][1]');
        
        $picker->setYearRange(2008-2, date('Y')+3);
        $picker->setDate($timestamp);
        
        $date = $picker->day();       // select tag with days
        $date .= $picker->month();    // select tag with mohth
        $date .= $picker->year();     // select tag with years
        
        //setTimeRange($start = 8, $hours = 24, $gap_min = 30);
        $picker->time_format = $this->time_format; //$time_format;
        $picker->setTimeRange(8, 24, 60);
        
        $date .= '&nbsp;&nbsp;&nbsp;';
        $date .= $picker->time();        
        
        return $date;    
    }
    
    
    function getSql($cond_match, $condition, $categories, $manager) {
        
        $table = array(
            '[is]'     => '=', 
            '[is_not]' => '!=', 
            '[less]'   => '<', 
            '[more]'   => '>',
            '[equal]'  => '=', 
            '[contain]'      => "LIKE '%%s%'", 
            '[not_contain]'  => "NOT LIKE '%%s%'", 
            '[start_with]'   => "LIKE '%s%'", 
            '[end_with]'     => "%%s",
            '[period_old_days]'  => 'DAY',
            '[period_old_hours]' => 'HOUR',
            '[in]'     => 'IN',
            '[not_in]' => 'NOT IN'
        );
        
        
        $file = sprintf('trigger_map_%s.php', 'article_automation');
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;
        
        $arr = array();
        foreach($condition as $k => $v) {
            
            $args = $v['rule'];
            $_sql = $items[$v['item']]['sql'];
            
            // order for params
            if(isset($items[$v['item']]['order'])) {
                $args = array();
                foreach(explode(',', $items[$v['item']]['order']) AS $v2) {
                    $args[] = $v['rule'][$v2];
                }
            }
            
            // special case for categories
            if ($v['item'] == 'category') {
                $args[0] = ($args[0] == 'is') ? 'in' : 'not_in';
                
                $category_id = $v['rule'][1];
                $cats = array($category_id);
                
                if (!empty($v['rule'][2])) {
                    $tree = new TreeHelper;
                    foreach($categories as $k => $row) {
                        $tree->setTreeItem($row['id'], $row['parent_id']);
                    }
                    
                    $children = $tree->getChildsById($category_id);
                    if (!empty($children)) {
                        $cats = array_merge($cats, $children);
                    }
                }
                
                if ($cond_match == 2) {
                    $subquery = ($args[0] == 'in') ? 'EXISTS' : 'NOT EXISTS';
                    $args[0] = 'in';
                    
                    $_sql = '%s (SELECT * FROM %s c WHERE c.entry_id = e.id AND c.category_id [%%s] %%s)';
                    $_sql = sprintf($_sql, $subquery, $manager->tbl->entry_to_category);
                }
                
                $args[1] = sprintf('(%s)', implode(',', $cats));
            }
            
            $arr[] = vsprintf($_sql, $args);
        }
        
        $match = ($cond_match == 1) ? ' OR ' : ' AND ';
        $sql = ($cond_match == 1) ? sprintf('(%s)', implode($match, $arr)) : implode($match, $arr);
        $sql = strtr($sql, $table);
        
        return $sql;
    }
}


class TriggerParserCondition_article_trigger extends TriggerParserCondition
{
    
    var $default_item = 'article';
    var $default_rule = array('created');
    var $etype = 'article';
    
    
    function getCategorySelectRange() {
        return $this->model->getCategorySelectRange('article');
    }
    
    
    function getStatusSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        return $m->getListSelectRange('article_status', false);
    }
    
    
    function getTypeSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        
        $range = $m->getListSelectRange('article_type', false);
        $extra_range = array(0 => '__');
        
        return $extra_range + $range;
    }
    
    
    function getAuthorSelectRange($value) {
        $placeholders = array('updater' => '[updater]');
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
    
    
    function getUpdaterSelectRange($value) {
        return $this->model->getUserSelectRange($value, array(), false);
    }
}


class TriggerParserCondition_article_automation extends TriggerParserCondition
{
    
    var $default_item = 'status';
    var $default_rule = array('is', 1);
    var $etype = 'article_automation';
    
    
    function getStatusSelectRange($value = false) {
        $m = &TriggerModel::instance('KBEntryModel');
        $range = $m->getListSelectRange('article_status', false);
        
        if ($value && !isset($range[$value])) {
            $range = array('none' => '---') + $range;
        }
        
        return $range;
    }
    
    
    function getTypeSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        
        $range = $m->getListSelectRange('article_type', false);
        $extra_range = array(0 => '__');
        
        return $extra_range + $range;
    }
    
    
    function getAuthorSelectRange($value) {
        $placeholders = array();
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }


    function validateStatus($value) {
        $range = $this->getStatusSelectRange();
        return (isset($range[$value]));
    }
    
    
    function validateType($value) {
        $range = $this->getTypeSelectRange();
        return (isset($range[$value]));
    }
    
    
    function validateCategory($value) {
        $category = $this->model->getCategoryById('article', $value);
        return (boolean) $category;
    }
    
}


class TriggerParserCondition_file_automation extends TriggerParserCondition
{
    
    var $default_item = 'status';
    var $default_rule = array('is', 1);
    var $etype = 'file_automation';
    
    
    function getStatusSelectRange() {
        $m = &TriggerModel::instance('KBEntryModel');
        return $m->getListSelectRange('file_status', false);
    }
    
    
    function getAuthorSelectRange($value) {
        $placeholders = array();
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
    
    
    function validateCategory($value) {
        $category = $this->model->getCategoryById('file', $value);
        return (boolean) $category;
    }
    
}


class TriggerParserCondition_setting extends TriggerParserCondition
{
    
    var $default_item = 'Facebook';
    var $default_rule = array('../client/images/icons/socialmediaicons/24x24/facebook.png');
    var $etype = 'setting';
    var $view;
    
    
    function getItemSelect($selected = false) {
        $items = array_keys($this->view->sites);
        $items[] = 'custom';
        return $this->_getItemSelect($items, $this->msg['trigger_item'], $selected);
    }
    
    
    function &getRuleOption($item) { // 2 rules only
        if ($item == 'custom') {
            $rule = array(
                array(
                    'image' => array()
                ),
                array(
                    'text' => array('placeholder' => 'title')
                ),
                array(
                    'text' => array('placeholder' => 'url', 'tooltip' => 'share_url_tip_msg')
                ),
                array(
                    'text' => array('placeholder' => 'icon', 'tooltip' => 'share_icon_tip_msg')
                ),
            );
            
        } else {
            $image = '';
            if (in_array($item, array_keys($this->view->sites))) {
                $image = str_replace('[size]', '24x24', $this->view->sites[$item]['icon']);
                $image = str_replace('{client_href}', '../client/', $image);
            }
            
            $rule = array(
                array(
                    'image' => array('value' => $image)
                ),
            );
        }
        
        return $rule;
    }
    
}


class TriggerParserCondition_email_automation extends TriggerParserCondition
{
    
    var $default_item = 'email';
    var $default_rule = array();
    var $etype = 'email_automation';
    
}
?>