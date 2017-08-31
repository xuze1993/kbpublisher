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
       
$controller->loadClass('ReportEntry');
$controller->loadClass('ReportEntryModel');
$controller->loadClass('ReportUsageView_common', 'report/usage');
$controller->loadClass('ReportEntryView_list');
$controller->loadClass('ReportEntryView_list_entry');

// initialize objects
$rq = new RequestData($_GET);
$rp = new RequestData($_POST);


$obj = new ReportEntry;

$manager =& $obj->setManager(new ReportEntryModel());
$priv->setCustomAction('file', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);

$controller->setMoreParams('entry_id');

// include 'inc/populate.php';

switch ($controller->action) {
case 'file': // ------------------------------

    if (isset($rq->entry_id)) {
        $obj->set('entry_id', $rq->entry_id);
        
        $view = new ReportEntryView_list_entry;
        $view = ReportEntryView_list_entry::factory($view->getRangeToGenerate());
        
        $controller->loadClass($view);
        $view = new $view;
    
        $controller->loadClass('ReportEntryExport_entry');
        $export = new ReportEntryExport_entry;
        
    } else {
        $view = new ReportEntryView_list;
        
        if (!empty($rq->filter['t'])) {
            $view->report_type = $rq->filter['t'];
        }
        
        $controller->loadClass('ReportEntryExport');
        $export = new ReportEntryExport;
    }

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
                                'hr' => (isset($rp->header_row)),
                                'tr' => (isset($rp->total_row))
                                );

        
            $params['data'] = &$export->getCsv($obj, $manager, $view, $csv_params);
            $params['contenttype'] = 'application/csv';
            $ext = 'csv';
            
            break;
            
        case 'xls':                
            $xls_params = array('ft' => $view->conf['lang']['excel_delim'],
                                'oe' => '"',
                                'lt' => "\r\n",
                                'hr' => true,
                                'tr' => true
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

    if(!empty($rq->entry_id)) {
        $obj->set('entry_id', $rq->entry_id);
        
        $view = new ReportEntryView_list_entry;
        $view = ReportEntryView_list_entry::factory($view->getRangeToGenerate());
        $view = $controller->getView($obj, $manager, $view);
    
    } else {
        $view = $controller->getView($obj, $manager, 'ReportEntryView_list');
    }
    
}

// $conf['app_width'] = '95%';
?>