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


class CronLogView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // header generate
        $bp = &$this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // sort generate
        // $sort = &$this->getSort();
        // $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $magic = array_flip($manager->getCronMagic());

        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $str = '%s - %s';
            $start_date = $this->getFormatedDate($row['date_started_ts'], 'datetimesec');
            $finish_date = ($row['date_finished']) ? $this->getFormatedDate($row['date_finished_ts'], 'datetimesec') : '';                
            $row['date_range'] = sprintf($str, $start_date, $finish_date);
            
            $str = '<b>%s</b>&nbsp;&nbsp;(%s)';
            $formatted_date = $this->getFormatedDate($row['date_finished_ts'], 'datetime');
            $interval_date = $this->getTimeInterval($row['date_finished_ts']);                
            $row['date_executed'] = sprintf($str, $interval_date, $formatted_date);
        
            $row['is_error'] = ($row['exitcode']) ? '' : '<img src="images/icons/bullet.svg" />';
            $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);
            
            if(APP_DEMO_MODE) { 
                $row['output2'] = 'Hidden in DEMO mode';
            }            
            
            $row['view_msg'] = $this->msg['view_msg'];
            $row['range'] = $this->msg['cron_type'][$magic[$row['magic']]];
            
            $tpl->tplAssign($this->getViewListVars($obj->get('id'), $obj->get('exitcode')));
                                               
            $tpl->tplParse($row, 'row');
        }
        
        
        $tpl->tplAssign($this->msg);
        // $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getViewListVars($id = false, $active = false,  $own = false) {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active == 0) ? 'color: red;' : '';
        
        return $row;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        //$sort->setCustomDefaultOrder('datef', 2);
        $sort->setDefaultSortItem('datef', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('date_executed_msg', 'datef', 'date_finished',  $this->msg['date_executed_msg']);
        $sort->setSortItem('is_error_msg',  'exitcode', 'exitcode',  $this->msg['is_error_msg']);
        
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
        
        //magic
        @$status = $values['s'];
        $range = array();
        $range['all'] = '__';
        foreach($manager->getCronMagic() as $title => $num) {
            $range[$num] = $this->msg['cron_type'][$title];
        }        
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // magic
        @$v = $values['s'];
        
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $arr[] = "AND magic = '$v'";
        }
        
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            $v = addslashes(stripslashes($v));
            $arr[]  = "AND output LIKE '%{$v}%'";
        }
        
        return implode(" \n", $arr);
    }    
}
?>