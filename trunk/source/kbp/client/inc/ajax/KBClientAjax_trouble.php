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


class KBClientAjax_trouble extends KBClientAjax
{

    function ajaxGetNextStep($entry_id, $step_id) {

        $objResponse = new xajaxResponse();


        $trouble_view = $this->controller->getView('trouble');
        $entry_view = $this->controller->getView('entry');

        $type = ListValueModel::getListRange('article_type', false);

        // related entry
        if (!$step_id) {
            $entry = $this->manager->getEntry($entry_id);

            $objResponse->assign('entry_description', 'innerHTML', $entry['body']);
            //$objResponse->assign('entry_title', 'innerHTML', '<h1 class="articleTitle">' . $entry['title'] . '</h1>');

            $entry_display = 'block';
            $step_display = 'none';
            $prev_button = true;


            if ($entry_id == $this->entry_id) {
                $prev_button = false;
            }

            $step_comment_block = '';
            $step_attachment_block = '';
            $external_links_step_block = '';
            $nextstep_msg = $entry['nextstep_msg'];

        // step
        } else {
            $step = $this->manager->getStepById($step_id);

            $entry_display = 'none';
            $step_display = 'block';
            $prev_button = false;
            $nextstep_msg = $step['nextstep_msg'];


            // step is linked to the entry
            if ($step['related_entry_id']) {
                $redirect_link = $this->controller->getLink('trouble', false, $step['related_entry_id']);

                $objResponse->redirect($redirect_link);
                return $objResponse;

                /*
                $entry_id = $step['related_entry_id'];
                $step_id = 0;
                $prev_button = true;
                */

            }

            $objResponse->assign('step_title', 'innerHTML', '<h1 class="articleTitle">' . $step['title'] . '</h1>');

            $step_comment_block =  $trouble_view->getStepCommentBlock($this->manager, $entry_id, $step_id, $step['parent_id']);
            $step_attachment_block = $entry_view->getEntryListAttachment($this->manager, $step_id, 'trouble');
            $external_links_step_block = $entry_view->getEntryListExternalLink($step, $type);
        }


        $step_block = $trouble_view->getStepBlock($this->manager, $entry_id, $step_id, $prev_button);

        //$objResponse->assign('entry_title', 'style.display', $entry_display);
        $objResponse->assign('entry_description', 'style.display', $entry_display);

        $objResponse->assign('step', 'style.display', $step_display);
        $objResponse->assign('step_title', 'style.display', $step_display);

        $objResponse->assign('nextstep_msg', 'innerHTML', $nextstep_msg);

        $objResponse->assign('step_block', 'innerHTML', $step_block);
        $objResponse->assign('attachment_step_block', 'innerHTML', $step_attachment_block);
        $objResponse->assign('external_links_step_block', 'innerHTML', $external_links_step_block);

        $objResponse->assign('comment_step_block', 'innerHTML', $step_comment_block);

        // related articles
        $related_article_step_block = '';
        if ($step_id) {

            $this->manager->tbl->related_to_entry = $this->manager->tbl->article_to_step;
            $this->manager->tbl->entry = $this->manager->tbl->kb_entry;
            $this->manager->tbl->category = $this->manager->tbl->kb_category;

            $related = $this->manager->getEntryRelated($step_id);
            $related_article_step_block = $entry_view->getEntryListRelated($this->manager, $type, $related['attached']);

            if(DocumentParser::isTemplate($step['body'])) {
                DocumentParser::parseTemplate($step['body'], array($this->manager, 'getTemplate'));
            }

            if(DocumentParser::isLink($step['body'])) {
                DocumentParser::parseLink($step['body'], array($entry_view, 'getLink'), $this->manager, $related['inline'], $entry_id, $this->controller);
            }

            if(DocumentParser::isCode($step['body'])) {
                DocumentParser::parseCode($step['body'], $this->manager, $this->controller);
            }

            DocumentParser::parseCurlyBraces($step['body']);

            $objResponse->assign('step', 'innerHTML', $step['body']);

            // print link
            $print_link = $trouble_view->getLink('print-step', false, $step_id);
            $print_link = $this->controller->_replaceArgSeparator($print_link);
            $objResponse->call('setPrintLink', $print_link);

        } else {

            $print_link = $trouble_view->getLink('print-trouble', false, $entry_id);
            $print_link = $this->controller->_replaceArgSeparator($print_link);
            $objResponse->call('setPrintLink', $print_link);
        }

        $objResponse->assign('related_article_step_block', 'innerHTML', $related_article_step_block);

        // to start button
        $objResponse->call('setStartButton');


        // trouble block
        if (($entry_id = $trouble_view->entry_id) && !$step_id) {
            $objResponse->assign('trouble_block', 'style.display', 'block');
        } else {
            $objResponse->assign('trouble_block', 'style.display', 'none');
        }

        $objResponse->call('setEditInPlace');

        return $objResponse;
    }

