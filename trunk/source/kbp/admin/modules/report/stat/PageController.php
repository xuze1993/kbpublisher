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

$controller->loadClass('ReportStat');
$controller->loadClass('ReportStatModel');
$controller->loadClass('ReportStatView');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new ReportStat;


$classes = array('rs_summary'     => 'summary',
                 'rs_article'     => 'article',
                 'rs_file'        => 'file',
                 'rs_news'        => 'news',
                 'rs_feedback'    => 'feedback',
                 'rs_user'        => 'user',
                 'rs_search'      => 'search',
                 'rs_forum'       => 'forum'                 
                );

$pr = 'rs_article';
if(isset($classes[$controller->sub_page])) {
    $pr = $classes[$controller->sub_page];
}


$manager = ReportStatModel::factory($pr);
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'detail':

    $view = $controller->getView($obj, $manager, 'ReportStatView_list', $rq->mode);

    break;


case 'file':
    
    $view = ReportStatView::factory($pr);
    $view->addMsg('report_msg.ini');
                            
    $type = ($rq->type == 'xml' || $rq->type == 'csv') ? $rq->type : null;
    $limit = (isset($rq->limit)) ? $rq->limit : 10;
    $view->executeExport($manager, $type, $rq->mode, $limit);
                                                   
    break;
    
    
default:

    $view = ReportStatView::factory($pr);
    $view->addMsg('report_msg.ini');

    $view = $view->execute($obj, $manager);
}
?>