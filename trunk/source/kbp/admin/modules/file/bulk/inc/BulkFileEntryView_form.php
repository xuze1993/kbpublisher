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

require_once 'core/common/CommonEntryView.php';
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryView_form.php';


class BulkFileEntryView_form extends FileEntryView_form
{
    
    var $template = 'form.html';
    
    
    // function execute(&$obj, &$manager, $draft_manager) {
    function execute(&$obj, &$manager, $data = array()) {
        
        $draft_manager = $data['draft_manager'];
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $this->controller->module));
        
        // categories
        $cat_records = $manager->getCategoryRecords();
        $add_option = $this->priv->isPriv('insert', 'file_category');
        $more = array('popup' => 1, 'field_id' => 'selHandler', 'autosubmit' => 1);
        $referer = WebUtil::serialize_url($this->getLink('file', 'file_entry', false, 'category', $more));
        $tpl->tplAssign('category_block_search_tmpl', 
            CommonEntryView::getCategoryBlockSearch($manager, $cat_records, $add_option, $referer, 'file', 'file_category'));
        
        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, 'file', 'file_bulk'));
        
        $select = new FormSelect();
        $select->select_tag = false;        
        
        // status
        $cur_status = false;
        $range = $manager->getListSelectRange('file_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;
        
        $select->resetOptionParam();
        $select->setRange($range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));        
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm2'));
        
        $author = $obj->getAuthor();
        if ($author) {
            $tpl->tplSetNeeded('/author');
            
            $tpl->tplAssign('author_id', $author['id']);
            $tpl->tplAssign('name', $author['last_name'] . ' ' . $author['first_name']); 
        }
        
        // user link
        $more = array('filter[priv]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);   

        // roles
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);
        
        // custom field
        $this->parseCustomField($tpl, $xajax, $obj, $manager, $cat_records);        
        
        // schedule
        $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range));    
    
        //files ajax
        $xajax->registerFunction(array('getFileList', $this, 'ajaxGetFileList'));
        
        
        // buttons
        if ($this->priv->isPrivOptional('insert', 'draft')) {
            
            $workflow = $draft_manager->getAppliedWorkflow();
            $submission_block = '';
            if ($workflow) {
                $submission_block = $this->getSubmissionBlock();
            }
            
            $tpl->tplAssign('submission_block', $submission_block);
            
        } else {
            
            $tpl->tplSetNeeded('/file_button');
            $tpl->tplSetNeeded('/status');
        }
        
        if ($obj->get('directory')) {
            $files = $this->getFileListBlock($obj->get('directory'));
            $tpl->tplAssign('file_list_block', $files);
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFileList($files, $entries) {
        
        $tpl = new tplTemplatez($this->template_dir . 'file_list.html');
        
        $entry_ids = array();
        $indexes_to_ids = array();
        foreach ($entries as $k => $entry) {
            $fname = (!empty($entry['filename_disk'])) ? $entry['filename_disk'] : $entry['filename'];
            $entry_ids[$entry['id']] = $entry['directory'] . $fname;
            $indexes_to_ids[$entry['id']] = $k; 
        }
        
        $i = 0;
        foreach($files as $v) {
            $a['filename'] = $v;
            $a['disabled_str'] = '';
            $a['color'] = 'black';
            
            $a['checked'] = '';
            if (!empty($_POST['files']) && in_array($v, $_POST['files'])) {
                $a['checked'] = 'checked';
            }
            
            $entry_id = array_search($v, $entry_ids);
            if ($entry_id !== false) {
                $tpl->tplSetNeeded('row/entry_link');
                
                $more = array('id' => $entry_id);
                $a['entry_link'] = $this->getLink('file', 'file_entry', false, 'detail', $more);
                $a['entry_id'] = $entry_id;
                $a['disabled_str'] = 'disabled';
                $a['color'] = 'grey';
                
                $index = $indexes_to_ids[$entry_id];
                $a['date_formatted'] = $this->getFormatedDate($entries[$index]['date_posted'], 'datetime');   
            }
            
            $a['id'] = $i;
            $i++;
            
            $tpl->tplParse(array_merge($a, $this->msg), 'row');
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);        
    }
    
    
    function ajaxGetFileList($directory) {
        $files = $this->getFileListBlock($directory);
        
        $objResponse = new xajaxResponse();
        //$objResponse->addAlert($directory);
        $objResponse->addAssign("file_root", "innerHTML", $files);
        $objResponse->call('onFileListLoaded');
    
        return $objResponse;    
    }
    
    
    function getFileListBlock($directory) {
        if(APP_DEMO_MODE) {
            $msgs = AppMsg::getMsgs('after_action_msg.ini', false, 'not_allowed_demo');
            $files = BoxMsg::factory('error', $msgs);
        
        } elseif(empty($directory)) {
            $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
            $msg['title'] = false;
            $msg['body'] = $msgs['specify_dir_msg'];
            $files = BoxMsg::factory('error', $msg);
        
        } elseif(!is_dir($directory) || !is_readable($directory)) {
            $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
            $msg['title'] = false;
            $msg['body'] = $msgs['dir_not_readable_msg'];
            $files = BoxMsg::factory('error', $msg);
        
        } else {
            
            $files = $this->manager->readDirectory($directory);
            
            if($files) {
                $upload = $this->manager->getFileData($files[0]);
                $this->manager->setSqlParams(sprintf('AND directory = "%s"', $upload['directory']));
                
                // $filenames = array();
                // foreach ($files as $file) {
                    // $filenames[] = sprintf('"%s"', basename($file));
                // }
                
                // $this->manager->setSqlParams(sprintf('AND filename IN (%s)', implode(',', $filenames)));
                $entries = $this->manager->getRecords();
                
                $files = RequestDataUtil::stripVarsBadUtf($files, $this->encoding);
                $files = $this->getFileList($files, $entries);
            
            } else {
                $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
                $msg['title'] = false;
                $msg['body'] = $msgs['no_files_in_directory_msg'];
                $files = BoxMsg::factory('error', $msg);            
            }
        }
        
        return $files;
    }
    
    
    function ajaxValidateForm2($values) {
		$options = array('func'=>'getValidate2');
        return $this->ajaxValidateForm($values, $options);
    }
    
    
    function getSubmissionBlock() {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/draft/template/block_submission.html');
        
        $tpl->tplSetNeeded('/submission_block');
        $tpl->tplAssign('submission_title', $this->msg['send_approval_msg']);
        
        $tpl->tplSetNeeded('/comment');
        $tpl->tplAssign('step_comment', @$_POST['step_comment']);
        
        $tpl->tplAssign('button_value', $this->msg['send_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>