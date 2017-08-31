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

$controller->loadClass('ForumCategory');
$controller->loadClass('ForumCategoryAction');
$controller->loadClass('ForumCategoryModel');


// initialize objects
$rq = new RequestData($_GET, array('id', 'parent_id'));
$rp = new RequestData($_POST);

$obj = new ForumCategory;

$manager = &$obj->setManager(new ForumCategoryModel());

$action = new ForumCategoryAction($rq, $rp);

$record_id = ($controller->action == 'insert' && !empty($rq->parent_id)) ? $rq->parent_id : @$rq->id;
$manager->checkPriv($priv, $controller->action, $record_id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {
case 'delete': // ------------------------------

    $msg = $manager->delete($rq->id, $rq->parent_id);

    $success = ($msg == 'success') ? false : true;
    $controller->go($msg, $success);

    break;


case 'status': // ------------------------------

    $manager->statusCategory($rq->status, $rq->id);
    $controller->go();

    break;

case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);

    break;


case 'category': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'ForumCategoryView_category');
    break;


case 'clone_tree': // ------------------------------
    $action->cloneTree($obj, $manager, $controller);
    
    break;
    

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new ForumCategoryModelBulk();
        $bulk_manager->setManager($manager);
        $bulk_manager->apply_child = (isset($rp->value['apply_child']));

        switch ($action) {
        //case 'delete': // ------------------------------
        //    $manager->delete($ids, true); // false to skip sort updating  ???
        //    break;

        case 'status': // ------------------------------
            $bulk_manager->status($rp->value['status'], $ids);
            break;

        case 'private': // ------------------------------
            $pr = (isset($rp->value['private'])) ? $rp->value['private'] : 0;
            $bulk_manager->setPrivate($rp->value, $pr, $ids);
            break;

        case 'public': // ------------------------------
            $bulk_manager->setPublic($ids);
            break;

        case 'forum_admin': // ------------------------------
            $bulk_manager->setAdmin($rp->value['admin_user'], $ids);
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
case 'insert': // ------------------------------

    if(isset($rp->submit)) {

        $is_error =$obj->validate($rp->vars);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);

            if(!empty($rp->vars['role_read'])) {
                $obj->setRoleRead($rp->vars['role_read']);
            }

            if(!empty($rp->vars['role_write'])) {
                $obj->setRoleWrite($rp->vars['role_write']);
            }

            if(!empty($rp->vars['admin_user'])) {
                $ids = implode(',', $rp->vars['admin_user']);
                $obj->setAdminUser($manager->getAdminUserByIds($ids));
            }

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setAdminUser(@$rp->vars['admin_user']);
            $obj->setRoleRead(@$rp->vars['role_read']);
            $obj->setRoleWrite(@$rp->vars['role_write']);

            $id = $manager->save($obj, $controller->action);

            if(!empty($rq->referer)) {
                if(strpos($rq->referer, 'client') !== false) {
                    $link = $controller->getClientLink(array('index', $id));
                    $controller->setCustomPageToReturn($link, false);
                    
                } else {
                    $referer = WebUtil::unserialize_url($rq->referer);
                    $referer .= '&amp;category_id=' . $id;
                    $controller->setCustomPageToReturn($referer, false);
                 }
            }

            $controller->go();
        }

    } elseif($controller->action == 'update') {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setAdminUser($manager->getAdminUserById($rq->id));
        $obj->setRoleRead($manager->getRoleReadById($rq->id));
        $obj->setRoleWrite($manager->getRoleWriteById($rq->id));

    // child clicked in list records or filter applied
    } else {

        $parent_id = 0;
        if(!empty($rq->parent_id)) {
            $parent_id = (int) $rq->parent_id;
        } elseif(!empty($rq->filter['c'])) {
            $parent_id = (int) $rq->filter['c'];
        }

        if($parent_id) {
            $data = $manager->getById($parent_id);
            $obj->set('parent_id', $parent_id);
            $obj->set('private', $data['private']);
            $obj->set('active', $data['active']);
            $obj->setAdminUser($manager->getAdminUserById($obj->get('parent_id')));
            $obj->setRoleRead($manager->getRoleReadById($obj->get('parent_id')));
            $obj->setRoleWrite($manager->getRoleWriteById($obj->get('parent_id')));
        }
        
        if (!empty($rq->category_name)) {
            $action->setCategoryParams($obj, $manager);
        }
    }
    
    $view = $controller->getView($obj, $manager, 'ForumCategoryView_form');

    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }

    $view = $controller->getView($obj, $manager, 'ForumCategoryView_list');
}
?>