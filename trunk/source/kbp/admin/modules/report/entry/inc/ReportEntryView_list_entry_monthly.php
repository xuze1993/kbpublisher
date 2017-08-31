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


class ReportEntryView_list_entry_monthly extends ReportEntryView_list_entry
{
    
    var $date_format = '%B, %Y';//strftime
    var $group_field = 'date_month';
    var $force_index = 'date_month';
    
    
    function getFormatedDateReport($date) {
        $date = substr($date,0,4) . '-' . substr($date,4    ,2) . '-01';
        return $this->_getFormatedDate($date, $this->date_format);
    }
    
    
    function getFormatedDateTooltip($date) {
        $date_format = '%d %B';
        
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        
        $date_start = $year . '-' . $month . '-01';
        $date_start = $this->_getFormatedDate($date_start, $date_format);
        
        $date_end = date('d F', mktime(0, 0, 0, $month + 1, 0, $year));
        
        $tooltip = '%s - %s';
        $tooltip = sprintf($tooltip, $date_start, $date_end);

        return $tooltip;
    }        
    
    
    function getFilterLink($date) {
        
        $date = substr($date,0,4) . '-' . substr($date,4    ,2) . '-01';
        $f = $_GET['filter'];
        $m = $this->getFormatedDate($date, '%m');
        $y = $this->getFormatedDate($date, '%Y');
        
        $f['r'] = 'daily';
        $f['p'] = 'range_day';
        
        $date_str = '%s/%s/%s';
        $f['date_from'] = sprintf($date_str, $m, '01', $y);
        
        $num_days = TimeUtil::getMonthDays($m, $y);
        $f['date_to'] = sprintf('%s/%s/%s', $m, $num_days, $y);
        
        $more['filter'] = $f;
        $more['entry_id'] = $_GET['entry_id'];
        
        $link = $this->getLink('this', 'this', false, false, $more);

        return $link;
    }    
}
?>