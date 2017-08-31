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
require_once 'core/app/AppAjax.php';


class ForumEntryView_list extends AppView
{
	
	var $template = 'list.html';
	var $template_popup = 'list_popup.html';
    var $template_popup_text = 'list_popup_text.html';
	var $template_popup_review = 'list_popup_review.html';
	var $child_categories = array();
	
	
	function execute(&$obj, &$manager) {
		
		$this->addMsgPrepend('user_msg.ini');
		$this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

		$tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        if($this->controller->getMoreParam('popup') == 'text') {
            $tmpl = $this->template_popup_text;
        }
        
		if($this->controller->getMoreParam('popup') == 2) {
			$tmpl = $this->template_popup_review;
		}

		
		$tpl = new tplTemplatez($this->template_dir . $tmpl);
		$tpl->tplAssign('msg', $this->getShowMsg2());
		
		// check 
		$update_allowed = true;
		$bulk_allowed = array();		
		
		
		// bulk
		$manager->bulk_manager = new ForumEntryModelBulk();
		if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
			$tpl->tplSetNeededGlobal('bulk');
			$tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'ForumEntryView_bulk', $this));
		}		
		
		         
		// status_msg
		$status = $manager->getEntryStatusData('forum_status');
		
		// filter sql
		$categories = $manager->getCategoryRecords();
		$params = $this->getFilterSql($manager, $categories);
		$manager->setSqlParams($params['where']);
		$manager->setSqlParamsSelect($params['select']);
		$manager->setSqlParamsFrom($params['from']);
        
        
		// category roles
		// probably we should not apply it in pop up window
		$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
		
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('featureArticle', $this, 'ajaxStickTopic'));
        
		// header generate
		$bp = &$this->pageByPage($manager->limit, $manager->getCountRecords());
		$tpl->tplAssign('header', 
			$this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), false));
		
		// sort generate
		$sort = &$this->getSort();
		$sort_order = $sort->getSql();
		$manager->setSqlParamsOrder($sort_order);
		
		// set force index date_updated
		if(strpos($sort_order, 'date_updated') !== false) {
			//$manager->entry_sql_force_index = 'FORCE INDEX (date_updated)';
		}

		// get records
		$rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
		$ids = $manager->getValuesString($rows, 'id'); 	
		
		// categories
		$entry_categories = ($ids) ? $manager->getCategoryByIds($ids) : array();
		$entry_categories = $this->stripVars($entry_categories);
		
		$full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
		$full_categories = $this->stripVars($full_categories);
        
        // messages
        $messages_num = $manager->getCountEntryMessages();

		// users
		$author_ids = $manager->getValuesArray($rows, 'author_id');
		$updater_ids = $manager->getValuesArray($rows, 'updater_id');
		$users = array();
		if($author_ids || $updater_ids) {
			$users = implode(',', array_unique(array_merge($author_ids, $updater_ids)));
			$users = $manager->getUser($users, false);
            $users = $this->stripVars($users);
		}

		// roles to entry
		$roles_range = $manager->getRoleRangeFolow();
		
		//$roles = ($ids) ? $manager->getRoleReadById($ids, 'id_list') : array();
		//$roles = &$this->parseEntryRolesMsg($roles, $roles_range);
		
        $category_ids = $manager->getValuesString($rows, 'category_id', true);
        $category_roles = ($category_ids) ? $manager->cat_manager->getRoleById($category_ids, 'id_list') : array();
        $category_roles = $this->parseEntryCategoryRolesMsg($category_roles, $roles_range);   
        
		
		// schedule
		$schedule = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();
		
		$client_controller = &$this->controller->getClientController();
		$publish_status_ids = $manager->getEntryStatusPublished('forum_status');
		
		
		// list records
		foreach($rows as $row) {
			
			$obj->set($row);	
			
            $title = $obj->get('title');
            $tpl->tplAssign('short_title', $this->getSubstringStrip($title, 30));
            // $tpl->tplAssign('escaped_title', $this->getSubstringJsEscape($title, 100));// for popup window
		
            $tpl->tplAssign('messages', $messages_num[$obj->get('id')]);
			$tpl->tplAssign('status', $status[$row['active']]['title']);
			$tpl->tplAssign('color', $status[$row['active']]['color']);
			
			// attachments
			$attachment_num = '--';
			if(isset($attachments_num[$row['id']])) {
				$n = $attachments_num[$row['id']];
				$str = '<a href="%s">%s</a>';
				$link = $this->getLink('file', 'file_entry', false, false, array('filter[q]'=>'attached2:'.$row['id']));
				$attachment_num = sprintf($str, $link, $n);
			} 
			$tpl->tplAssign('attachment_num', $attachment_num);
			
			// dates & user
			$user = (isset($users[$row['author_id']])) ? $users[$row['author_id']] : array();
			$formated_date_posted_full = $this->parseDateFull($user, $row['ts']);		
			
			$tpl->tplAssign('formated_date_posted', $this->getFormatedDate($row['ts']));
			$tpl->tplAssign('formated_date_posted_full', $formated_date_posted_full);			
			
			$formated_date_updated = '--';
			$formated_date_updated_full = '';
			$ddiff = $row['tsu'] - $row['ts'];
			if($ddiff > $manager->update_diff) {
				$user = (isset($users[$row['updater_id']])) ? $users[$row['updater_id']] : array();
				$formated_date_updated_full = $this->parseDateFull($user, $row['tsu']);		
				$formated_date_updated = $this->getFormatedDate($row['tsu']);
			}
			
			$tpl->tplAssign('formated_date_updated', $formated_date_updated);
			$tpl->tplAssign('formated_date_updated_full', $formated_date_updated_full);			
			
			// full categry link
			$cat_nums = count($entry_categories[$obj->get('id')]);
			$tpl->tplAssign('category_num', ($cat_nums > 1) ? "[$cat_nums]" : '');
			
            // category
            $cat_nums = count($entry_categories[$obj->get('id')]);
            $tpl->tplAssign('num_category', ($cat_nums > 1) ? "[$cat_nums]" : '');
            $tpl->tplAssign('category', $this->getSubstringSignStrip($row['category_title'], 20));
            
            $more = array('filter' => array('c' => $row['category_id']));
            $tpl->tplAssign('category_filter_link', $this->controller->getLink('all', '', '', '', $more));
            
            // full categories
            $_full_categories = array();
            $first_row = true;
            foreach(array_keys($entry_categories[$obj->get('id')]) as $cat_id) {
                $_full_categories[] = ($first_row) ? sprintf('<b>%s</b>', $full_categories[$cat_id]) : $full_categories[$cat_id];
                $first_row = false;
            }
            $tpl->tplAssign('full_category', implode('<br />',  $_full_categories));
			
			
			// public link
			if(in_array($obj->get('active'), $publish_status_ids)) {
				$entry_link = $client_controller->getLink('topic', $obj->get('category_id'), $obj->get('id'));
                
			} else {
				$entry_link = sprintf("javascript:confirmNotPublishedEntry('%s', '%s');", 
				                      $this->msg['confirm_not_published_entry_msg'],
				                      $this->getActionLink('update', $obj->get('id')));				
			}
			$tpl->tplAssign('entry_link', $entry_link);
            
            $tpl->tplAssign('view_link', $this->getLink('forum', 'forum_entry', false, 'detail', array('id'=>$row['id'])));   
			
			
			// private&roles
			if($row['private'] || $row['category_private']) {
				$tpl->tplAssign('roles_msg', $this->getEntryPrivateMsg(@$roles[$obj->get('id')], 
				                             @$category_roles[$obj->get('category_id')]));
				$tpl->tplAssign($this->getEntryColorsAndRolesMsg($row));
				$tpl->tplSetNeeded('row/if_private');
			}
			
      
			// schedule
			if(isset($schedule[$obj->get('id')])) {
				$tpl->tplAssign('schedule_msg', $this->getScheduleMsg($schedule[$obj->get('id')], $status));
				$tpl->tplSetNeeded('row/if_schedule');
			}
            
            // sticky
            if ($row['is_sticky']) {
                $new_value = 0;
                $assign_display = 'none';
                $remove_display = 'block';

            } else {
                $new_value = 1;
                $assign_display = 'block';
                $remove_display = 'none';
            }
            
            $tpl->tplAssign('new_value', $new_value);
            $tpl->tplAssign('assign_display', $assign_display);
            $tpl->tplAssign('remove_display', $remove_display);
            
			
			$tpl->tplAssign($this->getViewListVarsJsCustom($obj->get('id'), 
												   $obj->get('active'), 
												   $obj->get(),
												   $manager, 
												   $entry_categories[$obj->get('id')],
												   $update_allowed
												   ));
			
            $tpl->tplAssign('sticky', ($row['is_sticky'] ? '<li></li>' : ''));
            									   
			$tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
			//$tpl->tplParse($obj->get(), 'row');
		}
		
        if ($this->controller->getMoreParam('popup')) {
            $menu_msg = AppMsg::getMenuMsgs('forum');
            $tpl->tplAssign('popup_title', $menu_msg['forum_entry']);
        }
		
		$tpl->tplAssign($this->msg);
		$tpl->tplAssign($sort->toHtml());
		$tpl->tplAssign($this->parseTitle());
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
	
	
	function getViewListVarsJsCustom($id = false, $active = false, $values, $manager, $categories, $update_allowed) {
		
		$own_record = ($values['author_id'] == $manager->user_id);
		
        $row = parent::getViewListVarsJs($id, $active, $own_record, array('detail', 'update', 'delete'));
        
		// if some of categories is private
		// and user do not have this role so he can't update it
		$has_private = $manager->isCategoryNotInUserRole(array_keys($categories));
		if($has_private) {
			$row['update_link'] = false;
			$row['update_img'] = false;			
			
			$row['delete_link']	= false;
			$row['delete_img']	= false;
			
			$row['bulk_ids_ch_option'] = 'disabled';
		}
        
        $row['view_link'] = $this->getActionLink('detail', $id);
        $row['view_img'] = $this->getImgLink($row['view_link'], 'load', $this->msg['view_msg']);
		
		return $row;
	}
	
	
	function parseTitle() {
		$values = array();
		$values['comment_num_msg'] = $this->shortenTitle($this->msg['comment_num_msg'], 3);
		$values['attachment_num_msg'] = $this->shortenTitle($this->msg['attachment_num_msg'], 3);
		return $values;
	}	
	
	
	function &getSort() {
		
		//$sort = new TwoWaySort();
		$sort = new OneWaySort($_GET);
		$sort->setDefaultOrder(2);
		$sort->setCustomDefaultOrder('title', 1);
		$sort->setCustomDefaultOrder('sort_oder', 1);
        
        $sort->setDefaultSortItem('dateu', 2);
		
		$sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
		$sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);		
		
        $sort->setSortItem('date_updated_msg', 'dateu', 'date_updated', $this->msg['updated_msg']);
		$sort->setSortItem('date_posted_msg',  'datep', 'date_posted',  $this->msg['posted_msg']);
		$sort->setSortItem('entry_title_msg',  'title', 'title',        $this->msg['entry_title_msg']);
		
		$sort->setSortItem('id_msg', 'id', 'e.id', $this->msg['id_msg']);
		$sort->setSortItem('hits_num_msg', 'hits', 'hits', array($this->msg['hits_num_msg'], 5));
		$sort->setSortItem('entry_status_msg','status', 'active', array($this->msg['entry_status_msg'], 6));
		
		$sort->setSortItem('category_msg', 'cat', 'e.category_id', $this->msg['category_msg']);
        
		// search
		if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
			$f = $_GET['filter']['q'];
			if(!$this->isSpecialSearchStr($f)) {
				$sort->resetDefaultSortItem();
				$sort->setSortItem('search', 'search', 'score', '', 2);			
			}
		}
		
		//echo '<pre>', print_r($sort->getSql(), 1), '</pre>';
		return $sort;
	}
	
	
	function getFilter($manager, $categories) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        
        $categories = $manager->getCategorySelectRangeFolow($categories); // private removed
                
        // category
        if(!empty($values['c'])) {
            $category_id = (int) $values['c'];
            $category_name = $this->stripVars($categories[$category_id]);
            $tpl->tplAssign('category_name', $category_name);
        } else {
            $category_id = 0;
        }
        
        $tpl->tplAssign('category_id', $category_id);

        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }
   
        $js_hash = implode(",\n", $js_hash);         
        $tpl->tplAssign('categories', $js_hash);
        
        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));
        
        

        $select = new FormSelect();
        $select->select_tag = false;
        
        
        // sticky
        $range = array(
            'all' => '__',
            '1' => $this->msg['yes_msg'],
            '0' => $this->msg['no_msg']);
        $select->setRange($range);            
        @$sticky = $values['sticky'];
        $tpl->tplAssign('sticky_select', $select->select($sticky));
        
        
        // status
        $select->setRange($manager->getListSelectRange('forum_status', false),
                          array('all'=>'__'));
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
	}
	
	
	function getFilterSql(&$manager, $categories) {
		
		// filter
		$arr = array();
		$arr_select = array();
		$arr_from = array();
		@$values = $_GET['filter'];
		
		// category
		@$v = $values['c'];
		@$v2 = $values['ct'];
		if($v2 != 'all' && !empty($v)) {
			$category_id = (int) $v;
			
			if(!empty($values['ch'])) {
				$child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
				$child = implode(',', $child);
				$arr[] = "AND cat.id IN($child)";			
			} else {
				$arr[] = "AND cat.id = $category_id";
			}
			
			$manager->select_type = 'category';
			//$this->child_categories = &$child_arr;
		}
		
		
		// status
		@$v = $values['s'];
		if($v != 'all' && isset($values['s'])) {
			$v = (int) $v;
			$arr[] = "AND e.active = '$v'";
		}

        // sticky
        @$v = $values['sticky'];
        if($v != 'all' && isset($values['sticky'])) {
            $condition = ($v) ? 'IS NOT NULL' : 'IS NULL';
            $arr[] = "AND f.id " . $condition;
        }
		
		
		// search str
		@$v = $values['q'];
		if(!empty($v)) {
			$v = trim($v);

			if($ret = $this->isSpecialSearchStr($v)) {
				
				if($sql = CommonEntryView::getSpecialSearchSql($manager, $ret, $v)) {
					$arr[] = $sql['where'];
					if(isset($sql['from'])) {
						$arr_from[] = $sql['from'];
					}
					
				} elseif ($ret['rule'] == 'attachment') {
					$type = strpos($v, 'inline') ? '2,3' : '1,2,3';
					$type = strpos($v, 'attached') ? '1' : $type;

					$related = $manager->getEntryToAttachment($ret['val'], $type);
					$related = ($related) ? implode(',', $related) : "'no_attachment'";
					$arr[] = sprintf("AND e.id IN(%s)", $related);
				}
			
			} else {
				$v = addslashes(stripslashes($v));
				$arr_select[] = "MATCH (e.title) AGAINST ('$v') AS score";
				$arr[]  = "AND MATCH (e.title) AGAINST ('$v' IN BOOLEAN MODE)";
			}
		}
			
		
		//echo '<pre>', print_r($arr, 1), '</pre>';
		$arr['where'] = implode(" \n", $arr);
		$arr['select'] = implode(" \n", $arr_select);
		$arr['from'] = implode(" \n", $arr_from);
		
		return $arr;
	}
	
	
	// if some special search used
	function isSpecialSearchStr($str) {
		
		if($ret = parent::isSpecialSearchStr($str)) {
			return $ret;
		}
		
		$search = CommonEntryView::getSpecialSearchArray();
		
		// get all articles that have link to file (where attachment_id = '[attached:id]')
		$search['attachment'] = "#^attachment(?:-inline|-attached|-all)?:(\d+)$#";
		
		return $this->parseSpecialSearchStr($str, $search);
	}	
	
	
	function getShowMsg2() {
		@$key = $_GET['show_msg2'];
		if($key == 'note_remove_reference') {
			@$r = $this->isSpecialSearchStr($_GET['filter']['q']);
			$vars['article_id'] = $r['val'];
			$vars['delete_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'delete', array('id' => $r['val']));
			
			$file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
			$msgs = AppMsg::parseMsgsMultiIni($file);
			$msg['title'] = $msgs['title_remove_references'];
			$msg['body'] = $msgs['note_remove_reference'];
			return BoxMsg::factory('error', $msg, $vars);
		
		} elseif ($key == 'note_remove_reference_bulk') {
			$file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
			$msgs = AppMsg::parseMsgsMultiIni($file);
			$msg['title'] = $msgs['title_remove_references_bulk'];
			$msg['body'] = $msgs['note_remove_reference_bulk'];
			return BoxMsg::factory('error', $msg);			
		}
	}
  
	function getScheduleMsg($data, $status) {
		return CommonEntryView::getScheduleMsg($data, $status, $this);
	}	

	function getSortOrderSetting($setting = false) {
		return CommonEntryView::getSortOrderSetting($setting);
	}
		
	function parseDateFull($user, $date) {
		return CommonEntryView::parseDateFull($user, $date, $this);
	}
	
	function parseEntryCategoryRolesMsg($roles, $roles_range) {
		return CommonEntryView::parseEntryCategoryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
	}
	
	function &parseEntryRolesMsg($roles, $roles_range) {
		return CommonEntryView::parseEntryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
	}	
	
	function getEntryPrivateMsg($entry_roles, $category_roles) {
        return CommonEntryView::getEntryPrivateMsg($entry_roles, $category_roles, $this->msg);
	}
	
	function getEntryColorsAndRolesMsg($row) {
		return CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg);
	}
    

	// Filter // -----------

	function parseCategoryFilter(&$tpl, &$manager, $categories) {
        //return CommonEntryView::parseCategoryFilter($tpl, $manager, $categories, $this);
    }
	
    function ajaxGetChildCategories($id) {
    	return CommonEntryView::ajaxGetChildCategories($id);
	}
    
    function ajaxStickTopic($id, $category_id, $value) {
        $objResponse = new xajaxResponse();
        
        if ($value) {
            $this->manager->deleteFeaturedEntry($id);
            $this->manager->increaseFeaturedEntrySortOrder($category_id);
            $this->manager->saveFeaturedEntry($id);
            
            $new_value = 0;
            $show_class = 'featured_img_remove';
            $hide_class = 'featured_img_assign';
        
        } else {
            $new_value = 1;
            $show_class = 'featured_img_assign';
            $hide_class = 'featured_img_remove';
            
            $this->manager->deleteFeaturedEntry($id);
        }
        
        $ajax = sprintf("xajax_featureArticle(%s, '%s', '%s'); return false;", $id, $category_id, $new_value);
        
        $objResponse->script(sprintf('$("#featured_link_%s").attr("onclick", "%s");', $id, $ajax));
        $objResponse->script(sprintf('$("#featured_link_%s").find("img.%s").show();', $id, $show_class));
        $objResponse->script(sprintf('$("#featured_link_%s").find("img.%s").hide();', $id, $hide_class));
        
        return $objResponse;
    }
}
?>