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

require_once 'KBCommentView_helper.php';
require_once 'KBCommentView_list.php';


class KBCommentView_list_entry2 extends KBCommentView_list
{

    var $tmpl = 'list_entry2.html';

    var $limit = 10;

    var $skip_filter = false;


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        $entry_id = $obj->get('entry_id');
        $client_controller = &$this->controller->getClientController();

        if ($this->controller->page == 'kb_comment') {
            $tpl->tplSetNeededGlobal('comment_view');

            // update article link
            $more = array('id'=>$obj->get('entry_id'), 'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
            $tpl->tplAssign('entry_link_update', $link);

            // back link
            $link = $this->controller->getLink('knowledgebase', 'kb_comment');
            $tpl->tplAssign('back_link', $link);

        } else {
            $tpl->tplSetNeededGlobal('entry_view');
        }

        // public article link
        $link = $client_controller->getLink('entry', false, $entry_id);
        $tpl->tplAssign('entry_link', $link);
        $tpl->tplAssign('entry_id', $entry_id);

        // subscription
        $setting = SettingModel::getQuick(100, 'allow_subscribe_comment');
        if ($setting) {
            $tpl->tplSetNeeded('/subscribe');

            if($manager->isUserSubscribedToComments($obj->get('entry_id'))) {
                $tpl->tplAssign('subscribe_yes_display', 'none');
                $tpl->tplAssign('subscribe_no_display', 'inline');
            } else {
                $tpl->tplAssign('subscribe_yes_display', 'inline');
                $tpl->tplAssign('subscribe_no_display', 'none');
            }
        }


        // BB CODE
        $parser = KBCommentView_helper::getBBCodeObj();

        // filter sql
        if (!$this->skip_filter) {
            $params = $this->getFilterSql($manager);
            $manager->setSqlParams($params['where']);
            $manager->setSqlParamsSelect($params['select']);
        }

        $manager->setSqlParams(sprintf("AND e.id = %d", $entry_id));

        // header generate
        // $bp =& $this->pageByPage($this->limit, $manager->getRecordsSql(), true, array(), 'page', 'bpt');
        $bp_options = array('class'=>'page', 'get_name'=>'bpt');
        $bp =& $this->pageByPage($this->limit, $manager->getRecordsSql(), $bp_options);
        $this->num_records = $bp->num_records;

        if($bp->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $this->commonHeaderList($bp->nav, false, false));
        }
        $tpl->tplAssign('num_records', $this->num_records);

        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        if($rows) {
            $tpl->tplAssign('title', $rows[0]['title']);
            $tpl->tplAssign('short_title', $this->getSubstringStrip($rows[0]['title'], 100));
        }

        if($this->priv->isPriv('update', 'kb_comment')) {
            $tpl->tplSetNeeded('/update');
        }

