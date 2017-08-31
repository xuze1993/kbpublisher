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
       
$controller->loadClass('ReportEntryUser');
$controller->loadClass('ReportEntryUserModel');
$controller->loadClass('ReportEntryUserView_list');


// initialize objects
$rq = new RequestData($_GET);
$rp = new RequestData($_POST);


$obj = new ReportEntryUser;

$manager =& $obj->setManager(new ReportEntryUserModel());
$priv->setCustomAction('file', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);

$controller->setMoreParams('entry_id');

// include 'inc/populate.php';

switch ($controller->action) {
case 'file': // ------------------------------

    $view = new ReportEntryUserView_list;
    
    if (!empty($rq->filter['t'])) {
        $view->report_type = $rq->filter['t'];
    }
    
    $controller->loadClass('ReportEntryUserExport');
    $export = new ReportEntryUserExport;

    switch($rq->type) {
        case 'xml':
            $params['data'] = &$export->getXml($obj, $manager, $view);
            $params['contenttype'] = 'application/xml';
            $ext = 'xml';

            break;
            
        case 'csv':                
            $csv_params = array('ft' => $rp->fields_terminated,
                                'oe' => $rp->optionally_enclosed,
                                'lt' => $rp->lines_terminated,
                                'hr' => (isset($rp->header_row))
                                );

        
            $params['data'] = &$export->getCsv($obj, $manager, $view, $csv_params);
            $params['contenttype'] = 'application/csv';
            $ext = 'csv';
            
            break;
            
        case 'xls':                
            $xls_params = array('ft' => $view->conf['lang']['excel_delim'],
                                'oe' => '"',
                                'lt' => "\r\n",
                                'hr' => true
                                );
    
            $params['data'] = &$export->getCsv($obj, $manager, $view, $xls_params);
            $params['contenttype'] = 'application/xls';
            $ext = 'xls';

            break;
    }

    $filename_str = 'report_%s_%s.%s';
    $filename = sprintf($filename_str, $view->start_day, $view->end_day, $ext);

    WebUtil::sendFile($params, $filename);
    exit;
              
    break;
    
default: // ------------------------------------
    $view = $controller->getView($obj, $manager, 'ReportEntryUserView_list');
    
}

// $conf['app_width'] = '95%';
?>