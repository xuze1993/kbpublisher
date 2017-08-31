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


class KBDraftView_common
{

    static function getEntryMenu($obj, $manager, $view, $emanager) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $own_record = ($entry['author_id'] == $manager->user_id);
        $record_id = $obj->get('id');

        // preview
        $link = $view->getActionLink('preview', $record_id);
        $tabs['preview'] = array(
            'link' => sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link),
            'title'  => $view->msg['preview_msg']
        );

        // approval log
        $approval_log = $manager->getApprovalLog($obj->get('id'));
        if (!empty($approval_log)) {
            $tabs['approval_log'] = array(
                'link' => $view->getActionLink('approval_log', $record_id),
                'title'  => $view->msg['workflow_log_msg']
            );
        }

        // approval
        if ($manager->checkForApproval($record_id, 'approval')) {
            $tabs['approval'] = array(
                'link' => $view->getActionLink('approval', $record_id),
                'title'  => $view->msg['review_msg']
            );
        }

        if (!$manager->checkForApproval($record_id, 'update')) {
            $index = array_search('update', $tabs);
            unset($tabs[$index]);
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
        if(in_array($view->controller->action, array('update','approval'))) {
            $back_link = urlencode($back_link);
            $options['back_link'] = sprintf("javascript:cancelHandler('%s');", $back_link);
        }

        // menu
        $options['more'] = array();

        if($obj->get('entry_id')) {
            $options['more']['entry'] = array(
                'link' => $view->getLink('knowledgebase', 'kb_entry', false, 'detail', array('id' => $obj->get('entry_id'))),
                'title'  => $view->msg['view_article_msg']
            );
        }

        $options['more'][] = 'delete';

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }


    static function getDraftsMessage($view, $page) {
        $vars['link'] = $view->getLink('this', $page, false, false);
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = ''; //$msgs['title_entry_autosave'];
        $msg['body'] = $msgs['note_entry_draft'];
        return BoxMsg::factory('hint', $msg, $vars);
    }
    
    
    static function getAssigneeBlock($assignees, $view, $manager) {
        if (!empty($assignees)) {
            if (is_array($assignees)) {
                $assignees = implode(',', $assignees);
            }
            
            $assignees = $manager->getUser($assignees, false);
            $assignee_str = array();
            foreach ($assignees as $assignee) {
                $assignee_str[] = sprintf('<i>%s</i>', PersonHelper::getEasyName($assignee));
            }
            
            $user_str = implode(', ', $assignee_str);
            
        } else {
            $admin_email = SettingModel::getQuick(134, 'admin_email');
            $user_str = sprintf('<i>%s</i>', $admin_email);
        }
        
        $block_str = '%s: %s';
        $block = sprintf($block_str, $view->msg['submission_note_msg'], $user_str);
        
        return $block;
    }
}
?>