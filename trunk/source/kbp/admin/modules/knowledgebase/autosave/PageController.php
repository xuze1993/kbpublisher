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

$controller->loadClass('KBAutosave');
$controller->loadClass('KBAutosaveModel');
require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('entry_obj'));


if($controller->page == 'kb_autosave' || $controller->page == 'kb_draft_autosave') {

    $controller->loadClass('KBEntry', 'knowledgebase/entry');
    $controller->loadClass('KBEntryModel', 'knowledgebase/entry');
    $controller->loadClass('KBEntryView_detail', 'knowledgebase/entry');

    $entry_manager = new KBEntryModel;
    $detail_view = new KBEntryView_detail();
    
    if ($controller->page == 'kb_draft_autosave') {
        $detail_view->draft_view = true;
        $detail_view->page = 'kb_draft';
    }

} elseif($controller->page == 'news_autosave') {

    $controller->loadClass('NewsEntry', 'news');
    $controller->loadClass('NewsEntryModel', 'news');
    $controller->loadClass('NewsEntryView_detail', 'news');

    $entry_manager = new NewsEntryModel;
    $detail_view = new NewsEntryView_detail();

} else {
    // $controller->go();
}


$obj = new KBAutosave;
$manager = new KBAutosaveModel();
$manager->record_entry_type = ($controller->page == 'kb_draft_autosave') ? 7 : $entry_manager->entry_type;
// $manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------

    $manager->delete($rq->dkey);
    $controller->go();

    break;


case 'detail': // ------------------------------

    $entry_data = $manager->getByIdKey($rq->dkey);
    $entry_data['dkey'] = $rq->dkey;
    $entry_obj = unserialize($entry_data['entry_obj']);
    
    $entry_obj->restore($entry_manager);

    $view = $detail_view->execute($entry_obj, $entry_manager, $entry_data);

    break;


case 'preview': // ------------------------------

    $entry_data = $manager->getByIdKey($rq->dkey);
    $entry_obj = unserialize($entry_data['entry_obj']);

    if ($controller->page == 'kb_autosave' || $controller->page == 'kb_draft_autosave') { // articles
        $controller->loadClass('KBEntryView_preview', 'knowledgebase/entry');
        $view = new KBEntryView_preview;

    } else { // news
        $controller->loadClass('NewsEntryView_preview', 'news');
        $view = new NewsEntryView_preview;
    }

    $view = $view->execute($entry_obj, $manager);

    break;


default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'KBAutosaveView_list');
}
?>