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

require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Util/TimeUtil.php';

$controller->loadClass('ForumFeaturedEntry');
$controller->loadClass('ForumFeaturedEntryModel');
$controller->loadClass('KBEntryModel', 'knowledgebase/entry');

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new ForumFeaturedEntry;

$manager =& $obj->setManager(new ForumFeaturedEntryModel());
$priv->setCustomAction('delete_category', 'delete');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new ForumFeaturedEntryModelBulk;
        $bulk_manager->setManager($manager);

        switch ($action) {
        case 'remove': // ------------------------------
            $manager->delete($ids);
            break;

        case 'sort_order': // ------------------------------
            $bulk_manager->setSortOrder($rp->value['sort_order'], $ids);
            break;
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    
    
case 'update': // ------------------------------

    if(isset($rp->submit)) {
        $rp->stripVars();
        $obj->set($rp->vars);
        
        if ($rp->vars['date_to']) {
            $date_to = date('Ymd', strtotime($rp->vars['date_to']));
            $obj->set('date_to', $date_to);
        }
        
        $manager->save($obj);
        
        $controller->go();
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data);
        $obj->setTitle($data['title']);
    }
    
    $view = $controller->getView($obj, $manager, 'ForumFeaturedEntryView_form');
    break;
        

default: // ------------------------------------
    
    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id, $rp->lowest_sort_order);
    }
    
    $view = $controller->getView($obj, $manager, 'ForumFeaturedEntryView_list');
}
?>