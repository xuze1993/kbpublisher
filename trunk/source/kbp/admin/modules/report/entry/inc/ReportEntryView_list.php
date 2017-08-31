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
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';


class ReportEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $report_type = 1; // default
    var $default_period = 'this_week';
    var $start_day;
    var $end_day;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        $this->addMsg('report_msg.ini');
        $this->addMsg('datetime_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        if (!empty($_GET['filter']['t'])) {
            $this->report_type = $_GET['filter']['t'];
        }
        
        $entry_managers = array();
        $entry_managers[1] = new KBEntryModel;
        $entry_managers[2] = new FileEntryModel;
        
        // categories
        $categories = array();
        foreach ($entry_managers as $entry_type => $entry_manager) {
            $categories[$entry_type] = $entry_manager->getCategoryRecords();
            $categories[$entry_type] = $this->stripVars($categories[$entry_type], array(), false);
        }
        
        
        // filter
        $params = $this->getFilterSql($manager, $entry_managers, $categories);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsFrom($params['from']);
        //$manager->setSqlParamsJoin($params['join']);
        
        $tpl->tplAssign('filter_block_tmpl', $this->getFilter($manager, $entry_managers, $categories));
        
        $page_by_page = false;
        $limit = $this->getLimit();
        
        if ($limit == -1) {
            $page_by_page = true;
        }
        
        if ($page_by_page) {
            $bp =& $this->pageByPage($manager->limit, $manager->getCountRecords());
            $tpl->tplAssign('header', $bp->nav . '<br />');
            // $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, '', false, false));
        }
                
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
            
        $hidden_fields = array(
            'module' => 'report',
            'page' => 'report_entry',
            'filter[t]' => $this->report_type);
        
        $entry_filter_params = $this->getEntryFilterParams();
        $hidden_fields = array_merge($hidden_fields, $entry_filter_params);
        
        $tpl->tplAssign('hidden_fields', http_build_hidden($hidden_fields));
        

        // timestart('get_report_records');
        $offset = -1;
        
        if ($page_by_page) {
            $limit = $bp->limit;
            $offset = $bp->offset;
            
            $hits_sum = $manager->getTotalHits();
        }
        
        $rows = $this->stripVars($manager->getRecords($limit, $offset));
        // timestop('get_report_records');
        
        $entry_ids = $manager->getValuesString($rows, 'entry_id');
        
        if (!isset($hits_sum)) {
            $hits = $manager->getValuesArray($rows, 'value');
            $hits_sum = array_sum($hits);
        }
        
        // users
        if (!empty($rows)) {
            $field = ($this->report_type == 2) ? 'filename' : 'title';
            $titles = $this->stripVars($manager->getTitlesByIds($this->report_type, $entry_ids, $field));
        }
        
        // type
        $type = $manager->getReportTypeSelectRange($this->msg);
        
        
        foreach($rows as $entry => $row) {
            
            $row['title'] = (empty($titles[$row['entry_id']])) ? '--' : $this->getSubstring($titles[$row['entry_id']], 80);
            
            $more = array('filter[t]' => $this->report_type, 'entry_id[]' => $row['entry_id']);
            $more = array_merge($more, $entry_filter_params);
            $row['entry_link'] = $this->getLink('report', 'report_entry', false, false, $more);
            
            $row['rate'] = round(($row['value'] / $hits_sum) * 100, 2);
            $row['rate_width'] = $row['rate'] * 2;
            $row['value'] = number_format($row['value'], 0, '', ' ');
            
            $tpl->tplAssign($this->getViewListVars());
            
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

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setDefaultSortItem('hits', 2);
        
        $sort->setSortItem('id_msg', 'id', 'entry_id', $this->msg['id_msg']);
        $sort->setSortItem('hits_num_msg', 'hits', 'value', $this->msg['hits_num_msg']);
        
        return $sort;
    }
    
    
    function getFilter($manager, $entry_managers, $categories) {
        
        @$values = $_GET['filter'];
          
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $tpl->tplSetNeeded('/first_step_js');
        $tpl->tplSetNeeded('/first_step_blocks');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $select->setRange($manager->getReportTypeSelectRange($this->msg));
        $tpl->tplAssign('type_select', $select->select($this->report_type));
        
        // period
        $select->setRange($manager->getReportPeriodSelectRange($this->msg));
        
        $tpl->tplAssign('daily_datepicker_display', 'block');
        
        $v = (empty($values['p'])) ? $this->default_period : $values['p'];
        $tpl->tplAssign('custom_display', ($v == 'custom_period') ? 'block' : 'none');
        $tpl->tplAssign('period_select', $select->select($v));
        
        if (empty($v) || $v != 'custom_period' ) {
            $date_from = time();
            $date_to = $date_from;
               
        } else {
            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));
        }
        
        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));
        
        // category        
        $categories = $this->stripVars($categories, array(), 'stripslashes'); // for compability with other $js_hash
        foreach ($categories as $entry_type => $entry_categories) {
            $js_hash = array();
            $str = '{label: "%s", value: "%s"}';
            foreach(array_keys($entry_categories) as $k) {
                $js_hash[] = sprintf($str, addslashes($entry_categories[$k]['name']), $k);
            }
            
            $categories_js[] = sprintf('%s: [%s]', $entry_type, implode(",\n", $js_hash));
        }
        
        $tpl->tplAssign('categories', implode(',', $categories_js));
        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));
        
        
        $type_has_categories = in_array($this->report_type, array(1, 2));
        $category_block_display = ($type_has_categories) ? 'block' : 'none';
        $tpl->tplAssign('category_block_display', $category_block_display);
        
        $category_init_js = ($type_has_categories) ? sprintf('CategoriesInputHandler.init(categories[%s]);', $this->report_type) : '';
        $tpl->tplAssign('category_init_js', $category_init_js);
        
        if(!empty($values['c'])) {
            $category_id = (int) $values['c'];
            $ch_disabled = '';
            $category_name = $this->stripVars($categories[$this->report_type][$category_id]['name']);
            $tpl->tplAssign('category_name', $category_name);
        } else {
            $category_id = 0;
            $ch_disabled = 'disabled';
        }
        
        $tpl->tplAssign('category_id', $category_id);
        $tpl->tplAssign('ch_disabled', $ch_disabled);
        
        
        // limit
        $limit_range = array(5 => 5, 10 => 10, 20 => 20, 100 => 100, -1 => '__');
        $select->setRange($limit_range);
        
        $v = (empty($values['l'])) ? 10 : $values['l'];
        $tpl->tplAssign('limit_select', $select->select($v)); 
        
        
        // count
        $v = (!isset($values['ct']) || $values['ct'] == '') ? '' : (int) $values['ct'];
        $tpl->tplAssign('view_count', $v);
        
        $v = (empty($values['cts'])) ? 1 : $values['cts'];
        $tpl->tplAssign('count_checked_' . $v, 'checked="checked"');
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    

    function getFilterSql($manager, $entry_managers, $categories) {

        $arr = array();
        $arr_from = array();
        
        @$values = $_GET['filter']; 

        // type
        $arr[] = "AND re.report_id = '$this->report_type'";
        
        
        // period
        $v = (empty($values['p'])) ? $this->default_period : $values['p'];
        $arr[] = $this->getPeriodSqlReportEntry($v, $values);
        
        // entry ids
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 're.entry_id')) {
                    $arr[] = $sql['where'];
                }
            } else {
                unset($_GET['filter']['q']);
            }
        }
        
        
        // category
        @$v = $values['c'];
        $type_has_categories = in_array($this->report_type, array(1, 2));
        if(!empty($v) && $type_has_categories) {
            $category_id = (int) $v;
            
            $arr[] = 'AND re.entry_id = ec.entry_id';
            $arr_from[] = sprintf(', %s ec', $entry_managers[$this->report_type]->tbl->entry_to_category);
            
            if(!empty($values['ch'])) {
                $children = array_merge($entry_managers[$this->report_type]->getChilds($categories[$this->report_type], $category_id), array($category_id));
                $children = implode(',', $children);
                $arr[] = "AND ec.category_id IN($children)";
            } else {
                $arr[] = "AND ec.category_id = $category_id";
            }
        }
        
        
        // count
        if(isset($values['ct']) && $values['ct'] != '') {
            $v = (int) $values['ct'];
            
            $values['cts'] = (int) $values['cts'];
            $signs = array(
                1 => '>', 2 => '<', 3 => '='
            );
            $sign = $signs[$values['cts']];
            
            $manager->having_params .= sprintf(' AND value %s %d', $sign, $v);
        }
        
        $arr['where'] = implode(" \n", $arr);
        $arr['from'] = implode(" \n", $arr_from);

        return $arr;
    }
    
    
    function getLimit() {
        @$values = $_GET['filter'];
        $limit = (empty($values['l'])) ? 10 : $values['l'];
        return $limit;
    }
    
    
    function isSpecialSearchStr($str) {
        $search['ids'] = "#^(?:ids:)?(\s?[\d,\s?]+)$#";
        return $this->parseSpecialSearchStr($str, $search);
    }  
    
    
