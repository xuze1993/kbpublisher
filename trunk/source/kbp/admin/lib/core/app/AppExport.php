<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class AppExport
{

    var $contenttype = array('xml' => 'application/xml',
                             'csv' => 'application/csv',
                             'xls' => 'application/xls');

    var $extension   = array('xml' => 'application/xml',
                             'csv' => 'application/csv',
                             'xls' => 'application/xls');

    // var $params = array('data'=>'', 'extension'=>'', 'contenttype'=>'');
    
    var $_contenttype;
    var $_extension;
    var $_data;


    function setData(&$data, $type){
        $this->_data = &$data;
        $this->_extension = $this->contenttype[$type];
        $this->_contenttype = $this->extension[$type];        
    }
    

    function sendHeader($type = 'xml', $encoding = 'UTF-8') {
        header("Content-type: text/xml; charset={$encoding}");
    } 

}
?>