        // list records
        foreach($rows as $row) {

            $obj->set($row);

            $tpl->tplAssign('raw_comment', $obj->get('comment'));

            $obj->set('comment', nl2br($parser->qparse($obj->get('comment'))));

            $tpl->tplAssign('username', $row['username']);
            $tpl->tplAssign('r_email', $row['r_email']);

            $formatted_date = $this->getFormatedDate($row['date_posted'], 'datetime');
            $tpl->tplAssign('formatted_date', $formatted_date);

            $interval_date = $this->getTimeInterval($row['date_posted'], true);
            $tpl->tplAssign('interval_date', $interval_date);

            // status
            if($obj->get('active')) {
                $tpl->tplAssign('status_yes_display', 'none');
                $tpl->tplAssign('status_no_display', 'block');
            } else {
                $tpl->tplAssign('status_yes_display', 'block');
                $tpl->tplAssign('status_no_display', 'none');
            }

            if($this->priv->isPriv('update', 'kb_comment')) {
                $tpl->tplSetNeeded('row/update');
            }

            if($this->priv->isPriv('delete', 'kb_comment')) {
                $tpl->tplSetNeeded('row/delete');
            }

            if($this->priv->isPriv('status', 'kb_comment')) {
                $tpl->tplSetNeeded('row/update_status');
            }

            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active')));

            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        if ($this->controller->page == 'kb_comment') {
            $more = array('entry_id' => $this->controller->getMoreParam('entry_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }

        $this->parser = $parser;

        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        $xajax->registerFunction(array('addComment', $this, 'ajaxAddComment'));
        $xajax->registerFunction(array('updateCommentStatus', $this, 'ajaxUpdateCommentStatus'));
        $xajax->registerFunction(array('subscribe', $this, 'ajaxSubscribe'));
        $xajax->registerFunction(array('deleteAllComments', $this, 'ajaxDeleteAllComments'));

        if($this->priv->isPriv('insert', 'kb_comment')) {
            $tpl->tplSetNeeded('/add_new');
        }

        if($this->priv->isPriv('delete', 'kb_comment')) {
            $tpl->tplSetNeeded('/delete_all');
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->setStatusFormVars(1, false));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function ajaxDeleteComment($id) {
        $objResponse = new xajaxResponse();

        $entry_id = $this->manager->getEntryIdById($id);
        $this->manager->delete($id);
        $this->manager->updateCommentDateForEntry($entry_id);

        $fade_js = "$('#comment_{$id}').fadeOut(1000);";
        $objResponse->script($fade_js);

        $this->_ajaxRefreshHeader($objResponse, $this->manager->limit - 1, $this->num_records - 1);

        return $objResponse;
    }


    function ajaxUpdateComment($id, $comment) {
        $objResponse = new xajaxResponse();

        $data = $this->manager->getById($id);
        $this->obj->set($data);

        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $this->obj->set('comment', $comment);

        $this->manager->save($this->obj);
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'));

        $objResponse->script('$("#comment").val("");');

        $comment = $this->obj->get('comment');
        $comment = RequestDataUtil::stripVars($comment, array(), 'stripslashes');
        $objResponse->call('insertUpdatedComment', $id, nl2br($this->parser->qparse($comment)), $comment);

        return $objResponse;
    }


    function ajaxAddComment($comment, $status) {
        $objResponse = new xajaxResponse();

        $this->obj = new KBComment;

        $entry_id = ($this->controller->page == 'kb_comment') ? $_GET['entry_id'] : $_GET['id'];
        $this->obj->set('entry_id', $entry_id);

        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $this->obj->set('comment', $comment);

        $this->obj->set('date_posted', null);
        $this->obj->set('user_id', AuthPriv::getUserId());
        $this->obj->set('active', $status);

        $comment_id = $this->manager->save($this->obj);
        $this->obj->set('id', $comment_id);

        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'));

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        $row = $this->obj->get();
        $row['raw_comment'] = RequestDataUtil::stripVars($row['comment'], array(), 'stripslashes');
        $row['comment'] = nl2br($this->parser->qparse($row['raw_comment']));
        $row['formatted_date'] = $this->getFormatedDate($this->obj->get('date_posted'), 'datetime');
        $row['interval_date'] = $this->getTimeInterval($this->obj->get('date_posted', true));
        $row['username'] = AuthPriv::getUsername();

        if($this->obj->get('active')) {
            $row['status_yes_display'] = 'none';
            $row['status_no_display'] = 'block';

        } else {
            $row['status_yes_display'] = 'block';
            $row['status_no_display'] = 'none';
        }

        if($this->priv->isPriv('update', 'kb_comment')) {
            $tpl->tplSetNeeded('row/update');
        }

        if($this->priv->isPriv('delete', 'kb_comment')) {
            $tpl->tplSetNeeded('row/delete');
        }

        if($this->priv->isPriv('status', 'kb_comment')) {
            $tpl->tplSetNeeded('row/update_status');
        }

        $tpl->tplParse(array_merge($row, $this->msg), 'row');

        $objResponse->call('insertComment', $tpl->parsed['row']);
        $objResponse->script('$("#comment_form").hide();');
        $objResponse->script('$("#comment").val("");');

        $this->_ajaxRefreshHeader($objResponse, $this->manager->limit + 1, $this->num_records + 1);

        return $objResponse;
    }


    function _ajaxRefreshHeader($objResponse, $limit, $num) {
        $objResponse->addAssign('comments_num', 'innerHTML', $num);
    }


    function ajaxUpdateCommentStatus($id, $status) {
        $objResponse = new xajaxResponse();

        $status = ($status) ? 1 : 0;

        $entry_id = $this->manager->getEntryIdById($id);
        $this->manager->status($status, $id);
        $this->manager->updateCommentDateForEntry($entry_id);

         if($status) {
            $objResponse->addAssign('status_yes_' . $id, 'style.display', 'none');
            $objResponse->addAssign('status_no_' . $id, 'style.display', 'block');

        } else {
            $objResponse->addAssign('status_yes_' . $id, 'style.display', 'block');
            $objResponse->addAssign('status_no_' . $id, 'style.display', 'none');
        }

        return $objResponse;
    }


    function ajaxSubscribe($value) {

        $objResponse = new xajaxResponse();

        $value = (int) $value;
        $user_id = AuthPriv::getUserId();

        require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';
        $manager = new SubscriptionModel;

        if($value) {
            $manager->saveSubscription(array($this->obj->get('entry_id')), 31, $user_id);
            $objResponse->addAssign('div_subscribe_yes', 'style.display', 'none');
            $objResponse->addAssign('div_subscribe_no', 'style.display', 'inline');

        } else {
            $manager->deleteSubscription(array($this->obj->get('entry_id')), 31, $user_id);
            $objResponse->addAssign('div_subscribe_yes', 'style.display', 'inline');
            $objResponse->addAssign('div_subscribe_no', 'style.display', 'none');
        }

        return $objResponse;
    }


    function ajaxDeleteAllComments() {

        $objResponse = new xajaxResponse();

        $this->manager->deleteByEntryId($this->obj->get('entry_id'));
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'), NULL);

        $objResponse->addAssign('commentsBlock', 'innerHTML', '');

        $this->_ajaxRefreshHeader($objResponse, 0, 0);

        return $objResponse;
    }
}
?>