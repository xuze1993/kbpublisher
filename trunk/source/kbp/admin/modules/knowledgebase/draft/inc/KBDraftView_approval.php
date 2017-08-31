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


class KBDraftView_approval extends AppView
{
    
    var $template = 'form_approval.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        list($eobj, $emanager) = $data;

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $this->template_dir = APP_MODULE_DIR . '/knowledgebase/draft/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('menu_block', $this->getMenuBlock($obj, $manager, $this, $emanager));
        $tpl->tplAssign('draft_id', $obj->get('id'));
        
        // approval history
        $tpl->tplAssign('approval_history_block', $this->getApprovalHistory($obj));
        
        if (!empty($_GET['dialog'])) {
            $tpl->tplSetNeeded('/auto_dialog');
            $tpl->tplAssign('button_name', $_GET['dialog']);
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('cancelHandler', $this, 'ajaxCancelHandler'));
        $xajax->registerFunction(array('saveAssignee', $this, 'ajaxSaveAssignee'));
        
        //$date_submitted = $manager->getSubmissionDate($obj->get('id'));
        $tpl->tplAssign('date_submitted', $this->getFormatedDate($obj->last_event['date_posted'], 'datetime'));
        
        // author 
        if ($obj->get('author_id')) {
            $user = $emanager->getUser($obj->get('author_id'));
            $user_str = (empty($user)) ? '--' : PersonHelper::getEasyName($user);
            $tpl->tplAssign('author', $user_str);
        }
        
        // submitter
        $user = $emanager->getUser($obj->last_event['user_id']);
        $user_str = (empty($user)) ? '--' : PersonHelper::getEasyName($user);
        $tpl->tplAssign('submitter', $user_str);
        
        
        $assignees = ($obj->assignee) ? $emanager->getUser(implode(',', $obj->assignee), false) : array();
        
        $user_popup_link = $this->getLink('users', 'user', false, false, array('filter[priv]' => 'any', 'filter[s]' => 1));
        $tpl->tplAssign('user_popup_link', $user_popup_link);
        
        foreach($assignees as $id => $assignee) {
            $name = PersonHelper::getEasyName($assignee);
            $data = array('user_id' => $id, 'name' => $name);
            $data['delete_msg'] = $this->msg['delete_msg'];
            // $data['delete_assignee_display'] = (count($assignees) == 1) ? 'none' : 'block';
            $data['delete_assignee_display'] = 'block';
            
            $tpl->tplParse($data, 'assignee_row');
        }
        
        $tpl->tplAssign('assignees', $this->getAssigneeJsStr($assignees));
        
        
        if ($obj->last_event['step_num'] > 1) {
            $tpl->tplSetNeeded('/step_selection');
            
            $select = new FormSelect;
            $select->select_tag = false;
            
            $range = array(
                'start' => $this->msg['initial_step_msg'],
                'prev_step' => $this->msg['prev_step_msg']
            );
            
            if ($obj->last_event['step_title']) {
                $range['prev_step'] .= sprintf(' (%s)', $obj->last_event['step_title']);
            }
            
            $select->setRange($range);
            $step_select = $select->select();
            
            $tpl->tplAssign('step_select', $step_select);
        }
        
        
        // rejection notifications
        $block = KBDraftView_common::getAssigneeBlock($obj->s_event['user_id'], $this, $manager);
        $tpl->tplAssign('rejection_notification_block_start', $block);
        
        $block = KBDraftView_common::getAssigneeBlock($obj->last_event['user_id'], $this, $manager);
        $tpl->tplAssign('rejection_notification_block_prev_step', $block);
        
