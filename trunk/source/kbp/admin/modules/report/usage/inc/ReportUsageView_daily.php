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


class ReportUsageView_daily extends ReportUsageView
{
    
    var $date_format = '%d %b, %Y (%a)';
    var $group_field = 'date_day';
    var $force_index = 'date_day';
    
    
    
    function getHighlightStyle($date = false) {
        $str = '';
        if(CalendarUtil::isSunday(strtotime($date))) {
            // $str = 'background: #CCC; color: #F00;';
            $str = 'color: #F00;';
        }
        
        return $str; 
    }    
}
?>