    function ajaxGeneratePrevSteps($entry_id, $step_id) {

        $objResponse = new xajaxResponse();

        $arr = $this->manager->getSteps($entry_id);

        $parents = TreeHelperUtil::getParentsById($arr, $step_id);

        $data = array(array('entry_id' => $entry_id, 'step_id' => 0));
        foreach ($parents as $parent) {
            if ($parent != $step_id) {
                $data[] = array('entry_id' => $entry_id, 'step_id' => $parent);
            }
        }

        $objResponse->call('setPrevSteps', $data);

        return $objResponse;
    }

    function ajaxGetNextComments($bp, $step_id) {

        $objResponse = new xajaxResponse();

        $trouble_view = $this->controller->getView('trouble_comment');

        $_GET['bp'] = $bp;
        $limit = $this->manager->getSetting('num_comments_per_page');
        $num_comment = $this->manager->getCommentListCount($step_id);
        $bp = $trouble_view->pageByPage($limit, $num_comment);

        $data = $trouble_view->getList($this->manager, $step_id, $bp->limit, $bp->offset);

        // limit reached, hide button
        if ($bp->offset + $bp->limit >= $bp->num_records) {
            $objResponse->assign('comments_button', 'style.display', 'none');
        } else {
            $by_page = $bp->_getBpValuesNext('standart');
            $by_page = implode($bp->get_delim, $by_page);

            $js_str = '<span onclick="xajax_getNextComments(\'%s\', %d);">%s</span>';
            $objResponse->assign('comments_button', 'innerHTML', sprintf($js_str, $by_page, $step_id, $trouble_view->msg['get_next_comment_msg']));
        }

        $objResponse->call('insertNewComment', $data);
        return $objResponse;
    }


    function ajaxGetRawMessage($id) {

        $objResponse = new xajaxResponse();

        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleCommentModel.php';

        $manager = new TroubleCommentModel();
        $data = $manager->getById($id);

        $objResponse->addScriptCall('insertRawMessage', $data['comment'], $id);
        return $objResponse;
    }


    function ajaxPostComment($id, $values, $comment_count) {

        $objResponse = new xajaxResponse();

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        require_once 'eleontev/Validator.php';
        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleComment.php';
        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleCommentModel.php';

        $obj = new TroubleComment;
        $manager = new TroubleCommentModel();

        $view = $this->controller->getView('trouble');

        $msgs = AppMsg::getMsgs('error_msg.ini');

        if ($values['comment'] == '') {
            $objResponse->assign('comment_error_msg', 'innerHTML', $msgs['required_msg']);
            $objResponse->assign('comment_error_msg', 'style.color', 'red');
            return $objResponse;
        }

        if (isset($values['email'])) {
            if (!$ret = Validate::email(trim($values['email']))) {
                $objResponse->assign('comment_error_msg', 'innerHTML', $msgs['email_msg']);
                $objResponse->assign('comment_error_msg', 'style.color', 'red');
                return $objResponse;
            }
        }

        if($view->useCaptcha($this->manager, 'comment')) {
            if(!$view->isCaptchaValid($values['captcha'])) {
                $objResponse->assign('captcha_error_msg', 'innerHTML', $msgs['captcha_text_msg']);
                $objResponse->assign('captcha_error_msg', 'style.color', 'red');
                return $objResponse;
            }
        }


        if($this->manager->is_registered) {
            $msg = ($this->manager->getSetting('comment_policy') != 2) ? 'comment_posted' : 'comment_wait';
            $active = ($this->manager->getSetting('comment_policy') != 2) ? 1 : 0;
        } else {
            $msg = ($this->manager->getSetting('comment_policy') == 1) ? 'comment_posted' : 'comment_wait';
            $active = ($this->manager->getSetting('comment_policy') == 1) ? 1 : 0;
        }

        // for user who has priv for comment, no comment_policy in use
        $allowed = AuthPriv::getPrivAllowed('trouble_comment');
        if(in_array('insert', $allowed)) {
            $active = 1;
            $msg = 'comment_posted';
        }


        $comment = RequestDataUtil::stripVars($values['comment'], array(), 'addslashes');

        $obj->set($values);
        $obj->set('comment', $comment);
        $obj->set('id', NULL);
        $obj->set('date_posted', NULL);
        $obj->set('entry_id', $id);
        $obj->set('user_id', $this->manager->user_id);
        $obj->set('active', $active);

        $comment_id = $manager->add($obj);
        $obj->set('id', $comment_id);

        if(!$active) {
            /*$sent = $this->manager->sendApproveCommentAdmin($comment_id,
                                                      $this->entry_id,
                                                      $this->category_id,
                                                      $this->rp->vars);*/
        } else {
            $manager->updateCommentDateForEntry($this->entry_id);
        }


        // clear form
        $objResponse->assign('comment', 'value', '');
        $objResponse->assign('comment_form', 'style.display', 'none');
        $objResponse->assign('captcha', 'value', '');
        $objResponse->assign('email', 'value', '');
        $objResponse->assign('name', 'value', '');

        // refresh captcha
        $captcha_url = APP_CLIENT_PATH . 'captcha.php';
        $refresf_js_str = "$('#comment_captcha').attr('src', '%s?rand=' + Math.random());";
        $objResponse->script(sprintf($refresf_js_str, $captcha_url));


        $num_comment = $this->manager->getCommentListCount($id);
        if (($comment_count + 1) == $num_comment) {
            $view = $this->controller->getView('trouble_comment');
            $comment_block = $view->getCommentBlock($this->manager, $obj);

            $objResponse->call('insertNewComment', $comment_block);


            $file = $view->getMsgFile('after_action_msg.ini', 'public');
            $msgs = AppMsg::parseMsgs($file, 'comment_posted');

            $objResponse->assign('comment_msg_' . $comment_id, 'innerHTML', BoxMsg::factory('success', $msgs));

            $js_str = '$("#comment_msg_%d").delay(2000).fadeOut(1000);';
            $objResponse->script(sprintf($js_str, $comment_id));
        }

        return $objResponse;
    }


