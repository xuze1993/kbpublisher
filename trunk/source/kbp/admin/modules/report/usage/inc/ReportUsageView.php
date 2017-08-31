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


class ReportUsageView extends ReportUsageView_common
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    var $date_format = false; // default will be used

    var $default_report = array(1,2);
    var $default_range = 'daily';
    var $default_period = 'this_week';
    
    var $start_day;
    var $end_day;
    
    var $chart_max_period = 31;
    var $chart_type = 'canvas'; // flash or canvas
    
        
    static function factory($view) {
        return 'ReportUsageView_' . $view;
    }
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('report_msg.ini');
        $this->addMsg('random_msg.ini');
        
        // fix to add missed words from en
        $types_msg = AppMsg::getMsg('report_msg.ini', false, 'report_type');
        $this->msg['report_type'] = $types_msg;
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);   
        $tpl->tplAssign('filter_block_tmpl', $this->getFilter($manager));                         
                                                
        $types = array('xml', 'csv', 'xls');
        foreach ($types as $type) {
            $export_links[$type] = $this->getActionLink('file', false, array('type' => $type));  
        }
        

        $tpl->tplAssign('tab_block_tmpl', CommonExportView::getExportFormBlock($obj, $manager, $export_links));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->registerFunction(array('setDefaultChartType', 'CommonReportChart', 'ajaxSetDefaultChartType'));
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        $filter = $this->getReportToGenerate();
        
        // header generate
        //$bp = &$this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        //$tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        //$sort = &$this->getSort();
        //$manager->setSqlParamsOrder($sort->getSql());
        
        // group
        $manager->sql_params_group = ($this->group_field) ? 'report_id,' . $this->group_field : 'report_id';
        
        // get records
        $rows = $this->stripVars($manager->getRecords($this->force_index, $this->week_start));
       // echo '<pre>', print_r($rows, 1), '</pre>';
        
        $rows_total = $manager->getRecordsTotal();
        //echo '<pre>', print_r($rows_total, 1), '</pre>';
        
        // chart
        if (count($this->date_range) > $this->chart_max_period) {
            $tpl->tplSetNeeded('/chart_not_available');
                        
        } else {
            $this->chart_titles = $manager->getReportTypeSelectRange($this->msg);            
            $tpl->tplAssign('report_chart_block', CommonReportChart::getChartBlock($this->chart_type, $rows, $filter, $this));
        }

        
        $a = array();
        $a['td_width'] = ceil(75/count($filter)) . '%';
        foreach ($filter as $k => $v) {
            $a['type_title'] = $this->msg['report_type'][$manager->report_type[$v]];
            $a['total_value'] = (isset($rows_total[$v])) ? number_format($rows_total[$v], 0, '', ' ') : '--';
            
            $tpl->tplParse($a, 'td_title');
            $tpl->tplParse($a, 'td_total');
        }
        
        
        foreach($this->date_range as $date) {
            $row['date'] = $date;
            
            foreach($filter as $k => $v) {
                $a1['value'] = (isset($rows[$date][$v])) ? number_format($rows[$date][$v], 0, '', ' ') : '--';
                $tpl->tplParse($a1, 'row/td_row');
            }
        
            $row['formatted_date'] = $this->getFormatedDateReport($date);
            if($date_link = $this->getFilterLink($date)) {
                $str = '<a href="%s">%s</a>';
                $row['formatted_date'] = sprintf($str, $date_link, $row['formatted_date']);
            }
            
            $tpl->tplAssign($this->getViewListVarsCustom(1, 1, $date));
        
            $tpl->tplSetNested('row/td_row');
            $tpl->tplParse($row, 'row');
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
        $p = $this->default_report;
        if(!empty($_GET['filter']['t']) && is_array($_GET['filter']['t'])) {
            $p = array();
            foreach($_GET['filter']['t'] as $v) {
                $p[] = (int) $v;
            }
        }
        
        return $p;
    }
        
    
    function getFilterLink($date) {
        return false;
    }    
    
    
    function getFormatedDateReport($date) {
        return $this->getFormatedDate($date, $this->date_format);
    }    
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        $types = $this->getReportToGenerate();
        //$period = $this->
        

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        // type
        foreach($manager->report_type as $k => $v) {
            $a['value'] = $k;
            $a['text'] = $this->msg['report_type'][$v];
            $a['checked'] = $this->getChecked(in_array($k, $types));
            $tpl->tplParse($a, 'row');
        }
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // range
        $select->setRange($manager->getRangeSelectRange($this->msg['report_range']));
        $tpl->tplAssign('range_select', $select->select($values['r']));
        
        $tpl->tplAssign('myOptionsJson', $this->getPeriodSelectJson());
        
        
        // custom period
        $picker = new DatePicker();
        $picker->setFormName('search_form');        // set form name
        $picker->setFormMethod($_GET);                // set form method
        
        $start_date_ts = $manager->getEarliestReportDate();
        
        $picker->setYearRange(date('Y', $start_date_ts), date('Y') + 1);
        $picker->month_format = '%b';
        //$picker->css_class = 'colorInput';
        
        //$date = $picker->js();                    // js function
        //$tpl->tplAssign('js_date_select', $date);
        
        $selected = (isset($values['from'])) ? $picker->unixDate2($values['from']) : mktime(0, 0, 0, 1, 1, date('Y', $start_date_ts));
        $picker->setDate($selected);
        $picker->setSelectName('filter[from]');
        
        //$date['day_from_select'] = $picker->day();
        $date['week_from_select'] = $picker->week();
        $date['month_from_select'] = $picker->month();
        $date['year_from_select'] = $picker->year();
        
        
        if (!empty($values['date_from'])) {
            $date_from = strtotime($values['date_from']);
            $date_to = strtotime($values['date_to']);
            
        } else {
            $date_from = time();
            $date_to = $date_from;
        }
        
        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));
        
        
        //echo '<pre>', print_r($values['to'], 1), '</pre>';
        $selected = (isset($values['to'])) ? $picker->unixDate2($values['to']) : mktime(0, 0, 0, 12, 31, date('Y', $start_date_ts));
        $picker->setDate($selected);
        $picker->setSelectName('filter[to]');    
        
        //$date['day_to_select'] = $picker->day();
        $date['week_to_select'] = $picker->week();
        $date['month_to_select'] = $picker->month();
        $date['year_to_select'] = $picker->year();
        
        $tpl->tplAssign($date);
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // reports
        $v = implode(',', $this->getReportToGenerate());
        $arr[] = "AND report_id IN ($v)";
            
        // period
        $arr[] = $this->getReportPeriodSql($manager);
        
        
        $arr = implode(" \n", $arr);
        return $arr;
    }


    function getPeriodSelectJson() {

        $filter = array();
        $filter['yearly']  = array('all_period', 'range_year');
        $filter['monthly'] = array('this_year', 'previous_year', 'range_month');
        $filter['weekly']  = array('this_month', 'previous_month', 'this_year', 'previous_year', 'range_month');
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