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

require_once 'eleontev/CalendarUtil.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'eleontev/HTML/DatePicker.php';


class SphinxLogView_list extends AppView
{
    
    var $template = 'list.html';
    
    var $start_day;
    var $end_day;
    // var $week_start = 0;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
        $this->addMsg('random_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . $this->template);

        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // header                                                                                                    
        $bp = &$this->pageByPage($manager->limit, $manager->getRecordsSql());
       
        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager), false, false));


        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // type
        $type = $manager->getActionTypeSelectRange($this->msg);
        
        
        foreach($rows as $entry => $row) {

            $row['date_executed_formatted'] = $this->getFormatedDate($row['date_executed_ts'], 'datetime');
            $row['date_executed_interval'] = $this->getTimeInterval($row['date_executed_ts']);
            
            $row['action_type'] = $type[$row['action_type']];

            $row['output2'] = nl2br($row['output']);
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);
            
            $row['view_msg'] = $this->msg['view_msg'];

            $tpl->tplAssign($this->getViewListVars($row['id'], $row['exitcode']));
            
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml()); 
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getViewListVars($id = false, $active = false, $own = false) {
        $row = parent::getViewListVars($id, $active);
                
        $more = array('id' => $id);
        $row['detail_link'] = $this->getLink('this', 'this', false, 'detail', $more);

        // $row['is_success'] = ($row['exitcode'] == 1) ? '<img src="images/icons/bullet.svg" />' : '';
        $row['is_error'] = ($active != 1) ? '<img src="images/icons/bullet.svg" />' : '';
        $row['style'] = ($active != 1) ? 'color: red;' : '';

        return $row;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_msg',  'date_executed', 'id', $this->msg['date_msg'], 2);
        $sort->setSortItem('type_msg','action_type', 'action_type', $this->msg['type_msg']);
        $sort->setSortItem('is_error_msg','exitcode', 'exitcode', $this->msg['is_error_msg']); 
        
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
        
        // type
        $select->setRange($manager->getActionTypeSelectRange($this->msg),
                          array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array(
			'all'=> '__', 
			'0' => $this->msg['yes_msg'], 
			'1' => $this->msg['no_msg'])
		);

        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        // period
        $range = AppMsg::getMsgs('datetime_msg.ini', false, 'period_range');
        $range['all_period'] = '__';
        unset($range['this_year'],$range['previous_year']);
        $select->setRange($range);
        
        @$v = $values['p'];
        $tpl->tplAssign('custom_display', ($v == 'custom_period') ? 'block' : 'none');
        $tpl->tplAssign('period_select', $select->select($v));
        
        
        $start_date_timestamp = $manager->getStartDate();
        $start_date = date('m/d/Y', $start_date_timestamp);
        $tpl->tplAssign('min_date', $start_date);
        
        if (empty($v) || $v != 'custom_period') {
            $date_from = time();
            $date_to = time();
            
        } else {
            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));
            
            if (!$date_from && $date_to) {
                $date_from = $start_date_timestamp;
            }
            
            if ($date_from && !$date_to) {
                $date_to = time();
            }
            
            if (!$date_from && !$date_to) { // both dates are missing
                $date_from = time();
                $date_to = time();
            }
        }
        
        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    

    function getFilterSql($manager) {

        $arr = array();
        $arr_select = array();       
        
        @$values = $_GET['filter']; 

        // type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND action_type = '$v'";
        }

        // status
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $arr[] = "AND exitcode = '$v'";
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_executed', $this->week_start);
        }
        
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                 
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 'id'));
                    $arr[] = $sql['where'];
                    if(isset($sql['from'])) {
                        $arr_from[] = $sql['from'];
                    }
                
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND output LIKE '%{$v}%'";
            }
        }

        $arr = implode(" \n", $arr);
        // echo '<pre>', print_r($arr, 1), '</pre>';

        return $arr;
    }

}
?>