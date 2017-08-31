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


class ReportEntryView_list_entry_weekly extends ReportEntryView_list_entry
{
    
    var $group_field = 'date_week';
    var $force_index = 'date_week';


    function getFormatedDate($date, $format = false) {
        list($week_year, $week_number) = str_split($date, 4);
        
        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();
        
        $start_day = $cal->getFirstDayByWeek($week_year, $week_number);
        $end_day = $start_day + (6 * $cal->sek_in_day);
        
        return sprintf('%s #%s (%s - %s)', $week_year, $week_number, 
                            $this->_getFormatedDate($start_day), 
                            $this->_getFormatedDate($end_day));
    }
    
    
    function getFilterLink($date) {
        $f = $_GET['filter'];
        
        $f['r'] = 'daily';
        $f['p'] = 'range_day';
        
        list($week_year, $week_number) = str_split($date, 4);
        
        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();
        
        $start_day = $cal->getFirstDayByWeek($week_year, $week_number);
        $end_day = $start_day + (6 * $cal->sek_in_day);
        
        $date_str = 'm/d/Y';
        $f['date_from'] = date($date_str, $start_day);
        $f['date_to'] = date($date_str, $end_day);
        
        $more['filter'] = $f;
        $more['entry_id'] = $_GET['entry_id'];
        
        $link = $this->getLink('this', 'this', false, false, $more);

        return $link;
    }
    
}
?>