/*
    function getPeriodSql($period, $values) {
                              
        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();

        $sql = '';
  
        switch ($period) {
        case 'previous_day': // ------------------------------
            $this->start_day = date('Y-m-d', time() - 86400);
            $this->end_day = $this->end_day;
            
            $sql = sprintf("AND date_day = '%s'", $this->start_day);
            break;
            
        case 'this_week': // ------------------------------
            $d = $cal->setWeek();
            $this->start_day = date('Y-m-d', $d['start_day']);
            $this->end_day = date('Y-m-d', $d['end_day']);
            
            $sql = sprintf('AND date_week = %s', $cal->getWeekNumber(time()));
            break;

        case 'previous_week': // ------------------------------
            $d = $cal->setWeek();
            $this->start_day = date('Y-m-d', $d['prev']);
            $this->end_day = date('Y-m-d', $d['prev'] + $cal->sek_in_day * 6);
            
            $sql = sprintf('AND date_week = %s', $cal->getWeekNumber($d['prev']));
            break;
            
        case 'this_month': // ------------------------------
            $this->start_day = date('Y-m-01');
            $this->end_day = date('Y-m-' . $cal->cur_month_num_days);
            
            $sql = sprintf('AND date_month = %s', date('Ym'));
            break;

        case 'previous_month': // ------------------------------            
            $d = $cal->setMonth();
            $this->start_day = date('Y-m-d', $d['prev']);
            $this->end_day = date('Y-m-d', $d['prev'] + (($cal->prev_month_num_days - 1) * $cal->sek_in_day));
            
            $sql = sprintf('AND date_month = %s', date('Ym', $d['prev']));
            break;
        
        case 'this_year': // ------------------------------
            $this->start_day = sprintf('%s-%s-%s', $cal->year, '01', '01');
            $this->end_day = sprintf('%s-%s-%s', $cal->year, '12', '31'); 
            
            $sql = sprintf('AND date_year = %s', date('Y'));
            break;
            
        case 'previous_year': // ------------------------------
            $this->start_day = sprintf('%s-%s-%s', $cal->prev_year, '01', '01');
            $this->end_day = sprintf('%s-%s-%s', $cal->prev_year, '12', '31');  
            
            $sql = sprintf('AND date_year = %s', $cal->prev_year);
            break;
                        
        case 'all_period': // ------------------------------
            break;
            
        case 'custom_period': // ------------------------------
            $this->start_day = date('Y-m-d', strtotime(urldecode($values['date_from'])));
            $this->end_day = date('Y-m-d', strtotime(urldecode($values['date_to'])));
            
            $str = "AND date_day BETWEEN '%s' AND '%s'";
            $sql = sprintf($str, $this->start_day, $this->end_day);
            break;
        }
        
        // testing
        //$sql = sprintf("AND date_day BETWEEN '%s' AND '%s'", $this->start_day, $this->end_day);
        //var_dump($sql);

        return $sql;
    }*/


    function getPeriodSqlReportEntry($period, $values) {
                         
        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();

        $data = TimeUtil::getPeriodData($period, $values, $this->week_start);
        
        $this->start_day = $data['start_day'];
        $this->end_day = $data['end_day'];
        $sql = '';
        
  
        switch ($period) {
        case 'previous_day': // ---------------------------
            $sql = sprintf("AND date_day = '%s'", $this->start_day);
            break;
            
        case 'this_week': // ------------------------------
            $d = $cal->setWeek();
            $sql = sprintf('AND date_week = %s', $cal->getWeekNumber(time()));
            break;

        case 'previous_week': // --------------------------
            $d = $cal->setWeek();
            $sql = sprintf('AND date_week = %s', $cal->getWeekNumber($d['prev']));
            break;
            
        case 'this_month': // ------------------------------
            $sql = sprintf('AND date_month = %s', date('Ym'));
            break;

        case 'previous_month': // ---------------------------
            $m = $cal->getTimestampValues();
            $sql = sprintf('AND date_month = %s', date('Ym', $m['prev_month_start']));
            break;
        
        case 'this_year': // -------------------------------
            $sql = sprintf('AND date_year = %s', $cal->year);
            break;
            
        case 'previous_year': // -----------------------------
            $sql = sprintf('AND date_year = %s', $cal->prev_year);
            break;
                        
        case 'all_period': // --------------------------------
            break;
            
        case 'custom_period': // ------------------------------
            $str = "AND date_day BETWEEN '%s' AND '%s'";
            $sql = sprintf($str, $this->start_day, $this->end_day);
            break;
        }
        
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }

    
    
    function getEntryFilterParams() {
        @$values = $_GET['filter'];
        
        $current_period = (empty($values['p'])) ? $this->default_period : $values['p'];
        
        $params = array();
        $range = 'daily';
        $period = 'range_day';
        
        switch ($current_period) {
            case 'previous_day':
            	$prev_day = date('m/d/Y', time() - 86400);
            	$params['filter[date_from]'] = $prev_day;
                $params['filter[date_to]'] = $prev_day;
        	    break;
            
            case 'this_week':
                $period = 'this_week';
            	break;
                
            case 'previous_week':
                $period = 'previous_week';
            	break;
                
            case 'this_month':
                $period = 'this_month';
            	break;
                
            case 'previous_month':
                $period = 'previous_month';
            	break;
                
            case 'this_year':
            	$range = 'monthly';
                $period = 'this_year';
            	break;
                
            case 'previous_year':
            	$range = 'monthly';
                $period = 'previous_year';
            	break;
            
            case 'custom_period':
                $params['filter[date_from]'] = $values['date_from'];
                $params['filter[date_to]'] = $values['date_to'];
                break;
            
            case 'all_period':
                $range = 'yearly';
                $period = 'all_period';
                break;
        }
        
        //if ($current_period != 'all_period') {
            $params['filter[r]'] = $range;
            $params['filter[p]'] = $period;
        //}
        
        return $params;
    }

}
?>