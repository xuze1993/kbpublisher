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

$controller->loadClass('TrashEntry');
$controller->loadClass('TrashEntryModel');
$controller->loadClass('TrashAction');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

// to parse articltes/files
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html
$rp->setCurlyBracesValues('body');
$rp->setSkipKeys(array('schedule', 'schedule_on'));

$obj = new TrashEntry;

$manager =& $obj->setManager(new TrashEntryModel());
$priv->setCustomAction('empty', 'delete');
$priv->setCustomAction('restore', 'update');
$priv->setCustomAction('preview', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    // $manager->delete($rq->id);
    // $controller->go();

    break;
    

case 'empty': // ------------------------------

    require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';
    
    $types = $manager->getEntryTypes();
    
    $rows = $manager->getRecords();
    $ids = $manager->getValuesArray($rows, 'entry_id');
    
    $manager->truncate();
    
    UserActivityLog::add('article', 'delete', $ids);

    foreach($types as $entry_type) {
        $class_name = $manager->record_type[$entry_type];
        $action = TrashAction::factory($class_name);
        $action->deleteOnTrashEmpty();
    }
    
    $controller->go();

    break;
    
    
case 'restore': // ------------------------------
    
    $data = $manager->getById($rq->id);
    $class_name = $manager->record_type[$data['entry_type']];
    
    $action = TrashAction::factory($class_name);
    
    $entry_obj = unserialize($data['entry_obj']);
    
    if (isset($rp->submit)) {
        if (!empty($rp->category)) {
            $entry_obj->setCategory($rp->category);
        }
    }
    
    $rp->stripVarsValues($entry_obj->properties, false);
    
    $restoration_status = $action->restore($entry_obj);
    
    if (!$restoration_status) {
        $obj->set($data);
        $view = $controller->getView($obj, $manager, 'TrashEntryView_incomplete');
        
    } else {
        $manager->delete($rq->id);
        
        $controller->go();
    }

    break;
    
    
case 'preview': // ------------------------------
    
    $data = $manager->getById($rq->id);
    $class_name = $manager->record_type[$data['entry_type']];

    $action = TrashAction::factory($class_name);

    $view = $action->getPreview($data['entry_obj'], $controller);
    break;
    

default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'TrashEntryView_list');
}
?>