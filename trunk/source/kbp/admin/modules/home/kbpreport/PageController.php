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

$controller->loadClass('KBPReport');
$controller->loadClass('KBPReportModel');

$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new KBPReport;
$manager =& $obj->setManager(new KBPReportModel());

// settings
$sm = new SettingModel();
$manager->setting = $sm->getSettings('1, 2, 134, 140, 141');

if(isset($rp->submit)) {
    $manager->checkPriv($priv, 'update');
    
    $manager->runTest();
    $controller->go();
    
} else {
    $manager->checkPriv($priv, 'select');
    
    $data = $manager->getReport();
    $view = $controller->getView($obj, $manager, 'KBPReportView_default', $data); 
}

?>