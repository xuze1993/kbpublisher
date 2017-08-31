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
require_once 'core/common/CommonExportView.php';


class SearchLogView_list extends AppView
{
    
    var $template = 'list.html';
    
    var $start_day;
    var $end_day;
    // var $week_start = 0;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
        $this->addMsg('user_msg.ini');
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

        $msg = $this->msg['export_msg'];
        $link = "javascript: $('#tabs').show();void(0)";
        $button = array($msg => $link);

        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager), $button, false)); 


        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('search_option'));
        $ids = $manager->getValuesString($rows, 'user_id');
        
        // users
        if (!empty($rows)) {
            $users = $manager->getUserByIds($ids);
        }
        
        // search type
        $type = $manager->getSearchTypeSelectRange();
        
        
        foreach($rows as $entry => $row) {

            $date_search = preg_replace('#[^0-9]#', '', $row['date_search']);
            $row['id'] = sprintf('%s_%s_%s', $row['user_id'], $row['user_ip'], $date_search);
            
            $row['search_type'] = $type[$row['search_type']];
            $row['date_search_formatted'] = $this->getFormatedDate($row['date_search_ts'], 'datetime');
            $row['date_search_interval'] = $this->getTimeInterval($row['date_search_ts']);
            
            if ($row['user_id'] != 0) {
                $row['username'] = $users[$row['user_id']];
                if($row['user_id'] == AuthPriv::getUserId()) {
                    $row['userlink'] = $this->getLink('account', 'account_user');
                } else {
                    $more = array('id' => $row['user_id']);
                    $row['userlink'] = $this->getLink('users', 'user', false, 'update', $more);
                }                
            } else {
                $row['user_id'] = ''; // to hide 0
            }
            
            
            // search link
            $search_link = $manager->getSearchLink(unserialize($row['search_option']));
            $tpl->tplAssign('search_link', $search_link);

            $row['returned_rows'] = ($row['exitcode'] > 10) ? '> 10' : $row['exitcode'];

            $tpl->tplAssign($this->getViewListVars($row['id'], $row['exitcode']));
            $tpl->tplParse($row, 'row');
        }

        // export
        $export_types = array('xml', 'csv', 'xls');
        foreach ($export_types as $export_type) {
            $more = array('type' => $export_type);
            $export_links[$export_type] = $this->getActionLink('file', false, $more);  
        }
  
        $tpl->tplAssign('export_block', 
            CommonExportView::getExportFormBlock($obj, $manager, $export_links));
            
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml()); 
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getViewListVars($id = false, $returned_rows = false, $own = false) {
        $row = parent::getViewListVars($id, $returned_rows);
                
        $more = array('id' => $id);
        $row['detail_link'] = $this->getLink('log', 'search_log', false, 'detail', $more);

        $row['style'] = (!$returned_rows) ? 'color: red;' : '';

        return $row;
    }
    
    
    function &getSort() {
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_msg',  'date_search', 'date_search', $this->msg['date_msg'], 2);
        $sort->setSortItem('search_string_msg', 'search_string', 'search_string', $this->msg['search_string_msg']);
        $sort->setSortItem('returned_rows_msg','exitcode', 'exitcode', $this->msg['returned_rows_msg']);
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
        $select->setRange($manager->getSearchTypeSelectRange($this->msg),
                          array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array('all'=> '__', 
                                    1 => $this->msg['yes_msg'], 
                                    0 => $this->msg['no_msg']));

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

        // search type      
        @$v = $values['t'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $arr[] = "AND search_type = '$v'";
        }

        // is success
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            if($v) {
                $arr[] = "AND exitcode > 0";
            } else {
                $arr[] = "AND exitcode = 0";
            }
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_search', $this->week_start);
        }
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            $this->controller->loadClass('LoginLogView_list', 'log/login');            
            $view_login = new LoginLogView_list();

            if($ret = $view_login->isSpecialSearchStr($v)) {
                 
                if($sql = $view_login->getSpecialSearchSql($manager, $ret, $v)) {
                    $arr[] = $sql['where'];
                    if(isset($sql['from'])) {
                        $arr_from[] = $sql['from'];
                    }
                }
                
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND search_string LIKE '%{$v}%'";
            }
        }

        $arr = implode(" \n", $arr);
        //echo '<pre>', print_r($arr, 1), '</pre>';

        return $arr;
    }

}
?>