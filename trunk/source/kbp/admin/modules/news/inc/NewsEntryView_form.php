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
require_once 'core/common/CommonCustomFieldView.php';


class NewsEntryView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $tpl = $this->_executeTpl($obj, $manager);
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function _executeTpl(&$obj, &$manager, $template = false) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $template_dir = APP_MODULE_DIR . 'news/template/';
        $template = ($template) ?  $template : $template_dir . $this->template;
        
        $tpl = new tplTemplatez($template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // tabs
        if ($obj->get('id')) {
            $tpl->tplAssign('menu_block', NewsEntryView_common::getEntryMenu($obj, $manager, $this));
        }
        
        // body
        $tpl->tplAssign('ckeditor', $this->getEditor($obj->get('body'), 'news'));        
        
        // roles
        $this->parsePrivateStuff($tpl, $obj, $manager);

        // datepicker
        $timestamp = time();
        if(strtotime($obj->get('date_posted'))) {
            if(isset($_POST['date_posted']) || isset($_GET['dkey'])) { // error, autosave
                $timestamp = strtotime($obj->get('date_posted'));
                
            } else { // update
                $timestamp = DatePicker::toUnixDate($obj->get('date_posted')); // not sure we need this
            }
        }
        
        $tpl->tplAssign($this->setDatepickerVars($timestamp));
        
        // schedule
        $status[0] = $this->msg['status_not_published_msg'];
        $status[1] = $this->msg['status_published_msg'];
        if(!$this->priv->isPriv('status')) {
            if($this->controller->action == 'insert') {            
                unset($status[1]);
            } else {
                $st = ($obj->get('active')) ? 0 : 1;
                unset($status[$st]);
            }
        }
        
        $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status));
        
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
			
        // custom field
        $this->parseCustomField($tpl, $obj, $manager);
		
        // tag
        $this->parseTagBlock($tpl, $xajax, $obj);

        // auto save        
        if ($this->setting['entry_autosave']) {
            $xajax->registerFunction(array('autoSave', $this, 'ajaxAutoSave'));
            $xajax->registerFunction(array('deleteAutoSave', $this, 'ajaxDeleteAutoSave'));
            $tpl->tplAssign(CommonEntryView::getAutosaveValues($obj, $manager, $this));
            $tpl->tplSetNeeded('/auto_save');
        }

        // info
        if($this->controller->action == 'update') {
            $tpl->tplSetNeededGlobal('entry_id');
            
            $publish_status_ids = array(1);
            $client_controller = &$this->controller->getClientController();
            if(in_array($obj->get('active'), $publish_status_ids)) {
                $link = $client_controller->getLink('news', 0, $obj->get('id'));
                $tpl->tplAssign('entry_link', $link);
                $tpl->tplSetNeeded('/entry_link');
            }
            
            $tpl->tplAssign('detail_link', $this->getActionLink('detail', $obj->get('id')));
        }

        
        $tpl->tplAssign('preview_link', $this->getActionLink('preview'));
        
        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/continue_update');
        }
        
        
        // button text
        $button_text = $this->msg['save_msg'];
        $button_text_enabled = 0;
        
        if(in_array($this->controller->action, array('insert', 'clone'))) {
            $button_text_enabled = 1;
            $button_text = $this->msg['publish_msg'];
        }
        
        $tpl->tplAssign('button_text_enabled', $button_text_enabled);
        $tpl->tplAssign('button_text', $button_text);
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], array('news', false, $obj->get('id'))));
        $tpl->tplAssign($obj->get());
        
        return $tpl;
    }
    
    
    function &getDateTimeSelect($timestamp) {
        
        require_once 'eleontev/HTML/DatePicker.php';
        
        // dates
        $picker = new DatePicker();
        $picker->setFormName('trigger_form');        // set form name
        $picker->setFormMethod($_POST);                // set form method
        $picker->setSelectName('date_posted');
        
        $picker->setYearRange(2008-1, date('Y')+2);
        $picker->setDate($timestamp);
        
        //$date = $picker->js();                    // js function
        //$tpl->tplAssign('js_date_select', $date);
        
        $date = $picker->day();                    // select tag with days
        $date .= $picker->month();                // select tag with mohth
        $date .= $picker->year();                // select tag with years
                
        return $date;    
    }    
    
    
    function parsePrivateStuff(&$tpl, $obj, $manager) {
        $xajax = false;
        $tpl->tplAssign('block_private_tmpl', 
            PrivateEntry::getPrivateEntryBlock($xajax, $obj, $manager, $this, 'news', 'news_entry'));
    }
    

    // AUTOSAVE // ----------------------------    
    
    function ajaxAutoSave($data, $id_key) {
        $obj = new NewsEntry;
        return CommonEntryView::ajaxAutoSave($data, $id_key, $obj, $this, $this->manager);   
    }
    
        
    function ajaxDeleteAutoSave($id_key) {
        return CommonEntryView::ajaxDeleteAutoSave($id_key, $this->manager);
    }


    // TAG // ---------------------------

    function parseTagBlock(&$tpl, &$xajax, $obj) {
        $xajax->registerFunction(array('addTag', $this, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $this, 'ajaxGetTags'));
        
        $link = $this->getLink('news', 'news_entry', false, 'tags');
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link));
    }

    
    function ajaxAddTag($string) {
        return CommonEntryView::ajaxAddTag($string, $this->manager);
    }

    
    function ajaxGetTags($limit = false, $offset = 0) {
        return CommonEntryView::ajaxGetTags($limit, $offset, $this->manager);
    }
	
	
    // CUSTOM // ---------------------------   
    
    function parseCustomField(&$tpl, $obj, $manager) {
        
        $rows = $manager->cf_manager->getCustomField();
        $use_default = ($this->controller->action != 'update' && empty($this->controller->rp->vars));
        
        $tpl->tplAssign('custom_field_block_bottom', 
            CommonCustomFieldView::getFieldBlock($rows, $obj->getCustom(), $manager->cf_manager, $use_default));        
    }

}
?>