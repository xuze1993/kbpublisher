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
                                             

class FileRuleView_list extends AppView
{
    
    var $template = 'list.html';
        
    
    function execute(&$obj, &$manager) {      
                               
        $this->addMsg('log_msg.ini');
                
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $manager->setSqlParams('AND entry_type = ' . $obj->get('entry_type'));
        
        // filter sql
        $manager->setSqlParams($this->getFilterSql($manager));  
        
        // header generate
        $bp = &$this->pageByPage($manager->limit, $manager->getRecordsSql()); 
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));      
            
        // sort generate
        $sort = &$this->getSort();
        $sort_order = $sort->getSql();
        $manager->setSqlParamsOrder($sort_order);
                              
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('entry_obj'));
                                             
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            if($obj->get('parse_child')) {
                $obj->set('directory', $obj->get('directory') . '*');
            }
            
            if($row['date_executed']) {
                $formatted_date = $this->getFormatedDate($row['date_executed'], 'datetime');
                $interval_date = $this->getTimeInterval($row['date_executed']);
            } else {
                $formatted_date = '--';
                $interval_date = '--';
            }
            
            $tpl->tplAssign('formatted_date', $formatted_date);            
            $tpl->tplAssign('interval_date', $interval_date);
            
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
        $actions = array('update', 'delete');
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        // $sort->setCustomDefaultOrder('de', 2);
        $sort->setDefaultSortItem('de', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('id_msg','id', 'id', $this->msg['id_msg']);
        $sort->setSortItem('last_executed_msg','de', 'date_executed', $this->msg['last_executed_msg']); 
        $sort->setSortItem('directory_msg','directory', 'directory', $this->msg['directory_msg']); 
        $sort->setSortItem('status_active_msg','status', 'active', $this->msg['status_active_msg']);        
        
        return $sort;
    }    
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
    
        //status
        @$status = $values['s'];
        $range = array('all'=> '__',
                          1 => $this->msg['status_active_msg'],
                          0 => $this->msg['status_not_active_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse(@$values);
        return $tpl->tplPrint(1);
    }    
    
    
    function getFilterSql($manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $arr[] = "AND active = '$v'";
        }
        
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                 
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v)) {
                    $arr[] = $sql['where'];
                    if(isset($sql['from'])) {
                        $arr_from[] = $sql['from'];
                    }
                }
                
            } else {
                $v = addslashes(stripslashes($v));
                $v = str_replace('*', '%', $v);
                $arr[]  = "AND directory LIKE '$v'";
            }
        }        
        
        return implode(" \n", $arr);
    }
    
    
    function isSpecialSearchStr($str) {
        
        // if($ret = parent::isSpecialSearchStr($str)) {
        //     return $ret;
        // }

        $search = array();
        $search['author_id'] = "#^(?:author_id:\s*)(\d+)$#"; 
        
        return $this->parseSpecialSearchStr($str, $search);
    }
    
    
    function getSpecialSearchSql($manager, $ret, $string, $id_field = 'e.id') {
        
        $arr = array();
        
        if($ret['rule'] == 'author_id') {
            $arr['where'] = sprintf('AND entry_obj LIKE \'%%s:9:"author_id";s:%s:"%s";%%\'', strlen($ret['val']), $ret['val']);
        }
        
        return $arr;    
    }
}
?>