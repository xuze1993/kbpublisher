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


class ReportUsageView_weekly extends ReportUsageView
{
    
    //var $date_format = '%u - %d %b, %Y (%a)';// '(%V, %G)'; //'%d %b, %Y (%a)'; //'%B, %Y';//strftime
    var $date_format = '%d %b, %Y (%a)'; //'%B, %Y';//strftime
    var $group_field = 'date_week';
    var $force_index = 'date_week';
    var $start_day;


    function getFormatedDate($date, $format) {
        
        if(!$this->start_day) {
            $cal = new CalendarUtil();
            $cal->week_start = $this->week_start;
            $cal->setCalendar(strtotime($date));
            $d = $cal->setWeek();
            $this->start_day = $d['start_day'];
            $end_day = $d['end_day'];
                    
        } else {
            $this->start_day = $this->start_day + 86400*7;
            $end_day = $this->start_day + 86400*6;
        }
        
        //$format = 
        $str = '<b>%s</b> - %s - %s';
        return sprintf($str, strftime('%V', $this->start_day), 
                                       strftime($this->date_format, $this->start_day), 
                                       strftime($this->date_format, $end_day));
        
    }

}
?>