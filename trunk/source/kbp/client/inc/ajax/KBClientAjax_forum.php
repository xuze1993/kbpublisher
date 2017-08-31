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


class KBClientAjax_forum extends KBClientAjax
{

    function ajaxLoadMessageForm($message_id) {

        $message_id = (int) $message_id;

        $objResponse = new xajaxResponse();

        $html = $this->view->getMessageForm($message_id, $this->manager);
        $objResponse->call('insertForm', $message_id, $html);

        return $objResponse;
    }


    function ajaxUpdateMessage($message_id, $message = false, $attachment_changes = false) {
        $objResponse = new xajaxResponse();

        $view = $this->controller->getView('forum');

        if ($message !== false) {
            $trimmed_message = trim(str_replace(array('<br />', '&nbsp;'), '', $message));
            if (strlen($trimmed_message) == 0) { // nothing but whitespaces
                $objResponse->script(sprintf('$.growl.error({title: "", message: "%s"});', $view->msg['comment_cannot_be_empty_msg']));
                return $objResponse;
            }
        }

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumMessage.php';
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

        $obj = new ForumMessage;
        $manager2 = new ForumEntryModel;

        $data = $this->manager->getMessageById($message_id);

        if ($message) { // updating

            $admin_allowed = $this->manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
            $user_allowed = $this->manager->getPostActionsAllowedUser($data['user_id'], $data['date_posted'], $admin_allowed, 900);// extra 15 min
            $allowed = array_merge($admin_allowed, $user_allowed);

            if(!in_array('update', $allowed)) {
                $msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'message_not_allowed_update');
                $msg = addslashes(implode("\n", $msg));
                $objResponse->addAlert($msg);
                return $objResponse;
            }

            $this->controller->getAction('forums');
            $message = KBClientAction_forums::purify($message);

            $_message = $message;
            $_message = RequestDataUtil::stripVars($_message, array(), 'addslashes');

            $obj->set($data);
            $obj->set('message', $_message);
            $obj->set('message_index', RequestDataUtil::getIndexText($_message));

            $this->manager->saveMessage($obj);

            // attachments
            if (!empty($attachment_changes)) {
                if (!empty($attachment_changes['deleted'])) {
                    $manager2->reassignAttachment(0, $attachment_changes['deleted']);
                }

                if (!empty($attachment_changes['added'])) {
                    $manager2->reassignAttachment($message_id, $attachment_changes['added']);
                }
            }


            $attachments = $this->manager->getMessageAttachment($message_id);
            $attachment_block = $this->view->getAttachmentBlock($message_id, $attachments);

            $objResponse->assign('forumMsgBlockAttachment' . $message_id, 'innerHTML', $attachment_block);


            $updated_str = '%s: %s %s <b>%s %s</b>';
            $interval_date = $view->getTimeInterval(time(), true);
            $user = $this->manager->getUserInfo($obj->get('updater_id'));
            $updated_str = sprintf($updated_str, $view->msg['updated_msg'], $interval_date, $view->msg['by_msg'], $user['first_name'], $user['last_name']);
            $objResponse->assign('forumMsgUpdater' . $message_id, 'innerHTML', $updated_str);

            $objResponse->assign('forumBody' . $message_id, 'innerHTML', $message);

            $objResponse->addScriptCall('updateSavedMessage', $message_id);

            $msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'updated');
            $objResponse->script(sprintf('$.growl({title: "", message: "%s"});', $msg['body']));

        } else { // reverting
            $objResponse->addScriptCall('revert', $message_id);
        }

        $objResponse->assign('forumErrorMsg' . $message_id, 'innerHTML', '');

        return $objResponse;
    }


    function ajaxDeleteMessage($message_id) {

        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

		$message_id = (int) $message_id;

        $objResponse = new xajaxResponse();

		$data = $this->manager->getMessageById($message_id);

        $first_message = $this->manager->getTopicFirstMessage($data['entry_id']);

        $admin_allowed = $this->manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
        $user_allowed = $this->manager->getPostActionsAllowedUser($data['user_id'], $data['date_posted'], $admin_allowed, 900);// extra 15 min
        $allowed = array_merge($admin_allowed, $user_allowed);

        if(!in_array('delete', $allowed)) {
			$msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'message_not_allowed_delete');
			$msg = addslashes(implode("\n", $msg));
			$objResponse->addAlert($msg);
        	return $objResponse;
		}

        $this->manager->deleteAttachment($message_id);
        $this->manager->deleteMessage($message_id);

        $new_first_message = $this->manager->getTopicFirstMessage($data['entry_id']);

        if ($new_first_message) {
            $this->manager->updateMessageFieldsForEntry($data['entry_id'], 'delete', $new_first_message['id']);

            // we need to update indices
            if ($first_message['id'] == $message_id) {
                $entry = $this->manager->getEntryById($this->entry_id, $this->category_id);

                $str = sprintf('%s %s ', $entry['title'], $entry['meta_keywords']);
                $this->manager->updateMessageIndex($new_first_message['id'], $str);
            }

        } else {
            $manager3 = new ForumEntryModel;
            $manager3->delete($data['entry_id']);

            $link = $this->controller->getLink('forums');
            $objResponse->addRedirect($link);
        }

        $objResponse->addScriptCall('deleteMessageFadeOut', $message_id);
        return $objResponse;
    }


    function ajaxStickMessage($message_id) {

        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

		$message_id = (int) $message_id;

        $objResponse = new xajaxResponse();


        $this->manager->featureMessage($this->entry_id, $message_id);

        $view = $this->controller->getView('forum');

        $objResponse->assign('sticky_status_link_' . $message_id, 'innerHTML', $view->msg['unstick_this_message_msg']);

        $script = '$("#sticky_status_link_%s").attr("onclick", "xajax_unstickMessage(%s, \'forumActionSpinner%s\');");';
        $script = sprintf($script, $message_id, $message_id, $message_id);
        $objResponse->script($script);

        $msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'success');
        $objResponse->script(sprintf('$.growl({title: "", message: "%s"});', $msg['title']));

        return $objResponse;
    }


    function ajaxUnstickMessage($message_id) {

        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';

		$message_id = (int) $message_id;

        $objResponse = new xajaxResponse();

        $this->manager->unfeatureMessage($message_id);

        $view = $this->controller->getView('forum');

        if ($_GET['bp'] == 1) {
            $objResponse->assign('sticky_status_link_' . $message_id, 'innerHTML', $view->msg['stick_this_message_msg']);

            $script = '$("#sticky_status_link_%s").attr("onclick", "xajax_stickMessage(%s, \'forumActionSpinner%s\');");';
            $script = sprintf($script, $message_id, $message_id, $message_id);
            $objResponse->script($script);

        } else {
            $objResponse->script(sprintf('$("#forumBlock%s").remove();', $message_id));
        }

        $msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'success');
        $objResponse->script(sprintf('$.growl({title: "", message: "%s"});', $msg['title']));

        return $objResponse;
    }


    function ajaxGetQuotedMessage($message_id, $text = false) {

        $message_id = (int) $message_id;

        $objResponse = new xajaxResponse();

        $data = $this->manager->getMessageById($message_id);

        if(DocumentParserForum::isQuote($data['message'])) {
            $data['message'] = DocumentParserForum::cutQuote($data['message']);
        }

        $view = $this->controller->getView('forum');

        //$quoted_message = '[quote=%s]%s[/quote]';
        //$quoted_message = sprintf($quoted_message, $data['username'], $data['message']);

        $html = '<div class="forumQuote"></div>';
        $html = '<blockquote><div class="forumQuoteAuthor">%s %s:</div>%s</blockquote>';
        $message = ($text) ? nl2br($text) : $data['message'];
        $html = sprintf($html, $data['username'], $view->msg['wrote_msg'], $message);

        //$objResponse->addAssign('message', 'innerHTML', $quoted_message);
        $objResponse->call('insertQuote', $html);

        return $objResponse;
    }


    function loadNextEntries($offset) {

        $objResponse = new xajaxResponse();

        $limit = $this->view->dynamic_limit + 1;

        switch ($this->view->dynamic_type) {
            case 'forum_recent':
                $rows = $this->view->getEntryListRecent($this->manager, $limit, $offset);
                break;
        }

        if (empty($rows)) {
            $objResponse->call('DynamicEntriesScrollLoader.insert', '', 1);
            return $objResponse;
        }


        $end_reached = (int) (count($rows) <= $this->view->dynamic_limit);
        if (!$end_reached) {
            array_pop($rows);
        }

        $sname = sprintf($this->view->dynamic_sname, $this->view->dynamic_type);
        $_SESSION[$sname] = $offset + count($rows);

        // replace bad utf
        /*$replace_utf = RequestDataUtil::badUtfLoad($this->encoding);
        if($replace_utf) {
            $summary_limit = $this->manager->getSetting('preview_article_limit');
            foreach(array_keys($rows) as $k) {
                $rows[$k]['title'] = RequestDataUtil::stripVarBadUtf($rows[$k]['title']);
                $rows[$k]['body'] = DocumentParser::getSummary($rows[$k]['body'], $summary_limit);
                $rows[$k]['body'] = RequestDataUtil::stripVarBadUtf($rows[$k]['body']);
            }
        }*/

        $rows = $this->view->stripVars($rows);
        $tpl = $this->view->_parseEntryList($this->manager, $rows, '', '', false);

        if ($tpl instanceof tplTemplatez) {
            $data = $tpl->parsed['row'];

            // $objResponse->addAlert('<pre>' . print_r($this->manager->sql_params_order, 1) . '</pre>');
            $objResponse->call('DynamicEntriesScrollLoader.insert', $data, $end_reached);
        }

        return $objResponse;
    }

}
?>