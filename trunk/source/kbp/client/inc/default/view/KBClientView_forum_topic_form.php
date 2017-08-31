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

require_once 'core/base/Controller.php';
require_once 'core/app/AppController.php';
require_once 'core/common/CommonEntryView.php';
require_once 'eleontev/Util/TimeUtil.php';


class KBClientView_forum_topic_form extends KBClientView_forum
{
    
	function &execute(&$manager) {
	    
		$this->meta_robots = false;
		$this->meta_title = ($this->msg_id == 'update') ? $this->msg['update_msg'] : $this->msg['add_entry_msg'];
		$this->parse_form = false;

        $this->nav_title = $this->meta_title;
        $action = $this->msg_id;
        
        if ($action == 'update') {
            $link = $this->getLink('topic', false, $this->entry_id);
            $this->nav_title = array(
                $link => $this->entry_obj->get('title'), sprintf('[%s]', $this->msg['update_msg'])
                );
        }
		
		$data = $this->getForm($manager, $this->meta_title, $action);
		
		return $data;		
	}
    
    
    function getForm($manager, $form_title, $action) {
        
        // $this->addMsg('common_msg.ini');
        // $this->addMsg('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . 'forum_form_topic.html');
        
        $tpl->tplAssign('num_files_upload', $this->num_files_upload);
        
        
        $ext = $manager->setting['file_allowed_extensions'];
        $js_allowed_ext = 'false';
        if(!empty($ext)) {
            foreach(array_keys($ext) as $i) {
                $ext[$i] = addslashes($ext[$i]);
            }
            $js_allowed_ext = "['" . implode("','", $ext) . "']";
        }
        
        $tpl->tplAssign('allowed_extension', $js_allowed_ext);
        
        
        if($action == 'update') {
            $action_link = $this->getLink('topic', $this->category_id, $this->entry_id, $action);
            $cancel_link = $this->getLink('topic', $this->category_id, $this->entry_id);
        } else {
            $action_link = $this->getLink('forums', $this->category_id, $this->entry_id, $action);
            $cancel_link = $this->getLink('forums', $this->category_id);
        }
        
        $tpl->tplAssign('action_link', $action_link);
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        // editor
        $message = $this->getEditor($this->entry_obj->getFirstMessage(), 'forum', 'message');
        $tpl->tplAssign('ckeditor', $message);
        
        $admin_path = $this->controller->getAdminJsLink();
        $tpl->tplAssign('admin_href', $admin_path);
        
        $tpl->tplAssign('cancel_link', $cancel_link);
        $tpl->tplSetNeeded('/cancel_btn');
        
        $hidden = array(
            'category' => $this->category_id,
            'date_posted' => $this->entry_obj->get('date_posted'),
            'date_updated' => '',
            'author_id' => $this->entry_obj->get('author_id'),
            'user_id' => $this->entry_obj->get('author_id'),
            'updater_id' => '');
            
        $tpl->tplAssign('hidden_fields', http_build_hidden($hidden));
        
        // sticky
        $ch = ($this->entry_obj->getSticky()) ? 1 : 0;
        $tpl->tplAssign('ch_sticky', $this->getChecked($ch));
        
        $sticky_options_display = ($ch) ? 'inline' : 'none';
        $tpl->tplAssign('sticky_options_display', $sticky_options_display);
        
        $timestamp = time() + 3600;
        if(strtotime($this->entry_obj->getStickyDate())) {
            $timestamp = strtotime($this->entry_obj->getStickyDate());
            $tpl->tplSetNeeded('/date_set');
        }
        
        $tpl->tplAssign($this->setDatepickerVars($timestamp));
        
        // tags
        if ($manager->getSetting('allow_forum_tags')) {
            $tags = $this->entry_obj->getTag();
            
            $link = $this->controller->getAdminRefLink('forum', 'forum_entry', false, 'tags');
            $tpl->tplAssign('tag_block', CommonEntryView::getTagBlock($tags, $link, array(), 1));
            
            $tpl->tplSetNeeded('/tag');
        }   
        
        //xajax
        $ajax = &$this->getAjax('entry_update');
        $ajax->emanager = &$this->emanager;
        
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('addTag', $ajax, 'addTag'));
        $xajax->registerFunction(array('getTags', $ajax, 'getTags'));
        
        
        // subscribe    
        if($manager->isSubscribtionAllowed('forum') && $action != 'update') {
            $ch = ($_POST && empty($_POST['subscribe'])) ? 0 : 1;
            $tpl->tplAssign('ch_subscribe', $this->getChecked($ch));
            $tpl->tplSetNeeded('/subscribe');
        }
        
        
        // attachments
        $attachments = $this->entry_obj->getAttachment();
        if (!empty($attachments) || $manager->getSetting('forum_allow_attachment')) {
            $tpl->tplSetNeeded('/attachment_block');
        }
        
        if (!empty($attachments)) {
            $this->parseAttachments($this->entry_obj->get(), $attachments, $tpl);
        }
        
        if ($manager->getSetting('forum_allow_attachment')) {
            $tpl->tplSetNeededGlobal('new_attachment');
            
            
            $from_disk = '<a href="#" onclick="$(\'#input_file\').click();return false;" style="cursor: pointer;color: #aaaaaa;">$1</a>';
            $choose_msg = preg_replace('/<a>(.*?)<\/a>/', $from_disk, $this->msg['drop_files_to_attach_msg']);
            
            $tpl->tplAssign('attachment_drop_file', $choose_msg);
            
            $choose_msg = str_replace('$1', $this->msg['drop_files_disabled_msg'], $from_disk);
            $tpl->tplAssign('attachment_drop_disabled', $choose_msg);
            
            
            $file_upload_url = $this->controller->_replaceArgSeparator($this->getLink('forums', false, false, 'file_upload'));
            $tpl->tplAssign('file_upload_url', $file_upload_url);
            
            $max_files_num = $manager->getSetting('file_num_per_post') - count($attachments);
            $tpl->tplAssign('max_files_num', $max_files_num);
            
            $tpl->tplAssign('class', ($max_files_num) ? '' : 'dz-max-files-reached');
            $tpl->tplAssign('dropzone_active_display', ($max_files_num) ? 'inline' : 'none');
            $tpl->tplAssign('dropzone_inactive_display', ($max_files_num) ? 'none' : 'inline'); 
            
            $max_file_size = $manager->getSetting('file_max_filesize') / 1024;
            $tpl->tplAssign('max_file_size', $max_file_size);
            
            if ($manager->getSetting('file_allowed_extensions')) {
                $allowed_extensions = sprintf("'.%s'",  implode(',.', $manager->getSetting('file_allowed_extensions')));
                
            } else {
                $allowed_extensions = 'false';
            }
            
            $tpl->tplAssign('allowed_extensions', $allowed_extensions);
        }
        
        
        $fd = $this->getFormData();
        $tpl->tplAssign('required_sign', $fd['required_sign']);
        
        $tpl->tplAssign('preview_link', $this->getLink('forum_preview', $this->category_id));
        
        $tpl->tplAssign('form_title', $form_title);
        $tpl->tplAssign($this->entry_obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>