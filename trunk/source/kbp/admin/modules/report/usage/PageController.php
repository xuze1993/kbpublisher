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

require_once 'HTTP/Download.php';

$controller->loadClass('ReportUsage');
$controller->loadClass('ReportUsageModel');
$controller->loadClass('ReportUsageView_common');
$controller->loadClass('ReportUsageView');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new ReportUsage;

$manager =& $obj->setManager( new ReportUsageModel() );
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {

case 'file': // ------------------------------
    
    $view = new ReportUsageView();
    $view = ReportUsageView::factory($view->getRangeToGenerate());
    
    $controller->loadClass($view);
    $view = new $view;

    $controller->loadClass('ReportUsageExport');
    $export = new ReportUsageExport;

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

    $filename_str = 'report_%s_%s_%s.%s';
    $filename = sprintf($filename_str, $view->start_day, $view->end_day, $view->getRangeToGenerate(), $ext);

    WebUtil::sendFile($params, $filename);
    exit;
              
    break;


case 'chart': // ------------------------------------

    $view = new ReportUsageView;           
    $view = ReportUsageView::factory($view->getRangeToGenerate());  
    
    require_once 'inc/' . $view . '.php';
    $v = new $view; 
    
    $params = $v->getFilterSql($manager);
    $manager->setSqlParams($params);
    $manager->sql_params_group = ($v->group_field) ? 'report_id,' . $v->group_field : 'report_id';
    
    $filter = $v->getReportToGenerate();
    $rows = &$v->stripVars($manager->getRecords($v->force_index, $v->week_start)); 
                       
    $v->execute($obj, $manager, array());
    
    $chart = new ReportUsageChart;   
    $chart->view = $v;
            
    switch ($_GET['chart_type']) {
        case 'line':
            $chart->getChartJson('line', $rows, $filter, $manager);
            break;
                
        case 'pie':
            $chart->getChartPieJson($rows, $filter, $manager); 
            break;
                   
        case 'bar':
            $chart->getChartJson('bar', $rows, $filter, $manager); 
            break;
        }
      
      break;
    

default: // ------------------------------------

    $view = new ReportUsageView();
    $view = ReportUsageView::factory($view->getRangeToGenerate());
    $view = $controller->getView($obj, $manager, $view);
}


// $conf['app_width'] = '95%';
?>