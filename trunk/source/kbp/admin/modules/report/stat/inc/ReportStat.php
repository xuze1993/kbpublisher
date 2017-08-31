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

class ReportStat extends AppObj
{
    
    var $properties = array('report_id'        => NULL,
                            'date_year'         => '',
                            'date_month'    => 1,
                            'date_day'        => 1,
                            'int_value'        => '',
                            'text_value'    => ''
                            );
    
    
    //var $hidden = array('id');
}
?>