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

require_once 'core/common/CommonExportView.php'; 
require_once 'eleontev/CalendarUtil.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'eleontev/HTML/DatePicker.php';


class LoginLogView_list extends AppView
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


        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'user_id');
        
        // users
        if (!empty($rows)) {
            $users = $manager->getUserByIds($ids);
        }
        
        // type
        $type = $manager->getLoginTypeSelectRange($this->msg);
        
        // status
        $status = $manager->getLoginStatusSelectRange($this->msg);
        
        
        foreach($rows as $entry => $row) {

            $date_search = preg_replace('#[^0-9]#', '', $row['date_login']);
            $row['id'] = sprintf('%s_%s', $row['user_id'], $date_search);

            $row['date_login_formatted'] = $this->getFormatedDate($row['date_login_ts'], 'datetime');
            $row['date_login_interval'] = $this->getTimeInterval($row['date_login_ts']);
            
            $row['login_type'] = $type[$row['login_type']];
            //$row['login_status'] = $status[$row['login_status']];

            $more = array('filter[q]' => 'user_id:' . $row['user_id']);
            $row['user_id_link'] = $this->getLink('log', 'login_log', false, false, $more);
            
            $more = array('filter[q]' => 'ip:' . $row['user_ip_formatted']);
            $row['ip_link'] = $this->getLink('log', 'login_log', false, false, $more);
            
            if ($row['user_id'] != 0 && isset($users[$row['user_id']])) {
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


            $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);
            
            $row['view_msg'] = $this->msg['view_msg'];

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
    

    function getViewListVars($id = false, $active = false, $own = false) {
        $row = parent::getViewListVars($id, $active);
                
        $more = array('id' => $id);
        $row['detail_link'] = $this->getLink('log', 'login_log', false, 'detail', $more);

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

        $sort->setSortItem('date_msg',  'date_login', 'date_login', $this->msg['date_msg'], 2);
        $sort->setSortItem('user_id_msg', 'user_id', 'user_id', $this->msg['user_id_msg']);
        $sort->setSortItem('user_ip_msg','user_ip', 'user_ip', $this->msg['user_ip_msg']);
        $sort->setSortItem('type_msg','login_type', 'login_type', $this->msg['type_msg']);
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
        $select->setRange($manager->getLoginTypeSelectRange($this->msg),
                          array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange($manager->getLoginStatusSelectRange($this->msg),
                          array('all'=> '__'));

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

        // login_type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND login_type = '$v'";
        }

        // login_status
        @$v = $values['s'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND exitcode = '$v'";
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_login', $this->week_start);
        }
        
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                 
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v));
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
    
    
    function isSpecialSearchStr($str) {
        
        // if($ret = parent::isSpecialSearchStr($str)) {
        //     return $ret;
        // }

        $search = array();
        
        // get user by id (user_id:id)
        $search['user_id'] = "#^(?:user_id:\s*)(\d+)$#"; 
        
        // get all logins by ip
        $search['ip'] = "#^(?:ip:\s*)([\-\.\d]+)$#";
        
        // get all logins by ip range
        $search['ip_range'] = "#^(?:ip:\s*)([\- \.\d]+)$#";
        
        // get all logins by username
        $search['username'] = "#^(?:username:\s*)(\w+)$#";
        
        return $this->parseSpecialSearchStr($str, $search);
    }


    function getSpecialSearchSql($manager, $ret, $string, $id_field = 'e.id') {
        
        $arr = array();
        
        // if($sql = parent::getSpecialSearchSql($manager, $ret, $string, $id_field)) {
        //     $arr['where'] = $sql['where'];
        //     if(isset($sql['from'])) {
        //         $arr['from'] = $sql['from'];
        //     }
        //     
        //     return $arr;
        // }
        
        if($ret['rule'] == 'user_id') {
            $arr['where'] = sprintf("AND user_id = '%d'", $ret['val']);

        } elseif($ret['rule'] == 'username') {
            $v = addslashes(stripslashes($ret['val']));
            $user_id = $manager->getUserIdByUsername($v);
            $arr['where'] = sprintf("AND user_id = '%d'", $user_id);

        } elseif($ret['rule'] == 'ip') {
            
            $s = strlen($ret['val']);
            if($ret['val'][$s - 1] == '.') {
                $ret['val'] = substr($ret['val'], 0, $s - 1);
            }
            
            $ip = explode('.', $ret['val']);
            $c = count($ip);
            
            if ($c != 4) {
                
                $ip_mask_start = $ip;
                $ip_mask_end = $ip; 
                
                for($i = 0; $i < 4; $i++) {
                    if (!isset($ip[$i])) {
                        $ip_mask_start[$i] = '0';
                        $ip_mask_end[$i] = '255';
                    }
                }

                $ip_mask_start = implode('.', $ip_mask_start);
                $ip_mask_end = implode('.', $ip_mask_end);

                $str = "AND user_ip BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
                $arr['where'] = sprintf($str, $ip_mask_start, $ip_mask_end);
            } else {
    
                $arr['where'] = sprintf("AND user_ip = INET_ATON('%s')", $ret['val']);
            }
          
        } elseif($ret['rule'] == 'ip_range') {
            
            $ip = explode('-', $ret['val']);
            $ip[0] = explode('.', trim($ip[0]));
            $ip[1] = explode('.', trim($ip[1]));
            
            for($i = 0; $i < 4; $i++) {                        
                if(!isset($ip[0][$i])) $ip[0][$i] = '0';
                if(!isset($ip[1][$i])) $ip[1][$i] = '255';
            }
            
            $ip_mask_start = implode('.', $ip[0]);
            $ip_mask_end = implode('.', $ip[1]);

            $str = "AND user_ip BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
            $arr['where'] = sprintf($str, $ip_mask_start, $ip_mask_end);
        }
        
        // echo '<pre>', print_r($arr, 1), '</pre>';
        return $arr;    
    }

}
?>