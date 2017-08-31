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

class ReportUsageView_common extends AppView
{
    
    function getPeriodToGenerate() {
        $p = $this->default_period;
        if(!empty($_GET['filter']['p'])) {
            $p = htmlspecialchars($_GET['filter']['p']);
        }
        
        return $p;
    }
    
    
    function getRangeToGenerate() {
        $p = $this->default_range;
        if(!empty($_GET['filter']['r'])) {
            $p = htmlspecialchars($_GET['filter']['r']);
        }
        
        return $p;
    }
    
    
    // this olso used in .../report/entry/inc/ReportEntryView_list_entry.php
    function getReportPeriodSql($manager) {
        
        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();
        
        $range = $this->getRangeToGenerate();
        $period = $this->getPeriodToGenerate();
        
        $start_date_ts = $manager->getEarliestReportDate();
        $data = TimeUtil::getPeriodData($period, array(), $this->week_start, $start_date_ts);
        
        $start_day = $this->start_day = $data['start_day'];
        $end_day = $this->end_day = $data['end_day'];
        
        $sql = '';
        $str['year'] = "AND date_year BETWEEN '%s' AND '%s'";
        $str['month'] = "AND date_month BETWEEN '%s' AND '%s'";
        $str['week'] = "AND date_week BETWEEN '%s' AND '%s'";
        $str['day'] = "AND date_day BETWEEN '%s' AND '%s'";
        
        $str_date = '%s-%s-%s';
    
        switch ($period) {
        case 'this_year': // ------------------------------
            
            if ($range == 'monthly') {
                $start_day = $cal->year . '01';
                $end_day = $cal->year . '12';
                
                $this->date_range = range($start_day, $end_day);
                $sql = sprintf($str['month'], $start_day, $end_day);
                    
            } elseif ($range == 'weekly') {
                $start_week = $cal->year . '01';
                $end_week = $cal->year . '53';
                
                $this->date_range = range($start_week, $end_week);
                $sql = sprintf($str['week'], $start_week, $end_week);
            }
            
            break;

        case 'previous_year': // ------------------------------
            
            if ($range == 'monthly') {
                $start_day = $cal->prev_year . '01';
                $end_day = $cal->prev_year . '12';
                
                $this->date_range = range($start_day, $end_day);
                $sql = sprintf($str['month'], $start_day, $end_day);
                    
            } elseif ($range == 'weekly') {
                $start_week = $cal->prev_year . '01';
                $end_week = $cal->prev_year . '53';
                
                $this->date_range = range($start_week, $end_week);
                $sql = sprintf($str['week'], $start_week, $end_week);
            }
            break;

        case 'this_month': // ------------------------------
            $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
            $sql = sprintf($str['day'], $start_day, $end_day);
            break;

        case 'previous_month': // ------------------------------            
            $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
            $sql = sprintf($str['day'], $start_day, $end_day);
            break;
        
        case 'this_week': // ------------------------------            
            $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
            $this->date_range = array_unique($this->date_range);
            
            $sql = sprintf($str['day'], $start_day, $end_day);
            break;

        case 'previous_week': // ------------------------------
            $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
            $this->date_range = array_unique($this->date_range);
            
            $sql = sprintf($str['day'], $start_day, $end_day);
            break;
                    
        case 'range_year': // ------------------------------
            $values = $_GET['filter'];
            $start_day = $values['from']['year'];
            $end_day = $values['to']['year'];
            $this->start_day = sprintf($str_date, $values['from']['year'], '01', '01');
            $this->end_day = sprintf($str_date, $values['to']['year'], '12', '31');            
            
            $this->date_range = range($start_day, $end_day);
            $sql = sprintf($str['year'], $start_day, $end_day);
            break;                

        case 'range_month': // ------------------------------
            $v = $_GET['filter']['from'];
            $start_day = $v['year'].$v['month'];
            $start_year = $v['year'];
            $this->start_day = sprintf($str_date, $v['year'], $v['month'], '01');
            
            $v = $_GET['filter']['to'];
            $end_day = $v['year'].$v['month'];            
            $end_year = $v['year'];
            
            $num_days = TimeUtil::getMonthDays($v['month'], $v['year']);
            $this->end_day = sprintf($str_date, $v['year'], $v['month'], $num_days);

            $year_range = range($start_year, $end_year);
            foreach($year_range as $year) {
                for($i=1; $i<=12; $i++) {
                    $v = $year . sprintf("%'02s",   $i);
                    if($v < $start_day || $v > $end_day) {
                        continue;
                    }
                    
                    $this->date_range[] = $v;
                }
            }

            $sql = sprintf($str['month'], $start_day, $end_day);
            break;                
            
        case 'range_day': // ------------------------------
        
            $v = strtotime(urldecode($_GET['filter']['date_from']));
            $this->start_day = date('Y-m-d', $v);
            
            $v = strtotime(urldecode($_GET['filter']['date_to']));
            $this->end_day = date('Y-m-d', $v);
            
            $this->date_range = TimeUtil::getDateRange($this->start_day, $this->end_day);
            $sql = sprintf($str['day'], $this->start_day, $this->end_day);
            break;
            
        case 'range_week': // ------------------------------
        
            $v = strtotime(urldecode($_GET['filter']['week_date_from']));
            $start_week = $cal->getWeekNumber($v);
            
            $v = strtotime(urldecode($_GET['filter']['week_date_to']));
            $end_week = $cal->getWeekNumber($v);
            
            $this->date_range = range($start_week, $end_week);
            $sql = sprintf($str['week'], $start_week, $end_week);
            break;                
            
        // all period, only in year range
        case 'all_period': // ------------------------------
            $this->start_day = '2009-01-01';
            $this->end_day = date('Y-12-31');
            $this->date_range = range(2009, date('Y'));
            break;
        }
        
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }

}