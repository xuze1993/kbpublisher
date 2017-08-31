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


class KBEntryView_common
{

    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $status = $obj->get('active');
        $record_id = $obj->get('id');
        $own_record = ($entry['author_id'] == $manager->user_id);


        // history
        $hnum = $manager->getHistoryNum($record_id);
        if (!empty($hnum)) {
            $tabs['history'] = array(
                'link' => $view->getActionLink('history', $record_id),
                'title' => sprintf('%s (%d)', $view->msg['history_msg'], $hnum[$record_id]+1),
                'highlight' => array('diff')
            );
        }

        // comment
        if($view->priv->isPriv('select', 'kb_comment')) {
            $tabs['comment'] = array(
                'link' => $view->getActionLink('kb_comment', $record_id),
                'title'  => $view->msg['comments_msg']
            );

            // self
            if($view->priv->isSelfPriv('select', 'kb_comment') && !$own_record) {
                unset($tabs['comment']);
            }
        }

        // rating
        if($view->priv->isPriv('select', 'kb_rate')) {
            $tabs['rate'] = array(
                'link' => $view->getActionLink('kb_rate', $record_id),
                'title'  => $view->msg['rating_comment_num_msg']
            );

            // self
            if($view->priv->isSelfPriv('select', 'kb_rate') && !$own_record) {
                unset($tabs['rate']);
            }
        }

        // preview
        $link = $view->getActionLink('preview', $record_id);
        $tabs['preview'] = array(
            'link' => sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link),
            'title'  => $view->msg['preview_msg']
        );

        // public
        $cats = $manager->getCategoryByIds($record_id);
        $cats = $cats[$record_id];
        $publish_status_ids = $manager->getEntryStatusPublished('article_status');
        $published = CommonEntryView::isEntryPublished($obj->get(), $cats, $publish_status_ids);

        if($published) {
            $client_controller = &$view->controller->getClientController();
            $tabs['public'] = array(
                'link' => $client_controller->getLink('entry', $obj->get('category_id'), $record_id),
                'title'  => $view->msg['entry_public_link_msg'],
                'options'  => array('target' => '_blank')
            );
        }

        // approval
        $approval_log = $manager->isApprovalLogAvailable($record_id);
        if (!empty($approval_log)) {
            $tabs['approval'] = array(
                'link' => $view->getActionLink('approval_log', $record_id),
                'title'  => $view->msg['workflow_log_msg']
            );
        }

        // back button
        $options = array();
        $back_link = $view->controller->getCommonLink();
        if($referer = @$_GET['referer']) {
            if(strpos($referer, 'client') !== false) {
                $client_link = array('entry', false, $record_id);
                $back_link = $view->controller->getClientLink($client_link);
            }
        }

        $options['back_link'] = $back_link;
        if(in_array($view->controller->action, array('update'))) {
            $back_link = urlencode($back_link);
            $options['back_link'] = sprintf("javascript:cancelHandler('%s');", $back_link);
        }

        // menu
        $options['more'] = KBEntryView_common::getMoreMenu($obj, $manager, $view, 'kb_draft');

        // if some of categories is private
        // and user do not have this role so he can't access to some actions
        $has_private = $manager->isCategoryNotInUserRole($obj->getCategory());
        if($has_private) {
            unset($tabs[array_search('update', $tabs)]);
            unset($tabs['history']);
            unset($tabs['approval']);
            $options['more'] = array();
        }

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }


    static function getMoreMenu($obj, $manager, $view, $page) {

        $options = array('clone');
        $record_id = $obj->get('id');

        $rlink = $view->controller->getLink('all');
        $referer = WebUtil::serialize_url($rlink);


        $draft_id = $manager->isEntryDrafted($record_id);
        if ($draft_id) {
            $more = array('id' => $draft_id, 'referer' => $referer);
            $options['draft'] = array(
                'link' => $view->getLink('this', $page, false, 'detail', $more),
                'title' => $view->msg['view_draft_msg']
            );

        } else {
            if($view->priv->isPriv('insert', $page)) {
                $more = array('entry_id' => $record_id);
                $options['draft'] = array(
                    'link' => $view->getLink('this', $page, false, 'insert', $more),
                    'title'  => $view->msg['update_as_draft_msg']
                );

                if($view->priv->isPriv('delete')) {
                    $more = array('referer' => $referer);
                    $options['move_to_draft'] = array(
                        'link' => $view->getActionLink('move_to_draft', $record_id, $more),
                        'title' => $view->msg['move_to_drafts_msg'],
                        'confirm_msg' => $view->msg['move_to_drafts_note_msg']
                    );

                    // self
                    if($view->priv->isSelfPriv('delete') && !$own_record) {
                        unset($options['move_to_draft']);
                    }
                }
            }

            $options['delete'] = array(
                'title'  => $view->msg['trash_msg']
            );
        }

        // duplicate
        if($view->priv->isPrivOptional('insert', 'draft')) {
            unset($options[array_search('clone', $options)]);
        }


        return $options;
    }


    static function getDraftsMessage($view, $page) {
        $vars['link'] = $view->getLink('this', $page, false, false);
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = ''; //$msgs['title_entry_autosave'];
        $msg['body'] = $msgs['note_entry_draft'];
        return BoxMsg::factory('hint', $msg, $vars);
    }


    static function getUpdateAsDraftLink($view, $entry_id) {
        $rlink = $view->controller->getLink('full');
        $referer = WebUtil::serialize_url($rlink);
        $more = array('entry_id' => $entry_id, 'referer' => $referer);
        return $view->getLink('this', 'kb_draft', false, 'insert', $more);
    }
}
?>