        // if last step 
        $do_confirm = 0;
        $steps_ahead = count($obj->active_workflow['action']) - $obj->last_event['step_num'];
        if($steps_ahead == 0) {
            $this->msg['approve_msg'] = $this->msg['publish_msg'];
            if ($obj->get('entry_id')) {
                $do_confirm = 1;
            }
            
        } else {
            $assignees = $manager->getAssignees($obj, $eobj, $emanager, $obj->active_workflow, $obj->last_event['step_num'] + 1);
            $block = KBDraftView_common::getAssigneeBlock($assignees, $this, $manager);
            $tpl->tplAssign('approval_notification_block', $block);
        }
        
        
        $tpl->tplAssign('do_confirm', $do_confirm);
        
        
        $step_str = sprintf('%d / %d', $obj->last_event['step_num']-1, count($obj->active_workflow['action']));
        $tpl->tplAssign('step', $step_str);
        
        $vars = $this->setCommonFormVars($eobj);
        
        $referer = WebUtil::serialize_url($vars['action_link']);
        $more['referer'] = $referer;
        $update_link = $this->getActionLink('update', $obj->get('id'), $more);
        $tpl->tplAssign('update_link', $update_link);
        
        $tpl->tplAssign($vars);
        //$tpl->tplAssign('action_link', $this->getActionLink('update'), $obj->get('id'));
        
        $tpl->tplAssign($eobj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getMenuBlock($obj, $manager, $view, $emanager) {
        return KBDraftView_common::getEntryMenu($obj, $manager, $view, $emanager);
    }
    
    
    function getApprovalHistory($obj) {
        $view = new KBDraftView_approval_history;
        $draft_manager = new KBDraftModel;
        $data = $view->execute($obj, $draft_manager);
        return $data;
    }
    
    
    function getAssigneeJsStr($assignees) {
        $js_str = array();
        
        foreach($assignees as $id => $assignee) {
            $name = PersonHelper::getEasyName($assignee);
            $name = addslashes($name);
            $js_str[] = sprintf('{id: %s, title: "%s"}', $id, $name);
        }
        
        return implode(',', $js_str);
    }
    
    
    function ajaxCancelHandler($cancel_link) {
        $objResponse = new xajaxResponse();
        
        $entry_id = (int) $this->obj->get('id');
        $this->manager->setEntryReleased($entry_id);
        
        $cancel_link = $this->controller->_replaceArgSeparator($cancel_link);
        $objResponse->addScript("location.href='{$cancel_link}';");
        
        return $objResponse;
    }
    
    
    function ajaxSaveAssignee($user_ids) {

        $objResponse = new xajaxResponse();

        $old_assignees = array();
        if($this->obj->assignee) {
            $old_assignees = $this->obj->assignee;
        }

        // email to new assignee
        $new_assignees = array_diff($user_ids, $old_assignees);
        if (!empty($new_assignees)) {
            $new_assignees = implode(',', $new_assignees);
            $step_comment = $this->obj->last_event['comment'];
            $sent = $this->manager->sendDraftReview($this->obj, $this->controller, $step_comment, $new_assignees);
        }

        // update assignee
        $record_id = (int) $this->obj->last_event['id'];
        $this->manager->deleteAssignees($this->obj->get('id'));
        $this->manager->saveAssignees($this->obj->get('id'), $record_id, $user_ids);
        
        $assignee_str = implode(',', $user_ids);
        
        // sphinx
        $assignee_mva = sprintf('(%s)', $assignee_str);
        AppSphinxModel::updateAttributes('assignee', $assignee_mva, $this->obj->get('id'), $this->manager->entry_type);
        
        $assignees = (empty($user_ids)) ? array() : $this->manager->getUser($assignee_str, false);
        $js_str = $this->getAssigneeJsStr($assignees);
        $objResponse->addScript(sprintf('assignees = [%s];', $js_str));

        // redirect if user delete yourself and no access now
        if(!$this->manager->isUserAllowedToApprove($user_ids)) {
            $link = $this->controller->getGoLink('success');
            $objResponse->addRedirect($link);
        }

        $objResponse->addScript('$("#assignee_buttons").hide();');

        // growl
        $msg = AppMsg::getMsgs('after_action_msg.ini', false, 'data_updated');
        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        $growl_cmd = sprintf($growl_cmd, '', $msg['body']);
        $objResponse->script($growl_cmd);

        return $objResponse;
    }
}
?>