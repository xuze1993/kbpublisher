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


class KBGlossaryView_list extends AppView
{
    
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {

        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBGlossaryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'KBGlossaryView_bulk', $this));
        }        
        
        //letter filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        
        $params2 = $this->parseLetterFilter($tpl, $_GET, $manager);
        $manager->setSqlParams('AND ' . $params2);
        

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter()));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset), array('definition'));
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('display_once', ($obj->get('display_once') ? '<img src="images/icons/bullet.svg" />' : ''));
            
            // actions/links
            $links = array();
            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), true, $actions));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj, $links) {
        $actions = array('status', 'update', 'delete');
        return $actions;
    }
    
    
    function &getSort() {
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->default_order = 1;
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('phrase_msg',       'phrase', 'phrase', $this->msg['phrase_msg'], 1);
        $sort->setSortItem('highlight_once_msg', 'once', 'display_once', $this->msg['highlight_once_msg']);
        $sort->setSortItem('status_msg', 'status', 'active', $this->msg['status_msg']);
        
        //$sort->getSql();
        //$sort->toHtml()
        return $sort;
    }
    
    
    function getFilter() {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
        }

        $tpl = new tplTemplatez($this->template_dir . 'search_form.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
    
        //status
        @$status = $values['s'];
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_published_msg'],
               0 => $this->msg['status_not_published_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
    
    
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
        
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];    
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }
        
        @$v = $values['q'];
        if($v) {
            $v = addslashes(stripslashes($v));
            $mysql['where'][] = "AND phrase LIKE '%" . $v . "%'";
            $mysql['where'][] = "OR definition LIKE '%" . $v . "%'";
            
            $sphinx['match'][] = $v;
        }
        
        $options = array('index' => 'glossary');
        $arr = $this->parseFilterSql($manager, $values['q'], $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }    
    
    
    function parseLetterFilter(&$tpl, $vars, $manager) {
            
        //letter filter
        $get = $vars;
        unset($get['letter']);
        $str = http_build_query($get);
        
        $letters = array();
        $result =& $manager->getGlossaryLettersResult();
        while($row = $result->FetchRow()) {
            $letter = _strtoupper(_substr($row['phrase'], 0, 1));
            $letters[$letter] = $letter;
        }    
        

        //SORT_LOCALE_STRING - compare items as strings, based on the current locale. 
        //Added in PHP 4.4.0 and 5.0.2. Before PHP 6, it uses the system locale, 
        // which can be changed using setlocale(). 
        //Since PHP 6, you must use the i18n_loc_set_default() function.
        sort($letters, SORT_LOCALE_STRING);

        foreach($letters as $letter) {
            $a['letter'] = $letter;
            $a['letter_link'] = $_SERVER['PHP_SELF'] . '?' . $str . '&letter=' . urlencode($letter);
            $a['letter_class'] = (@$vars['letter'] == $letter) ? 'tdSubTitle' : '';
            
            $tpl->tplParse($a, 'letter');
        }
    
    
        $ar = $vars; unset($ar['letter']);
        $tpl->tplAssign('all_letter_link', $_SERVER['PHP_SELF'] . '?'. http_build_query($ar));

        if(isset($vars['letter'])) {
            
            $l = addslashes(urldecode(_strtoupper($vars['letter'])));
            $l2 = addslashes(urldecode(_strtolower($vars['letter'])));
            $sql = "(phrase LIKE '$l%' OR phrase LIKE '$l2%')";
        } else {
            $sql = '1';
        }
        
        return $sql;
    }

}
?>