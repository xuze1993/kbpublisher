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

require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/CalendarUtil.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'eleontev/URL/RequestDataUtil.php';
require_once 'core/common/CommonExportView.php';
require_once 'core/common/CommonReportChart.php';


class ReportEntryView_list_entry extends ReportUsageView_common
{
    
    var $template = 'list_entry.html';
    var $template_popup = 'list_popup.html';
    
    var $date_format = false; // default will be used
    
    var $default_range = 'daily';
    var $default_period = 'this_week';
    
    var $start_day;
    var $end_day;
    
    var $chart_max_period = 31;
    var $chart_type = 'canvas'; // flash or canvas
    
        
    static function factory($view) {
        return 'ReportEntryView_list_entry_' . $view;
    }
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('report_msg.ini');
        $this->addMsg('random_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        // fix to add missed words from en
        $types_msg = AppMsg::getMsg('report_msg.ini', false, 'report_type');
        $this->msg['report_type'] = $types_msg;
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
                                                
        $types = array('xml', 'csv', 'xls');
        foreach ($types as $type) {
            $export_links[$type] = $this->getActionLink('file', false, array('type' => $type));  
        }
        
        $tpl->tplAssign('tab_block_tmpl', CommonExportView::getExportFormBlock($obj, $manager, $export_links));
        
        
        // breadcrumbs
        $report_type = $this->getReportToGenerate();
        $link = $this->getLink('report', 'report_entry', false, false, array('filter[t]' => $report_type));

        $nav = array();
        $item_title = $this->msg['report_type'][$manager->report_type[$report_type]['key']];
        $nav[1] = array('link' => $link, 'item' => $item_title);
        
        $params = $this->getFilterParams();
        $filtered_link = $this->getLink('report', 'report_entry', false, false, $params);
        $nav[2] = array('link' => $filtered_link, 'item' => $this->msg['filtered_views_msg']);   
        $nav[3]['item'] = $this->msg['detail_msg'];
        $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));
        
        // filter sql
        $params = $this->getFilterSql($obj, $manager);
        //echo '<pre>', print_r($params, 1), '</pre>';
        $manager->setSqlParams($params);
        
        $tpl->tplAssign('filter_block_tmpl', $this->getFilter($obj, $manager));
        
        $tpl->tplAssign('entries_num', count($obj->get('entry_id')));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array('entry_id' => $obj->get('entry_id'), 'filter[t]' => $report_type);
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        
        $xajax->registerFunction(array('setDefaultChartType', 'CommonReportChart', 'ajaxSetDefaultChartType'));
        
        // header generate
        //$bp = &$this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        //$tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        //$sort = &$this->getSort();
        //$manager->setSqlParamsOrder($sort->getSql());
        
        // group
        $manager->sql_params_group = ($this->group_field) ? 'entry_id,' . $this->group_field : 'entry_id';
        
        // get records
        $manager->setSqlParams(sprintf('AND entry_id IN (%s)', implode($obj->get('entry_id'), ',')));
        $rows = $this->stripVars($manager->getEntryRecords($this->force_index, $this->week_start));
        //echo '<pre>', print_r($rows, 1), '</pre>';
        
        // titles, total
        $entry_ids_str = implode(',', $obj->get('entry_id'));
        $field = ($report_type == 2) ? 'filename' : 'title';
        $entry_titles = $this->stripVars($manager->getTitlesByIds($report_type, $entry_ids_str, $field));
        
        $reported_ids = $manager->getReportedEntryIds($report_type, $entry_ids_str);
        $nothing_to_display = empty($reported_ids);
        
        foreach ($obj->get('entry_id') as $entry_id) {
            $a = array();
            $a['entry_id'] = $entry_id;
            $a['id_msg'] = $this->msg['id_msg'];
            
            if (empty($entry_titles[$entry_id])) {
                $a['title'] = '--';
                $a['color'] = 'red';
                $entry_status = $this->msg['nonexistent_msg'];
                
                if (in_array($entry_id, $reported_ids)) { // been removed
                    $a['color'] = 'grey';
                    $entry_status = $this->msg['removed_msg'];
                }
                
                $a['entry_status'] = ', ' . $entry_status;
                 
            } else {
                $a['title'] = $entry_titles[$entry_id];
                $a['color'] = 'black';
            }
            
            $hits = @$manager->getValuesArray($rows, $entry_id);
            $a['total'] = array_sum($hits);
            
            $tpl->tplParse($a, 'entry_title');
        }
            
        if (!$nothing_to_display) {
            $tpl->tplSetNeeded('/report');
            
            // chart
            if (count($this->date_range) > $this->chart_max_period) {
                $tpl->tplSetNeeded('/chart_not_available');
                
            } else {
                foreach ($obj->get('entry_id') as $id) {
                    $this->chart_titles[$id] = sprintf('%s %s', $this->msg['id_msg'], $id);
                }
                $tpl->tplAssign('report_chart_block', CommonReportChart::getChartBlock($this->chart_type, $rows, $obj->get('entry_id'), $this));
            }
            
            $entry_ids = $obj->get('entry_id');
            $single_article = (count($entry_ids) == 1);
            
            if (!$single_article) {
                $tpl->tplSetNeeded('/entry_column');
            }
            
            foreach($this->date_range as $date) {
                $date_td_set = false;
                
                $row['date'] = $date;
                $row = array_merge($row, $this->getViewListVarsCustom(1, 1, $date));
                
                foreach($entry_ids as $entry_id) {
                    $row['entry_id'] = $entry_id;
                    
                    if (!$date_td_set) {
                        $row['formatted_date'] = $this->getFormatedDateReport($date);
                    
                        if($date_link = $this->getFilterLink($date)) {
                            $str = '<a href="%s">%s</a>';
                            $row['formatted_date'] = sprintf($str, $date_link, $row['formatted_date']);
                        }
                        
                        $tpl->tplSetNeeded('row/date');
                        
                        if (!$single_article) {
                            $row['rowspan'] =  sprintf('rowspan="%d"', count($entry_ids));   
                        }
                        
                        $date_td_set = true;
                    }           
                
                    $row['value'] = (empty($rows[$date][$entry_id])) ? '--' : number_format($rows[$date][$entry_id], 0, '', ' ');
                    
                    if (!$single_article) {
                        $row['title'] = (empty($entry_titles[$entry_id])) ? '--' : $this->getSubstring($entry_titles[$entry_id], 60);
                        $tpl->tplSetNeeded('row/entry_column');
                    }
                    
                    $tpl->tplParse($row, 'row');
                }
            }
        
        }
                
        $tpl->tplAssign($this->msg);
        //$tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsCustom($id = false, $active = false, $date = false) {
        
        $row = parent::getViewListVarsRow($id, $active);
        
        $row['style'] = $this->getHighlightStyle($date);
        
        return $row;
    }    
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('datep', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('date_posted_msg',  'datep', 'date_posted',  $this->msg['posted_msg']);
        //$sort->setSortItem('entry_title_msg',  'title', 'title',        $this->msg['entry_title_msg']);
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);
            }
        }
        
        return $sort;
    }
    
    
    function getHighlightStyle() {
        return false;
    }
    

    function getReportToGenerate() {
        return $_GET['filter']['t'];
    }
    
    
    function getFilterLink($date) {
        return false;
    }    
    
    
    function getFormatedDateReport($date) {
        return $this->getFormatedDate($date, $this->date_format);
    }    
    
    
    function getFilter($obj, $manager) {

        @$values = $_GET['filter'];
        $type = $this->getReportToGenerate();
        //$period = $this->
        

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $select->setRange($manager->getReportTypeSelectRange($this->msg));
        $tpl->tplAssign('type_select', $select->select($type));
        //$tpl->tplAssign('type_disabled', 'disabled');
        
        // range
        $tpl->tplSetNeeded('/range');
        $select->setRange($manager->getRangeSelectRange($this->msg['report_range']));
        $tpl->tplAssign('range_select', $select->select(@$values['r']));
        
        
        $tpl->tplSetNeeded('/entry_js');
        $tpl->tplAssign('daily_datepicker_display', 'none');
        $tpl->tplSetNeeded('/entry_fields');
        
        $tpl->tplAssign('myOptionsJson', $this->getPeriodSelectJson());
        
        if (!empty($values['date_from'])) {
            $date_from = strtotime($values['date_from']);
            $date_to = strtotime($values['date_to']);
            
        } else {
            $date_from = time();
            $date_to = $date_from;
        }
        
        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));
        
        // custom period
        $picker = new DatePicker();
        $picker->setFormName('search_form');        // set form name
        $picker->setFormMethod($_GET);                // set form method
        
        $start_date_ts = $manager->getEarliestReportDate();
        
        $picker->setYearRange(date('Y', $start_date_ts), date('Y') + 1);
        $picker->month_format = '%b';
        
        //$date = $picker->js();                    // js function
        //$tpl->tplAssign('js_date_select', $date);
        
        $month_from = (isset($values['from'])) ? $values['from']['month'] : date('m', $start_date_ts);
        $year_from = (isset($values['from'])) ? $values['from']['year'] : date('Y', $start_date_ts);
        
        $selected = mktime(0, 0, 0, $month_from, 1, $year_from);
        $picker->setDate($selected);
        $picker->setSelectName('filter[from]');
        
        $date['month_from_select'] = $picker->month();
        $date['year_from_select'] = $picker->year();
        
        
        $month_to = (isset($values['to'])) ? $values['to']['month'] : 12;
        $year_to = (isset($values['to'])) ? $values['to']['year'] : date('Y', $start_date_ts);
        
        $selected = mktime(0, 0, 0, $month_to, 31, $year_to);
        $picker->setDate($selected);
        $picker->setSelectName('filter[to]');
        
        $date['month_to_select'] = $picker->month();
        $date['year_to_select'] = $picker->year();
        
        $tpl->tplAssign($date);
        
        if (empty($values['q'])) {
            $entry_ids_str = implode(',', $obj->get('entry_id'));
            $tpl->tplAssign('q', $entry_ids_str);
        }
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($obj, $manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // report
        $v = $this->getReportToGenerate();
        $arr[] = "AND report_id = '$v'";
        
        // entry ids
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                $sql = $this->getSpecialSearchSql($manager, $ret, $v, 'entry_id');
                if($sql) {
                    $arr[] = $sql['where'];
                    $obj->set('entry_id', explode(',', $v)); // overwriting GET
                }
            } else {
                unset($_GET['filter']['q']);
            }
        }
            
        // period
        $arr[] = $this->getReportPeriodSql($manager);
        
        $arr = implode(" \n", $arr);
        return $arr;
    }
    
    
    function getFilterParams() {
        @$values = $_GET['filter'];
        
        $current_period = (empty($values['p'])) ? $this->default_period : $values['p'];
        
        $params = array('filter[t]' => $this->getReportToGenerate());
        $period = 'custom_period';
        
        switch ($current_period) {
            case 'range_day':
            	$params['filter[date_from]'] = $values['date_from'];
                $params['filter[date_to]'] = $values['date_to'];
        	    break;
            
            case 'range_week':
                $params['filter[date_from]'] = $values['date_from'];
                $params['filter[date_to]'] = $values['date_to'];
            	break;
                
            case 'range_month':
                $params['filter[date_from]'] = sprintf('%s/01/%s', $values['from']['month'], $values['from']['year']);
                $params['filter[date_to]'] = sprintf('%s/%s/%s', $values['to']['month'], date('t', $values['to']['month']), $values['to']['year']);
            	break;
                
            case 'range_year':
                $params['filter[date_from]'] = '01/01/' . $values['from']['year'];
                $params['filter[date_to]'] = '12/31/' . $values['to']['year'];
            	break;
            
            default:
                $period = $current_period;
                break;
        }
        
        //if ($current_period != 'all_period') {
            $params['filter[p]'] = $period;
        //}
        
        
        return $params;
    }


    function getPeriodSelectJson() {

        $filter = array();
        $filter['yearly']  = array('all_period', 'range_year');
        $filter['monthly'] = array('this_year', 'previous_year', 'range_month');
        $filter['weekly']  = array(/*'this_month', 'previous_month',*/ 'this_year', 'previous_year', 'range_week');
        $filter['daily']   = array('this_week', 'previous_week', 'this_month', 'previous_month', 'range_day');
                
        $selected = $this->getPeriodToGenerate();
        $json = array();
        foreach($filter as $range_key => $range_data) {
            
            $json_body = array();
            foreach($range_data as $v) {
                $val  = $v;
                $text = $this->msg['report_period'][$v];
                $s    = ($v == $selected) ? 'true' : 'false';
                    
                $json_body[] = sprintf('{"val": "%s", "text": "%s", "s": %s}', $val, $text, $s);
            }
            
            $json[] = sprintf("\"%s\": [\n%s\n]", $range_key, implode(",\n", $json_body));
        }
    
        return implode(",\n", $json);
    }

}
?>