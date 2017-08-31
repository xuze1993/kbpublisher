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

$controller->loadClass('NewsEntry');
$controller->loadClass('NewsEntryView_common');
$controller->loadClass('NewsEntryModel');
$controller->loadClass('NewsEntryModelBulk');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html
$rp->setCurlyBracesValues('body');
$controller->rp = &$rp;

$obj = new NewsEntry;

$manager =& $obj->setManager( new NewsEntryModel() );
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {
case 'delete': // ------------------------------

    $manager->delete($rq->id);
    $controller->go();

    break;


case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'preview': // ------------------------------

    if(!empty($rq->id)) {

        $data = $manager->getById($rq->id);
        if($data) {
            $rp->stripVarsValues($data);
            $obj->set($data);
            $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
        }
    }

    $view = $controller->getView($obj, $manager, 'NewsEntryView_preview');
    break;


case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);

    break;


case 'autosave': // ------------------------------

    if(isset($rp->submit)) {

        $manager->deleteAutosave($rq->id);
        $more = array('id'=>$rq->id);
        if(!empty($rq->referer)) {
            $more['referer'] = $rq->referer;
        }

        $controller->goPage('this', 'this', false, 'update', $more);
    }

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->set('date_updated', $data['date_updated']);


    $controller->loadClass('KBEntryView_autosave', 'knowledgebase/entry');
    $view = new KBEntryView_autosave();
    $view = $view->execute($obj, $manager);

    break;
    
    
case 'tags': // ------------------------------

    $controller->loadClass('KBEntryView_tags', 'knowledgebase/entry');
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $manager);

    break;
    

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new NewsEntryModelBulk();
        $bulk_manager->setManager($manager);

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            break;

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

        case 'schedule': // ------------------------------
            if($rp->value['schedule_action'] == 'set') {
                $bulk_manager->setSchedule($rp->schedule_on, $rp->schedule, $ids);
            } else {
                $bulk_manager->removeSchedule($ids);
            }
            break;

        case 'tag': // ------------------------------
            $bulk_manager->setTags($rp->tag, $ids, $rp->value['tag_action']);
            break;

        case 'custom': // ------------------------------
            $bulk_manager->setCustomData($rp->value['custom'], $ids, $rp->value);
            break;

        }

        $controller->go();
    }

    $controller->goPage('main');

    break;


case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);

    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->setRoleRead($manager->getRoleReadById($rq->id));
    $obj->setRoleWrite($manager->getRoleWriteById($rq->id));

    foreach($manager->getScheduleByEntryId($rq->id) as $num => $v) {
        $obj->setSchedule($num, $v);
    }

    $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
    $obj->setTag($manager->tag_manager->getTagByEntryId($rq->id));

    $obj->setAuthor($manager->getUser($data['author_id']));
    $obj->setUpdater($manager->getUser($data['updater_id']));
    $obj->set('date_updated', $data['date_updated']);

    $view = $controller->getView($obj, $manager, 'NewsEntryView_detail');
    break;


case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit)) {

        $is_error = $obj->validate($rp->vars, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);
            
        } else {
            $rp->stripVars();
            $obj->populate($rp->vars, $manager);
            
            $date_posted = date('Ymd', strtotime($rp->vars['date_posted']));
            $obj->set('date_posted', $date_posted . date('His'));
            $obj->set('body_index', RequestDataUtil::getIndexText($rp->vars['body']));

            $entry_id = $manager->save($obj);
            
            $controller->setRequestVar('id', $entry_id);

            // unlock, remove autosave
            $actions = array('update');
            if(in_array($controller->action, $actions)) {
                // $manager->setEntryReleased($rq->id);
                $manager->deleteAutosave($rq->id);
            }

            // remove autosave
            $actions = array('insert', 'clone');
            if(in_array($controller->action, $actions)) {
                if(!empty($rp->id_key)) {
                    $manager->deleteAutosaveByKey($rp->id_key);
                }
            }

            // continue editing
            if(isset($rp->continue_update)) {
                $more = array('id' => $entry_id);
                $controller->goPage('this', 'this', false, 'update', $more);
            }

            // referer
            if(!empty($rq->referer)) {
                $controller->setCustomPageToReturn($rq->referer);
                if(strpos($rq->referer, 'client') !== false) {
                    $link = $controller->getClientLink(array('news', false, $entry_id));
                    $controller->setCustomPageToReturn($link, false);
                }
            }

            $controller->go();;
        }


    } elseif(in_array($controller->action, array('update', 'clone'))) {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        
        $obj->set($data, false, $controller->action);
        $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
        $obj->setTag($manager->tag_manager->getTagByEntryId($rq->id));        

        foreach($manager->getScheduleByEntryId($rq->id) as $num => $v) {
            $obj->setSchedule($num, $v);
        }

        $obj->setRoleRead($manager->getRoleReadById($rq->id));
        $obj->setRoleWrite($manager->getRoleWriteById($rq->id));
    }


    // if locked, if autosaved
    $actions = array('update');
    if(in_array($controller->action, $actions)  && !isset($rq->ajax)) {
        if(!isset($rp->submit)) {

/*
            // lock
            if($manager->isEntryLocked($rq->id)) {
                $more = array('id'=>$rq->id);
                if(!empty($rq->referer)) {
                    $more['referer'] = $rq->referer;
                }

                $controller->goPage('this', 'this', false, 'lock', $more);

            } else {
                $manager->setEntryLocked($rq->id);
            }
*/

            // if autosaved
            if(!isset($rq->dkey)) {
                if($manager->isAutosaved($rq->id, $data['date_updated'])) {
                    $more = array('id'=>$rq->id);
                    if(!empty($rq->referer)) {
                        $more['referer'] = $rq->referer;
                    }

                    $controller->goPage('this', 'this', false, 'autosave', $more);
                }
            }

        }
    }


    // open autosave in form, in update and in insert
    if(isset($rq->dkey) && !isset($rq->ajax)) {
        if(!isset($rp->submit)) {

            if($data_draft = &$manager->getAutosavedDataByKey($rq->dkey))  {

                $obj = unserialize($data_draft['entry_obj']);
                $rp->stripVarsValues($obj->properties);
                $obj->schedule = RequestDataUtil::stripVars($obj->schedule, array(), 'display');
                $obj->custom = RequestDataUtil::stripVars($obj->custom, array(), 'display');

                if($controller->action == 'update') {
                    $data = $manager->getById($rq->id);
                    $obj->set('date_updated', $data['date_updated']);
                } else {
                    $obj->set('id', NULL);
                }

                foreach($obj->getSchedule() as $num => $v) {
                    $v['date'] = strtotime($v['date']);
                    $obj->setSchedule($num, $v);
                }

                $tag = $obj->getTag();
                if(!empty($tag)) {
                    $ids = implode(',', $tag);
                    $obj->setTag($manager->tag_manager->getTagByIds($ids));
                }

            }
        }
    }


    $view = $controller->getView($obj, $manager, 'NewsEntryView_form');

    break;


default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'NewsEntryView_list');
}
?>