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


class WorkflowParserCondition extends WorkflowParser
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
        $class = 'WorkflowParserCondition_' . $type;
        $file  = 'WorkflowParserCondition_' . $type . '.php';
        
        //require_once $file;
        return new $class;
    }
    
    
    function &getRuleOption($item) {

        $file = sprintf('workflow_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/workflow/inc/' . $file;
        
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
    
    
    function getItemSelect($selected = false, $selected_only = false) {   
        $file = sprintf('workflow_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/workflow/inc/' . $file;
        
        $_items = $items;
        if ($selected_only) {
            //$_items = array($selected => $items[$selected]);
        }
        
        return $this->_getItemSelect(array_keys($_items), $this->msg['trigger_item'], $selected);
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
        
        //$date = $picker->js();                    // js function
        //$tpl->tplAssign('js_date_select', $date);
        
        
        $date = $picker->day();                    // select tag with days
        $date .= $picker->month();                // select tag with mohth
        $date .= $picker->year();                // select tag with years
        
/*        $date .= ' ';
        $picker->default_time = 'CURRENT';
        $date .= $picker->hour();
        $date .= ':';
        
        $picker->setMinuteRange($min = 0, $max = 30, $gap = 30);
        $date .= $picker->minute();*/
        
        
        //setTimeRange($start = 8, $hours = 24, $gap_min = 30);
        $picker->time_format = $this->time_format; //$time_format;
        $picker->setTimeRange(8, 24, 60);
        
        $date .= '&nbsp;&nbsp;&nbsp;';
        $date .= $picker->time();        
        
        return $date;    
    }
    
    
    function getSql($cond_match, $condition) {
        
        $table = array(
            '[is]'     => '=', 
            '[is_not]' => '!=', 
            '[less]'   => '<', 
            '[more]'   => '>',
            '[equal]'   => '=', 
            '[contain]' => "LIKE '%%s%'", 
            '[not_contain]'   => "NOT LIKE '%%s%'", 
            '[start_with]'   => "LIKE '%s%'", 
            '[end_with]'   => "%%s",
            '[period_old_days]' => 'DAY',
            '[period_old_hours]' => 'HOUR'
        );
        
        
        $file = sprintf('workflow_map_%s.php', 'article_automation');
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;
        
        $arr = array();
        foreach($condition as $k => $v) {
            
            $args = $v['rule'];
            
            // order for params
            if(isset($items[$v['item']]['order'])) {
                $args = array();
                foreach(explode(',', $items[$v['item']]['order']) AS $v2) {
                    $args[] = $v['rule'][$v2];
                }
            }
            
            $arr[] = vsprintf($items[$v['item']]['sql'], $args);
        }
        
        $match = ($cond_match == 1) ? ' OR ' : ' AND ';
        $sql = ($cond_match == 1) ? sprintf('(%s)', implode($match, $arr)) : implode($match, $arr);
        $sql = strtr($sql, $table);
        
        return $sql;
    }
}


class WorkflowParserCondition_workflow extends WorkflowParserCondition
{
    
    var $default_item = 'draft';
    var $default_rule = array('is', 'published');
    var $etype = 'article';
    var $extra_html = false;
    
    
    function getCategorySelectRange() {
        return $this->model->getCategorySelectRange('article');
    }
    
    
    function getStatusSelectRange() {
        $m = WorkflowModel::instance('KBEntryModel');
        return $m->getListSelectRange('article_status', false);
    }
    
    
    function getPrivilegeSelectRange() {
        $m = WorkflowModel::instance('UserModel');
        return $m->getPrivSelectRange();
    }
    
    
    function getAuthorSelectRange($value) {
        $placeholders = array();
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
    
    
    function getUpdaterSelectRange($value) {
        return $this->model->getUserSelectRange($value, array(), false);
    }
}

?>