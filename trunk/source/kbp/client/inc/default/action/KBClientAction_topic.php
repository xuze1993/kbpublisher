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

require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';
require_once 'KBClientAction_forums.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class KBClientAction_topic extends KBClientAction_common
{

	function &execute($controller, $manager) {

        $controller->loadClass('forum');
		$action = $controller->msg_id;

        // redirect to correct  page where this message
        $message_id = $controller->getRequestVar('message_id');
        if ($message_id) { // got here from the search page
            $more = array('message_id' => $message_id);
            $page_num = $this->getMessagePageNum($message_id, $manager);
            if ($page_num > 1) {
                $more['bp'] = $page_num;
            }

            $controller->go('topic', false, $this->entry_id, false, $more);
        }

        if($action == 'post') {
            $view = $this->addPost($controller, $manager);

        } elseif($action == 'update') {
            $action = &$controller->getAction('forums');
            $action->setVars($controller);
            $action->setCategoryId($controller, $manager);
            $view = &$action->updateTopic($controller, $manager);

        } elseif($action == 'detail') {
            $view = &$this->manage($controller, $manager);

        } elseif($action == 'dfile') {
            $view = $this->dfile($manager);

        } else {
            if($manager->isUserViewed($this->entry_id) === false) {
                $manager->addView($this->entry_id);
                $manager->setUserViewed($this->entry_id);
            }

            UserActivityLog::add('forum_topic', 'view', $this->entry_id);

            $view = $controller->getView('forum_topic');
        }

        $view->msg['browse_msg'] = $view->msg['forum_switch_msg'];

        return $view;
	}


    function getMessagePageNum($message_id, $manager) {
        $num_per_page = $manager->setting['num_comments_per_page'];
        $position = $manager->getMessagePosition($this->entry_id, $message_id);
        return ceil($position / $num_per_page);
    }


    function &addPost($controller, $manager) {

        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumMessage.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';


        // section, status
        if(!$manager->isPostAllowed($this->entry_id, $this->category_id)) {
            $controller->go('forums', $this->category_id);
        }

        // not logged, private, banned
        if(!$manager->isPostAllowedByUser($this->entry_id, $this->category_id)) {
            $controller->go('forums', $this->category_id);
        }


        $obj = new ForumMessage;
        $manager->setting['file_allowed_extensions'] = ($manager->setting['file_allowed_extensions'])
                                                ? explode(',', $manager->setting['file_allowed_extensions'])
                                                : array();

        $manager3 = new ForumEntryModel;

        $view = &$controller->getView('forum_form');
        $view->form_title = $view->msg['reply_to_topic_msg'];

        if(isset($this->rp->submit)) {

            if (!empty($this->rp->message)) {
                if (KBClientAction_forums::checkSpam($manager, $view, $this->rp->message)) {
                    $controller->go('forums', false, $this->entry_id, 'message_spam_detected');
                }

                $this->rp->message = KBClientAction_forums::purify($this->rp->message);
            }

            $errors = $this->validate($obj, $this->rp->vars);
            //$flood = $manager->isFlood();

            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            //} elseif($flood) {
            //    $this->rp->stripVars(true);
            //    $view->setFormData($this->rp->vars);
            //    $view->msg_id = 'flood_comment';

            } else {

                $this->rp->stripVars();
                $obj->set($this->rp->vars);
                $obj->set('id', NULL);
                $obj->set('entry_id', $this->entry_id);

                $obj->set('message_index', RequestDataUtil::getIndexText($obj->get('message')));

                $message_id = $manager->saveMessage($obj);
                $manager->updateMessageFieldsForEntry($this->entry_id);

                // attachment
                if (!empty($this->rp->attachment_id)) {
                    $manager3->reassignAttachment($message_id, $this->rp->attachment_id);
                }

                if($manager->isSubscribtionAllowed('forum')) {
                    $entry_id = (int) $this->entry_id;
                    $user_id = (int) $manager->user_id;
                    $type = 4;

                    require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';
                    $emanager = new SubscriptionModel();

                    if(isset($this->rp->subscribe)) {
                        $emanager->saveSubscription($entry_id, $type, $user_id);

                        // set lastsent to NOW(), to not sent notifications to this user
                        $emanager->updateDateLastsent($entry_id, $type, $user_id);

                    } else {
                        $emanager->saveSubscription($entry_id, $type, $user_id);
                    }
                }


                $more = array('message_id' => $message_id);
                $page_num = $this->getMessagePageNum($message_id, $manager);
                if ($page_num > 1) {
                    $more['bp'] = $page_num;
                }

                $controller->go('success_go', false, $this->entry_id, 'message_posted', $more);

                // $anchor = '#c' . $message_id;
                // $url = $controller->getLink('topic', false, $entry_id, false, $more) . $anchor;
                // $controller->goUrl($url);
            }
        }

        return $view;
    }


    function &manage($controller, $manager) {

        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntry.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

        $obj = new ForumEntry;
        $emanager = new ForumEntryModel();

        $allowed = $manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);

        if (isset($this->rp->delete)) {
            if(in_array('delete', $allowed)) {
                $emanager->delete($this->entry_id);
                UserActivityLog::add('forum_topic', 'delete', $entry_id);
            }
            $controller->go('success_go', false, false, 'topic_deleted');
        }

        $data = &$emanager->getById($this->entry_id);
        $categories = $emanager->getCategoryById($this->entry_id);
        $obj->set($data);
        $obj->setCategory($categories);

        $entry_id = $this->entry_id;
        $msg_key = 'topic_updated';

        $is_sticky = $manager->getStickyDate($entry_id);
        $obj->setSticky($is_sticky);

        //$msg_key = '';
        if(in_array('update', $allowed)) {

            if (isset($this->rp->stick)) {
                if ($obj->getSticky()) {
                    $emanager->unfeatureTopic($entry_id);

                } else {
                    $emanager->featureTopic($entry_id);
                }

            } elseif (isset($this->rp->close)) {
                $obj->set('active', 2);
                $msg_key = 'topic_closed';
                $emanager->save($obj);

            } elseif (isset($this->rp->reopen)) {
                $obj->set('active', 1);
                $msg_key = 'topic_reopened';
                $emanager->save($obj);

            } elseif (isset($this->rp->move)) {
                $obj->setCategory(array($this->rp->category));
                foreach($emanager->getScheduleByEntryId($this->entry_id) as $num => $v) {
                    $obj->setSchedule($num, $v);
                }

                $emanager->save($obj);
            }
        }

        $controller->go('success_go', false, $entry_id, $msg_key);
    }


    function validate($obj, $values) {
        $obj->validate($values);
        return $obj->errors;
    }


    function &dfile($manager) {
        $id = (int) $_GET['id'];
        $manager->download($id);
        exit;
    }
}
?>