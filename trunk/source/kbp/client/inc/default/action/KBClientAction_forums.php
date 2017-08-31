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

require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class KBClientAction_forums extends KBClientAction_common
{

	function &execute($controller, $manager) {

		$controller->loadClass('forum');
		$action = $controller->msg_id;

        if($action == 'post') {
            $view = $this->addTopic($controller, $manager, 'post');

		} elseif ($this->category_id) {
			$view = $controller->getView('forum_topic_list');

		} elseif($action == 'file_upload') {
            $this->uploadFile($controller, $manager);

        } else{
            $view = $controller->getView('forum_index');
        }

        $emanager = new KBEntryModel();
        $view->emanager = &$emanager;
        $view->msg['browse_msg'] = $view->msg['forum_switch_msg'];

        return $view;
	}


    function addTopic($controller, $manager) {

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntry.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumMessage.php';
        require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';

        // section, status
        if(!$manager->isTopicAddingAllowed($this->category_id)) {
            $controller->go('forums', $this->category_id);
        }

        // not logged, private, banned
        if(!$manager->isTopicAddingAllowedByUser($this->category_id)) {
            $controller->go('forums', $this->category_id);
        }

        $eobj = new ForumEntry;
        $emanager = new ForumEntryModel;

        return $this->_saveTopic($controller, $manager, $eobj, $emanager, 'insert');
    }



    function updateTopic($controller, $manager) {

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntry.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumMessage.php';
        require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';

        $eobj = new ForumEntry;
        $emanager = new ForumEntryModel;

        // validate
        $data = $emanager->getById($this->entry_id);
        $admin_allowed = $manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
        $user_allowed = $manager->getPostActionsAllowedUser($manager->user_id, $data['date_posted'], $admin_allowed, 900);// extra 15 min
        $allowed = array_merge($admin_allowed, $user_allowed);

        if(!in_array('update', $allowed)) {
            $controller->go('forums', $this->category_id);
        }


        $data = &$emanager->getById($this->entry_id);
        $eobj->set($data);

        $sticky_date = $manager->getStickyDate($this->entry_id);
        if ($sticky_date) {
            $eobj->setSticky(true);
            if (strtotime($sticky_date)) {
                $eobj->setStickyDate($sticky_date);
            }
        }

        $tags = $manager->getTagByEntryId($this->entry_id);
        $eobj->setTag($tags);

        $eobj->setAttachment($manager->getMessageAttachment($eobj->get('first_post_id')));

        $first_post = $manager->getMessageById($eobj->get('first_post_id'));
        $eobj->setFirstMessage($first_post['message']);


        return $this->_saveTopic($controller, $manager, $eobj, $emanager, 'update');
    }



    function _saveTopic($controller, $manager, $eobj, $emanager, $action) {

        $manager->setting['file_allowed_extensions'] = ($manager->setting['file_allowed_extensions'])
                                                ? explode(',', $manager->setting['file_allowed_extensions'])
                                                : array();


        $view = &$controller->getView('forum_topic_form');
        $view->entry_obj = $eobj;

        if(isset($this->rp->submit)) {

            $eobj->setCategory(array($this->rp->category));
            $is_error = $eobj->validate($this->rp->vars, true);

            if($is_error) {
                $view->setErrors($eobj->errors);

                $this->rp->stripVars(true);

                $eobj->set($this->rp->vars);
                $eobj->setFirstMessage($this->rp->vars['message']);

                if(!empty($this->rp->vars['attachment_id'])) {
                    $attachment_ids = implode(',', $this->rp->vars['attachment_id']);
                    $attachments = $manager->getAttachmentByIds($attachment_ids);
                    $eobj->setAttachment($attachments);
                }

                if(!empty($this->rp->vars['tag'])) {
                    $tag_ids = implode(',', $this->rp->vars['tag']);
                    $tags = $emanager->tag_manager->getTagByIds($tag_ids);
                    $eobj->setTag($tags);
                }

                if(!empty($this->rp->vars['sticky'])) {
                    $eobj->setSticky(true);

                    if (!empty($this->rp->vars['sticky_to'])) {
                        $eobj->setStickyDate($this->rp->vars['sticky_to']);
                    }
                }

            } else {

                if ($this->checkSpam($manager, $view, $this->rp->message)) {
                    $controller->go('forums', $this->category_id, false, 'message_spam_detected');
                }

                $this->rp->message = KBClientAction_forums::purify($this->rp->message);

                $this->rp->stripVars();
                $eobj->set($this->rp->vars);
                $title = $eobj->get('title');

                $meta_keywords = '';
                if(!empty($this->rp->tag)) {
                    $meta_keywords = $emanager->tag_manager->getKeywordsStringByIds(implode(',', $this->rp->tag));
                    $meta_keywords = RequestDataUtil::addslashes($meta_keywords);

                    $eobj->set('meta_keywords', $meta_keywords);
                }

                if(!empty($this->rp->vars['sticky'])) {
                    $eobj->setSticky(true);

                    if (!empty($this->rp->vars['sticky_to'])) {
                        $eobj->setStickyDate($this->rp->vars['sticky_to']);
                    }
                }

                $entry_id = $emanager->save($eobj);

                $emanager->tag_manager->deleteTagToEntry($entry_id, $manager->entry_type);
                if(!empty($this->rp->tag)) {
                    $emanager->tag_manager->saveTagToEntry($this->rp->tag, $entry_id, $manager->entry_type);
                }

                // save the first message
                $message_obj = new ForumMessage;

                $message_obj->set($this->rp->vars);
                $message_obj->set('entry_id', $entry_id);

                if ($action == 'update') {
                    $message_obj->set('id', $eobj->get('first_post_id'));
                }

                $message_index = sprintf('%s %s %s', $title, $meta_keywords, RequestDataUtil::getIndexText($message_obj->get('message')));
                $message_obj->set('message_index', $message_index);

                $message_id = $manager->saveMessage($message_obj);
                if (!$message_id) {
                    $message_id = $eobj->get('first_post_id');
                }

                if ($action == 'insert') {
                    $manager->updateMessageFieldsForEntry($entry_id, 'add', $message_id);
                }

                // attachments
                if ($action == 'update') {
                    $old_attachments = $manager->getMessageAttachment($message_id);
                    if (!empty($old_attachments)) {
                        $old_attachment_ids = $manager->getValuesArray($old_attachments, 'id');
                        $emanager->reassignAttachment(0, $old_attachment_ids);
                    }
                }

                if (!empty($this->rp->attachment_id)) {
                    $emanager->reassignAttachment($message_id, $this->rp->attachment_id);
                }

                // subscription
                if ($action == 'insert' && $manager->isSubscribtionAllowed('forum')) {
                    $user_id = (int) $manager->user_id;
                    $type =  4;

                    $emanager = new SubscriptionModel();

                    if(isset($this->rp->subscribe)) {
                        $emanager->saveSubscription($entry_id, $type, $user_id);

                        // set lastsent to NOW(), to not sent notifications to this user
                        $emanager->updateDateLastsent($entry_id, $type, $user_id);
                    } else {
                        $emanager->deleteSubscription($entry_id, $type, $user_id);
                    }
                }

                $log_action = ($action == 'insert') ? 'create' : 'update';
                UserActivityLog::add('forum_topic', $log_action, $entry_id);

                $action_key = ($action == 'insert') ? 'topic_posted' : 'topic_updated';
                $controller->go('success_go', false, $entry_id, $action_key);
            }
        }

        return $view;
    }


    static function checkSpam($manager, $view, $message) {

        require_once 'eleontev/Util/Akismet.class.php';
        require_once APP_MODULE_DIR . 'user/ban/inc/UserBan.php';
        require_once APP_MODULE_DIR . 'user/ban/inc/UserBanModel.php';

        $akismet_key = $manager->getSetting('akismet_key');
        if(strtolower($akismet_key) == 'off') {
            return false;
        }

        $akismet = new Akismet('', $akismet_key);
        $akismet->setCommentContent($message);

        if($akismet->isCommentSpam()) {
            if (!isset($_SESSION['spam_'])) {
                $_SESSION['spam_'] = 0;
            }

            $_SESSION['spam_'] ++;

            // auto ban
            $num = $manager->getSetting('num_auto_ban');
            if ($num != 'off' && $_SESSION['spam_'] > $num) {
                $eobj = new UserBan;
                $emanager = new UserBanModel();

                $eobj->set('ban_type', 2); // forum
                $eobj->set('ban_rule', 1); // user id
                $eobj->set('ban_value', $manager->user_id);
                $eobj->set('date_start', date('Y-m-d H:i:s'));
                $eobj->set('date_end', null);
                $eobj->set('admin_reason', $view->msg['ban_auto_admin_reason']);
                $eobj->set('user_reason', $view->msg['ban_auto_user_reason']);

                $emanager->save($eobj);

                //$_SESSION['ban_'][1] = $view->msg['ban_auto_user_reason'];
            }
            return true;
        }

        return false;
    }


    // function remove_empty_tags_recursive ($str, $repto = NULL) {
    //
    //     //** Return if string not given or empty.
    //     if (!is_string ($str) || trim ($str) == '') {
    //         return $str;
    //     }
    //
    //     //** Recursive empty HTML tags.
    //     return preg_replace (
    //
    //         //** Pattern written by Junaid Atari.
    //         '/<([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU',
    //
    //         //** Replace with nothing if string empty.
    //         !is_string ($repto) ? '' : $repto,
    //
    //         //** Source string
    //         $str
    //     );
    // }
    //

    static function purify($message) {

        require_once 'htmlpurifier/HTMLPurifier.auto.php';


        // trim
        $message = str_replace('&nbsp;', ' ', $message);
        $message = str_replace(array("\n\r", "\r"), "\n", $message);
        $message = str_replace(array("<br />\n", "<br />"), "\n", $message);
        $message = trim($message);

        // $message = preg_replace('/ (?![^<>]*>)/i', '&nbsp;', $message);
        $message = preg_replace('#( ){2}(?![^<>]*>)#i', ' &nbsp;', $message);

        // $message = preg_replace_callback('#( ){2,}(?![^<>]*>)#i',
        //      function($m) {
        //          $count = strlen($m[1]) ;
        //          $replace = str_repeat('&nbsp;', $count);
        //          return str_replace($m[1], $replace, $m[0]);
        //      }, $message);
        //
        $message = str_replace("\n", "<br />", $message);

        // print_r("\n=========\n");
        // print_r($message);
        // exit;

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', APP_CACHE_DIR);

        //$config->set('URI.DisableExternalResources', false);
        //$config->set('URI.DisableResources', false);
        $config->set('HTML.AllowedElements', 'u,p,b,i,font,span,p,blockquote,strong,em,li,ul,ol,div,br,img');
        $config->set('HTML.AllowedAttributes', 'class,style,src,height,width,align,alt,color');

        //$config->set('AutoFormat.RemoveEmpty', true);
        //$config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);

        $config->set('URI.AllowedSchemes', array(
            'http' => true,
            'https' => true,
            'mailto' => true,
            'data' => true
        ));

        $purifier = new HTMLPurifier($config);
        $message = $purifier->purify($message);

        return $message;
    }


    function uploadFile($controller, $manager) {

        require_once 'eleontev/Dir/Uploader.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

        $emanager = new ForumEntryModel;

        $message_id = 0;
        $attachment_id = $emanager->saveAttachment($message_id, array($_FILES['file']));

        $data = array(
            'name' => $_FILES['file']['name'],
            'attachment_id' => $attachment_id,
            'message_id' => $message_id);

        echo json_encode($data);
        exit;
    }
}
?>