    function ajaxUpdateComment($id, $comment) {

        $objResponse = new xajaxResponse();

        $view = $this->controller->getView('trouble');

        $allowed = AuthPriv::getPrivAllowed('trouble_comment');
        if(!in_array('update', $allowed)) {
            return $objResponse;
        }

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleComment.php';
        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleCommentModel.php';
        require_once APP_MODULE_DIR . 'knowledgebase/comment/inc/KBCommentView_helper.php';

        $obj = new TroubleComment;
        $manager = new TroubleCommentModel();

        $parser = KBCommentView_helper::getBBCodeObj();

        $data = $manager->getById($id);
        $data = RequestDataUtil::stripVars($data, array(), 'addslashes');

        $obj->set($data);
        $obj->set('comment', $comment);

        $manager->save($obj);

        $file = $view->getMsgFile('after_action_msg.ini', 'public');
        $msgs = AppMsg::parseMsgs($file, 'updated');

        $objResponse->assign('comment_msg_' . $id, 'innerHTML', BoxMsg::factory('success', $msgs));
        $js_str = '$("#comment_msg_%d").delay(2000).fadeOut(1000);';
        $objResponse->script(sprintf($js_str, $id));

        $objResponse->assign('comment_text_' . $id, 'innerHTML', nl2br($parser->qparse($obj->get('comment'))));
        $objResponse->assign('comment_action_' . $id, 'style.display', 'block');

        return $objResponse;
    }


    function ajaxDeleteComment($id) {

        $objResponse = new xajaxResponse();

        $view = $this->controller->getView('trouble');

        $allowed = AuthPriv::getPrivAllowed('trouble_comment');
        if(!in_array('delete', $allowed)) {
            return $objResponse;
        }

        require_once APP_MODULE_DIR . 'trouble/comment/inc/TroubleCommentModel.php';

        $manager = new TroubleCommentModel();
        $manager->delete($id);

        //$js_str = '$("#comment_%d").remove();';
        $js_str = '$("#comment_%d").fadeOut(1000, function() { $("#comment_%d").remove(); });';
        $objResponse->script(sprintf($js_str, $id, $id));

        $file = $view->getMsgFile('after_action_msg.ini', 'public');
        $msgs = AppMsg::parseMsgs($file, 'deleted');

        $objResponse->assign('comment_msg_' . $id, 'style.display', 'block');
        $objResponse->assign('comment_msg_' . $id, 'innerHTML', BoxMsg::factory('success', $msgs));

        $js_str = '$("#comment_msg_%d").delay(2000).fadeOut(1000);';
        $objResponse->script(sprintf($js_str, $id));

        return $objResponse;
    }

}
?>