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

require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraftView_form.php';


class FileDraftView_form extends KBDraftView_form
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        list($eobj, $emanager) = $data;
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // tabs
        if ($obj->get('id')) {
            $tpl->tplAssign('menu_block', FileDraftView_common::getEntryMenu($obj, $manager, $this, $emanager));
        }
        
        $view = new FileEntryView_form;
        $view->draft_view = true;
        $view->show_required_sign = false;
        $view->page = 'file_draft';
        
        if ($eobj->errors) {
            $obj->errors = $eobj->errors;
            $eobj->errors = false;
            
            $view->show_required_sign = true;
        }
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if (!$obj->sent_to_approval) {
            $allowed = false;
            $workflow = $manager->getAppliedWorkflow();
            
            if ($workflow) { // no matter what access
                $allowed = true;
                
            } elseif($this->priv->isPriv('insert', 'file_entry')) {
                if(!$this->priv->isPrivOptional('insert', 'draft', 'file_entry')) {
                    $allowed = true;
                }
            }
            
            if ($allowed) {
                $tpl->tplAssign('submission_block', $this->getSubmissionBlock($obj, $manager, $emanager, $workflow));
            }
        }
        
        $tpl2 = $view->_executeTpl($eobj, $emanager);
        
        
        $file_link = '';
        if ($obj->get('id')) {
            $more = array('id' => $obj->get('id'));
            $file_link = $this->getLink('file', 'file_draft', false, 'file', $more);
            
        } elseif ($eobj->get('id')) {
            $more = array('id' => $eobj->get('id'));
            $file_link = $this->getLink('file', 'file_entry', false, 'file', $more);
        }
        
        $tpl2->tplAssign('file_link', $file_link);
        
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
       
        
        $draft_hidden = array();
        foreach($obj->hidden as $v) {
            $draft_hidden[$v] = $obj->properties[$v];
        }
               
        
        if($this->controller->action == 'insert' && !$eobj->get('id')) {
            $this->msg['file_help_msg'] = '';
        }
        
        $vars = $this->setCommonFormVars($obj);
        $vars['hidden_fields'] = http_build_hidden(array('draft' => $draft_hidden));
        $vars['preview_link'] = $this->getActionLink('preview');
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($this->msg);
        
        // referer
        $link = array('files', $eobj->get('category_id'));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], $link));
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $this->view = $view;
        
        if ($this->controller->getMoreParam('entry_id')) {
            $more = array('entry_id' => $this->controller->getMoreParam('entry_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormFile'));
        $xajax->registerFunction(array('validatePublish', $this, 'ajaxValidateFormPublish'));

        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getApprovalHistory($obj) {
        
        $view = new KBDraftView_approval_history;
        $draft_manager = new FileDraftModel;
        
        $data = $view->execute($obj, $draft_manager);
        return $data;
    }
    
    
    function ajaxValidateFormFile($values, $options = array()) {
        $this->view->obj = $this->obj;
        return $this->view->ajaxValidateFormFile($values, $options);
    }
    
    
    function ajaxValidateFormPublish($values, $options = array()) {
        return $this->view->ajaxValidateFormFile($values, $options);
    }
}
?>