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

require_once 'core/app/AppAjax.php';
require_once 'core/common/CommonEntryView.php';


class ForumEntryView_form extends AppView
{

	var $template = 'form.html';
    
    var $module = 'forum';
    var $page = 'forum_entry';
    
	
	function execute(&$obj, &$manager) {

		$this->addMsgPrepend('user_msg.ini');
		$this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

		$file = AppMsg::getModuleMsgFile('knowledgebase', 'form_help_msg.ini');
		$this->msg['external_help_msg'] = AppMsg::parseMsgsMultiIni($file, 'external_link');
	
		$tpl = new tplTemplatez($this->template_dir . $this->template);
		$tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $tpl->tplAssign('menu_block', ForumEntryView_common::getEntryMenu($obj, $manager, $this));
                                         
		// categories
		$cat_records = $manager->getCategoryRecords();
        $add_option = $this->priv->isPriv('insert', 'forum_category');
        $more = array('popup' => 1, 'field_id' => 'selHandler', 'autosubmit' => 1);
        $referer = WebUtil::serialize_url($this->getLink('this', 'this', false, 'category', $more));
        $tpl->tplAssign('category_block_search_tmpl', 
            CommonEntryView::getCategoryBlockSearch($manager, $cat_records, $add_option, $referer, $this->module, 'forum_category'));
        
		$cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        $options = array('limited' => true);
		$tpl->tplAssign('category_block_tmpl', 
		    CommonEntryView::getCategoryBlock($obj, $manager, $categories, $this->module, $this->page, $options));
		
		$select = new FormSelect();
		$select->select_tag = false;
		
		// status
		$cur_status = ($this->controller->action == 'update') ? $obj->get('active') : 0; 
		$range = $manager->getListSelectRange('forum_status', true, $cur_status);
		$range = $this->getStatusFormRange($range, $cur_status);
		$status_range = $range;

		$select->resetOptionParam();
		$select->setRange($range);
		$tpl->tplAssign('status_select', $select->select($obj->get('active')));		
		
        // first comment
        if ($this->controller->action == 'insert') {
            $tpl->tplSetNeeded('/first_comment');
        }
		
		
		//attachment		
		foreach($obj->getAttachment() as $id => $filename) {
			$data = array('attachment_id'=>$id, 'filename'=>$filename);
			$data['delete_msg'] = $this->msg['delete_msg'];
			$data['sure_common_msg'] = $this->msg['sure_common_msg'];
			$data['insert_as_link_msg'] = $this->msg['insert_as_link_msg'];
			$tpl->tplParse($data, 'attachment_row');
		}
		
		//xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
            
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        // tag
        $this->parseTagBlock($tpl, $xajax, $obj);
        
        // private
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);        
        		
		// url title
		//$xajax->registerFunction(array('populateUrlTile', $this, 'ajaxPopulateUrlTile'));
		
		
        // info
        if($this->controller->action == 'update') {
            $this->parseInfoBlock($tpl, $obj);
            
            $publish_status_ids = $manager->getEntryStatusPublished('forum_status');
            
            $a = array();
            $client_controller = &$this->controller->getClientController();
            if(in_array($obj->get('active'), $publish_status_ids)) {
                $link = $client_controller->getLink('topic', $obj->get('category_id'), $obj->get('id'));
                $tpl->tplAssign('entry_link', $link);
                $tpl->tplSetNeeded('/entry_link');
            }
            
            $tpl->tplAssign('hits', $obj->get('hits'));
            //$tpl->tplSetNeeded('/as_new');   
        }   

		// schedule
		//$tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range));
        
        // first message
        $tpl->tplAssign('message', $obj->getFirstMessage()); 
		
        
		// sticky
        $tpl->tplAssign('sticky_checked', ($obj->getSticky()) ? 'checked' : '');
        
        $sticky_date = $obj->getStickyDate();
        $timestamp = time() + 3600;
        if(strtotime($sticky_date)) {
            $timestamp = strtotime($sticky_date);
            $tpl->tplSetNeeded('/date_set');
        }
        
        $tpl->tplAssign($this->setDatepickerVars($timestamp));
        
		
		$tpl->tplAssign($this->setCommonFormVars($obj));
		$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
		$link = ($this->controller->action == 'update') ? array('entry', false, $obj->get('id')) : 
		                                                  array('index', $obj->get('category_id'));
		$tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], $link));
		
		$tpl->tplAssign($obj->get());
		$tpl->tplAssign($this->msg);
		
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
	
	
	// INFO // -------------------------------
	
	function parseInfoBlock(&$tpl, $obj) {
		CommonEntryView::parseInfoBlock($tpl, $obj, $this);
	}
    
    
    // TAG // ---------------------------

    function parseTagBlock(&$tpl, &$xajax, $obj) {
        $xajax->registerFunction(array('addTag', $this, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $this, 'ajaxGetTags'));
        
        $link = $this->getLink($this->module, $this->page, false, 'tags');
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link));
    }

    
    function ajaxAddTag($string) {
        return CommonEntryView::ajaxAddTag($string, $this->manager);
    }

    
    function ajaxGetTags($limit = false, $offset = 0) {
        return CommonEntryView::ajaxGetTags($limit, $offset, $this->manager);
    }


	// PRIVATE // ----------------------------
	
	function ajaxGetCategoryPrivateInfo($category_id, $category_title) {
        return PrivateEntry::ajaxGetCategoryPrivateInfo($category_id, $category_title, $this->manager);
	}
	
	
	function parsePrivateStuff(&$tpl, &$xajax, $obj, $manager) {
        $tpl->tplAssign('block_private_tmpl', 
            PrivateEntry::getPrivateEntryBlock($xajax, $obj, $manager, $this, $this->module, $this->page));
	}
	
	
	// SORT ORDER // ----------------------------
	
	function ajaxPopulateSortSelect($category_id, $title) {
	    return CommonEntryView::ajaxPopulateSortSelect($category_id, $title, $this->manager, $this->obj);
    }
	
	
	function getSortSelectRange($rows, $start_num, $entry_id = false, $show_more_top = false) {
		return CommonEntryView::getSortSelectRange($rows, $start_num, $entry_id, $show_more_top);
	}
	
	
	function getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax = false) {
		return CommonEntryView::getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax);
	}
	
    
    function ajaxGetNextCategories($mode, $val, $category_id) {
        return CommonEntryView::ajaxGetNextCategories($mode, $val, $category_id, $this->manager);  
    }

	
	// URL TITLE // ----------------------------
	
	function ajaxPopulateUrlTile($title) {
		
		$objResponse = new xajaxResponse();
		
		
		//$objResponse->addAlert(1);
		//$search = array();
		//$search[] = ;
		
		//$replace = array();
		//$replace[] = ;
		
		//$title = preg_replace();
		
		
		$objResponse->addScript("var ut = document.getElementById('url_title');");
		$objResponse->addScript("ut.value = '$title'");			
	
		return $objResponse;	
	}
    
}
?>