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


class KBDraftView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        list($eobj, $emanager) = $data;
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // tabs
        if ($obj->get('id')) {
            $tpl->tplAssign('menu_block', KBDraftView_common::getEntryMenu($obj, $manager, $this, $emanager));
        }
        
        $view = new KBEntryView_form;
        $view->draft_view = true;
        $view->show_required_sign = false;
        $view->page = 'kb_draft';
        
        if ($eobj->errors) {
            $obj->errors = $eobj->errors;
            $eobj->errors = false;
            
            $view->show_required_sign = true;
        }
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if (!$obj->sent_to_approval) { // if allowed to insert
            $tpl->tplSetNeeded('/no_approval');
            
            $allowed = false;
            $workflow = $manager->getAppliedWorkflow();
            
            if ($workflow) { // no matter what access
                $allowed = true;
                
            } elseif($this->priv->isPriv('insert', 'kb_entry')) {
                if(!$this->priv->isPrivOptional('insert', 'draft', 'kb_entry')) {
                    $allowed = true;
                }
            }
            
            if ($allowed) {
                $tpl->tplAssign('submission_block', $this->getSubmissionBlock($obj, $manager, $emanager, $workflow));
            }
            
        } else {
            if ($manager->isUserAllowedToApprove($obj->assignee)) {
                $tpl->tplSetNeededGlobal('approval');
            }
        }
        
        $tpl2 = $view->_executeTpl($eobj, $emanager);
        
        if ($obj->get('id')) {
            
            // info block
            CommonEntryView::parseInfoBlock($tpl2, $obj, $view);
            $tpl2->tplAssign('id', $obj->get('id'));
            
            if ($obj->get('entry_id')) {
                $tpl2->tplSetNeeded('/entry_id2');
                $tpl2->tplAssign('id2', $obj->get('entry_id'));
            }
        }
        
        if ($this->controller->action == 'insert' && $obj->get('entry_id')) {
            $tpl2->tplSetNeeded('/entry_id2');
            $tpl2->tplAssign('id2', $obj->get('entry_id'));
        }
        
        $tpl2->tplParse();
        $tpl->tplAssign('form_block', $tpl2->tplPrint(1));
        $tpl->tplAssign('related_templates', $view->getRelatedBlockTemplate());
       
        
        $draft_hidden = array();
        foreach($obj->hidden as $v) {
            $draft_hidden[$v] = $obj->properties[$v];
        }
        
        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/continue_update');
        }
        
        $vars = $this->setCommonFormVars($obj);
        $vars['hidden_fields'] = http_build_hidden(array('draft' => $draft_hidden));
        $vars['preview_link'] = $this->getActionLink('preview');        
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($this->msg);
        
        // referer
        $link = ($obj->get('entry_id')) ? array('entry', false, $obj->get('entry_id')) : // update as draft in client
                                          array('index', $eobj->get('category_id'));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], $link));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $this->emanager = $emanager;
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormEntry'));
        $xajax->registerFunction(array('validatePublish', $this, 'ajaxValidateFormPublish'));
        $xajax->registerFunction(array('getWorkflowBlock', $this, 'ajaxGetWorkflowBlock'));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getApprovalHistory($obj) {
        $view = new KBDraftView_approval_history;
        $draft_manager = new KBDraftModel;
        
        $data = $view->execute($obj, $draft_manager);
        return $data;
    }
    
    
    function getSubmissionBlock($obj, $manager, $emanager, $workflow) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/draft/template/block_submission.html');
        
        $author_block = false;
        $comment_block = false;
        
        if ($obj->get('author_id') && $obj->get('author_id') != $manager->user_id) {
            $author_block = true;
            $submission_title = $this->msg['publish_msg'];
        }
        
        if ($workflow) {
            $comment_block = true;
            $submission_title = $this->msg['send_approval_msg'];
        }
        
        
        if ($author_block || $comment_block) {
            $tpl->tplSetNeeded('/submission_block');
            $tpl->tplAssign('submission_title', $submission_title);
            
            if ($author_block) {
                $tpl->tplSetNeeded('/author');
                $tpl->tplAssign('button_value', $this->msg['publish_msg']);
                
                // $author_msg_key = ($obj->get('id')) ? 'draft_author_set2_msg' : 'draft_author_set_msg';
                $author_msg_key = ($obj->get('entry_id')) ? 'draft_author_set2_msg' : 'draft_author_set_msg';
                $tpl->tplAssign('author_hint', $this->msg[$author_msg_key]);
            }
            
            if ($comment_block) {
                $tpl->tplSetNeeded('/comment');
                $tpl->tplAssign('step_comment', @$_POST['step_comment']);
                $tpl->tplAssign('button_value', $this->msg['send_msg']);
            }
            
        } else {
            $tpl->tplSetNeeded('/publication_button');
        }
        
        $msg_key = ($obj->get('entry_id')) ? 'sure_draft_rewrite_msg' : 'sure_common_msg';
        $tpl->tplAssign('publish_alert', $this->msg[$msg_key]);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormEntry($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if ($this->obj->errors) {
            $objResponse->script("$('#tabs').tabs('option', 'active', 0);");            
        }
        
        return $objResponse;
    }
    
    
    function ajaxValidateFormPublish($values, $options = array()) {
        $this->obj = new KBEntry;
        $this->manager = new KBEntryModel;
        
        return $this->ajaxValidateForm($values, $options);
    }
    
    
    function ajaxGetWorkflowBlock($categories) {
        $objResponse = new xajaxResponse();
        
        $obj = new KBDraft;
        $eobj = new KBEntry;
        
        $options = array();
        
        if (!empty($_GET['id'])) {
            $data = $this->manager->getById($_GET['id']);
            $obj->set($data);
            
            $options['user_id'] = $data['author_id'];
        } else {
            $obj->set('author_id', AuthPriv::getUserId());
        }
        
        if ($categories) {
            $eobj->setCategory($categories);
            
        } else {
            $objResponse->assign('workflow_block', 'style.display' , 'none');
            return $objResponse;
        }
        
        $workflow = $this->manager->getAppliedWorkflow();
        if ($workflow) {
            $assignees = $this->manager->getAssignees($obj, $eobj, $this->emanager, $workflow, 1);
            
            $block = KBDraftView_common::getAssigneeBlock($assignees, $this, $this->manager);
            
            $objResponse->assign('workflow_block', 'style.display' , 'block');
            $objResponse->assign('workflow_block', 'innerHTML' , $block);
            
        } else { // some text that will not be approval?
            $objResponse->assign('workflow_block', 'style.display' , 'none');
        }
        
        return $objResponse;
    }
    
    
    
}
?>