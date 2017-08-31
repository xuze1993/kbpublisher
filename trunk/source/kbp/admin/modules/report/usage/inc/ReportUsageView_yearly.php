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


class ReportUsageView_yearly extends ReportUsageView
{
    
    var $date_format = '%Y';//strftime
    var $group_field = 'date_year';
    var $force_index = 'date_year';
    
    
    function getFormatedDateReport($date) {
        $date = $date . '-01-01';
        return $this->_getFormatedDate($date, $this->date_format);
    }    
    
    
    function getFilterLink($date) {
        
        $date = $date . '-01-01';
        $f = $_GET['filter'];
        $y = $this->getFormatedDate($date, '%Y');
        
        $f['r'] = 'monthly';
        $f['p'] = 'range_month';
        
        $f['from']['day'] = '01';
        $f['from']['week'] = 1;
        $f['from']['month'] = '01';
        $f['from']['year'] = $y;
        
        $f['to']['day'] = '31';
        $f['to']['week'] = 1;
        $f['to']['month'] = '12';
        $f['to']['year'] = $y ;        
        
        $more['filter'] = $f;
        $link = $this->getLink('this', 'this', false, false, $more);

        return $link;
    }    
}
?>