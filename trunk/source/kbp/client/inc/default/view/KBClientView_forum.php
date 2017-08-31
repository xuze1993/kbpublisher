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

require_once 'core/app/BanModel.php';


class KBClientView_forum extends KBClientView_common
{
    
    var $num_files_upload = 10;
    
    var $image_mime_types = array(
        'image/gif',
        'image/jpeg',
        'image/png'
    );
     

    function &getLeftMenu($manager) {
        return $this->getSwitchMenu($manager);
    }

    
    function &getSwitchMenu($manager, $parent_id = 0) {
        
        $template_dir = $this->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->getTemplate('forum_switch_menu.html', $template_dir));
        
        $ajax = &$this->getAjax('category_filter');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getChildForums', $ajax, 'ajaxGetChildCategories'));
        
		$top_cat_id = 0;
        if($this->category_id) {
            $top_cat_id = TreeHelperUtil::getTopParent($manager->categories, $this->category_id);
        }
            
        $tpl->tplAssign('filter_select', $this->_getCategoryFilterSelect($manager, false));
        
        if ($top_cat_id) {
            $range_ = $manager->getCategorySelectRange($manager->categories, $top_cat_id);
    
            $range = array();
            if(isset($manager->categories[$top_cat_id])) {
                $range[$top_cat_id] = $manager->categories[$top_cat_id]['name'];
            }
    
            if (!empty($range_)) {
                foreach (array_keys($range_) as $cat_id) {
                    $range[$cat_id] = '-- '. $range_[$cat_id];
                }
            }
    
            $select = new FormSelect();
            $select->select_tag = false;
            $select->setRange($range);
            
            //$tpl->tplAssign('category_select', $select->select($this->category_id));
            //$display = (empty($range_)) ? 'none' : 'block';
            $display = 'none';
            
        } else {
            $display = 'none';
        }

        $tpl->tplAssign('category_filter_display', $display);
       
        $tpl->tplAssign('page', $this->getLink('forums'));
        $tpl->tplAssign('rewrite', ($this->controller->mod_rewrite) ? 1 : 0);
       
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getBannedMessage() {

        $m = new HintMsg;
        $m->img = false;
        
        $error_str = '%s. %s: %s';
        $msg = array();
        $msg['body'] = sprintf($error_str, 
            $this->msg['forum_post_ban_msg'], 
            $this->msg['ban_reason_msg'], 
            $banned['user_reason']);
        
        $m->setMsgs($msg);
        return $m->getHtml();
    }
    
    
    function getForm($manager, $title, $entry_page = false) {
        
        if (!$manager->is_registered) {
            return '';
        }
        
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tmpl = ($entry_page) ? 'forum_form_message.html' : 'forum_form_message_collapsed.html';
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        $tpl->tplAssign('num_files_upload', $this->num_files_upload);
        
        $this->addMsg('common_msg.ini', 'file');
        $size = WebUtil::getFileSize($manager->setting['file_max_filesize']*1024);
        $tpl->tplAssign('file_size_max', $size);
        
        $ext = $manager->setting['file_allowed_extensions'];
        
        if (!is_array($ext)) {
            $ext = ($ext) ? explode(',', $ext) : array();
        }
        
        $js_allowed_ext = 'false';
        if(!empty($ext)) {
            foreach(array_keys($ext) as $i) {
                $ext[$i] = addslashes($ext[$i]);
            }
            $js_allowed_ext = "['" . implode("','", $ext) . "']";
        }
        
        $tpl->tplAssign('allowed_extension', $js_allowed_ext);
        
                                                                                   
        $action = ($this->msg_id == 'update') ? 'update' : 'post';
        $more = ($this->msg_id == 'update') ? array('id'=>$_GET['id']) : array();
        $link = $this->getLink('topic', false, $this->entry_id, $action, $more);
        $tpl->tplAssign('action_link', $link);        
        $tpl->tplAssign('user_msg', $this->getErrors());

        if (!empty($entry_page)) {
            $tpl->tplAssign('cancel_link', $this->getLink('topic', false, $this->entry_id));
        }
        

        // editor
        $form_data = $this->getFormData();
        $value = (empty($form_data['message'])) ? '' : $form_data['message'];
        $message = $this->getEditor($value, 'forum', 'message');
        $tpl->tplAssign('ckeditor', $message);
        
        $admin_path = $this->controller->getAdminJsLink();
        $tpl->tplAssign('admin_href', $admin_path);
        
        // attachments
        if ($manager->getSetting('forum_allow_attachment')) {
            $tpl->tplSetNeededGlobal('attachment');
            
            
            $from_disk = '<a href="#" onclick="$(\'#input_file\').click();return false;" style="cursor: pointer;color: #aaaaaa;">$1</a>';
            $choose_msg = preg_replace('/<a>(.*?)<\/a>/', $from_disk, $this->msg['drop_files_to_attach_msg']);
            
            $tpl->tplAssign('attachment_drop_file', $choose_msg);
            
            $choose_msg = str_replace('$1', $this->msg['drop_files_disabled_msg'], $from_disk);
            $tpl->tplAssign('attachment_drop_disabled', $choose_msg);
            
            
            $file_upload_url = $this->controller->_replaceArgSeparator($this->getLink('forums', false, false, 'file_upload'));
            $tpl->tplAssign('file_upload_url', $file_upload_url);
            
            $tpl->tplAssign('max_files_num', $manager->getSetting('file_num_per_post'));
            
            $max_file_size = $manager->getSetting('file_max_filesize') / 1024;
            $tpl->tplAssign('max_file_size', $max_file_size);
            
            if ($manager->getSetting('file_allowed_extensions')) {
                $allowed_extensions = sprintf("'.%s'",  implode(',.', $manager->getSetting('file_allowed_extensions')));
                
            } else {
                $allowed_extensions = 'false';
            }
            
            $tpl->tplAssign('allowed_extensions', $allowed_extensions);
        }
        
        // subscribe    
        if($manager->isSubscribtionAllowed('forum') && $action != 'update') {
            
            if ($_POST) { // submitted
                $ch = (empty($_POST['subscribe'])) ? 0 : 1;
                
            } else {
                $ch = $manager->isEntrySubscribedByUser($this->entry_id, 4);
            }
            
            $tpl->tplAssign('ch_subscribe', $this->getChecked($ch));
            $tpl->tplSetNeeded('/subscribe');
        }
        
        $tpl->tplAssign('preview_link', $this->getLink('forum_preview', $this->category_id, $this->entry_id));
        
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign('message_title', $title);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getCategoryList($title, $manager, $has_topics = true) {
        
        $rows = $manager->categories;
        
        if(!$rows) {
			$empty = '';
			return $empty; 
		}
        
        $use_sections = $manager->getSetting('forum_sections');
        $sections_tmpl = $this->mobile_view && !$this->category_id && $use_sections;
        
        $tmpl = ($sections_tmpl) ? 'forum_list_forum_sections.html' : 'forum_list_forum.html';  
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
		
        $cat_ids = $manager->getValuesArray($rows, 'id');
		$count_entries = $manager->countEntriesPerCategory(implode(',', $cat_ids));
        
        $post_ids = array();
        foreach ($count_entries as $cat_id => $v) {
            $post_ids[] = $v['last_category_post_id']; 
        }
        
        $last_posts = ($post_ids) ? $manager->getMessagesInfo($post_ids) : array();
                
        $tree_helper = $manager->getTreeHelperArray($manager->categories, $this->category_id);
        
        if(empty($tree_helper)) {
			$empty = '';
			return $empty; 
		}
        
        if ($sections_tmpl) {
            $this->_parseForumListMobile($manager, $tree_helper, $rows, $count_entries, $last_posts, $tpl);
            
        } else {
            $this->_parseForumList($manager, $tree_helper, $rows, $count_entries, $last_posts, $tpl);
        }

        if ($this->category_id) {
            if(empty($has_topics) && count($rows) > 1) {
                $options = array('subscribe');
                $tpl->tplAssign('block_list_option_tmpl', $this->getBlockListOption($tpl, $manager, $options));
            }
        }


        $tpl->tplAssign('list_title', $title);
        $tpl->tplAssign('meta_title', $this->meta_title);
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }
    
    
    function _parseForumList($manager, $tree_helper, $rows, $count_entries, $last_posts, &$tpl) {
        
        $padding = 15;
        $i = 0;
        
        $hidden_category_ids = $this->getUserPool('forum');
        $hide_next = false;
        
        $use_sections = $manager->getSetting('forum_sections');
        
        foreach(array_keys($tree_helper) as $cat_id) {
            $v1 = $rows[$cat_id];
            
            $v1['link'] = $this->getLink('forums', $cat_id);
            
            $level = $tree_helper[$cat_id];
            $v1['padding'] = $level * $padding;
            
            $is_section = ($level == 0 && !$this->category_id && $use_sections);
            
            $v1['description'] = nl2br($v1['description']);
            
            if (!empty($last_posts[$cat_id]) && !$is_section) {
                $tpl->tplSetNeeded('row/last_post');
                
                $v1['username'] = $last_posts[$cat_id]['username'];
                $v1['date_formatted'] = $this->getTimeInterval($last_posts[$cat_id]['date_posted']);
                $v1['topic_title'] = $last_posts[$cat_id]['title'];
                $v1['topic_link'] = $this->getLink('topic', false, $last_posts[$cat_id]['entry_id']);
            }
            
                
            $private = $this->isPrivateEntry(false, $v1['private']);
            $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'forum');
            
            $v1['num_entry'] = 0;
			if(!empty($count_entries[$v1['id']]['num_entry'])) {
				$v1['num_entry'] = $count_entries[$v1['id']]['num_entry'];
			}
			
			$v1['num_message'] = 0;
			if(!empty($count_entries[$v1['id']]['num_message'])) {
				$v1['num_message'] = $count_entries[$v1['id']]['num_message'];
			}
            
            $v1['class'] = ($i++ & 1) ? 'forumTrLighter' : 'forumTrDarker';
            $v1['style'] = (!$is_section && $hide_next) ? 'style="display: none;"' : '';
            
            if ($is_section) {
                $v1['class'] = 'forumSection';
                $v1['item_img'] = '';
                
                if (in_array($v1['id'], $hidden_category_ids)) {
                    $hide_next = true;
                    $v1['arrow_direction'] = 'down';
                    
                } else {
                    $hide_next = false;
                    $v1['arrow_direction'] = 'up';
                }
                
                $tpl->tplSetNeeded('row/section');
            } else {
                $tpl->tplSetNeeded('row/info');
            }
            
            $tpl->tplParse($v1, 'row');
        }
    }
    
    
    function _parseForumListMobile($manager, $tree_helper, $rows, $count_entries, $last_posts, &$tpl) {
        
        $padding = 15;
        $i = 0;
        
        $top_ids = array();
        $top_children = array();
        foreach($tree_helper as $id => $level) {
            if($level == 0) {
                $top_ids[] = $id;
                $top_id = $id;
                
            } else {
                $top_children[$top_id][] = $id;
            }
        }
        
        foreach($top_ids as $top_id) {
            $v1 = $rows[$top_id];
            
            if (!empty($top_children[$top_id])) {
                foreach ($top_children[$top_id] as $child_id) {
                    $v2 = $rows[$child_id];
                    $v2['link'] = $this->getLink('forums', $child_id);
                    
                    $level = $tree_helper[$child_id];
                    $v2['padding'] = ($level - 1) * $padding;
                    
                    $v2['description'] = nl2br($v1['description']);
                    
                    $v2['num_entry'] = 0;
        			if(!empty($count_entries[$v2['id']]['num_entry'])) {
        				$v2['num_entry'] = $count_entries[$v2['id']]['num_entry'];
        			}
        			
        			$v2['num_message'] = 0;
        			if(!empty($count_entries[$v2['id']]['num_message'])) {
        				$v2['num_message'] = $count_entries[$v2['id']]['num_message'];
        			}
                    
                    $tpl->tplParse($v2, 'section/row');
                }
            }
            
            
            $tpl->tplSetNested('section/row');
            $tpl->tplParse($v1, 'section');
        }
    }
	
	
	// parse data with topics
	function _parseEntryList($manager, $rows, $title, $by_page = '', $type = '', $add_button = false, $more_link = false) {
	    
        $tpl = new tplTemplatez($this->getTemplate('forum_list_topic.html'));
        
		if(!$rows) {
		    $add_link = false;
            if ($this->category_id) {
                $str = ' - <a href="%s">%s</a>';
                $add_link = $this->getLink('forums', $this->category_id, false, 'post');
                $add_link = sprintf($str, $add_link, $this->msg['add_entry_msg']);
            }
             
			$msg = &$this->getActionMsg('success', 'no_category_topic', false, array('link' => $add_link));
			$tpl->tplAssign('msg', '<br />' . $msg);
            
		} else {
		    
		    if ($this->category_id || ($type == 'member_topic' && $by_page)) {
                $in_section = $manager->isForumInSection($this->category_id);
                $topic_num_msg = ($in_section) ? $this->msg['topics_in_this_section_msg'] : $this->msg['topics_in_this_forum_msg']; 
                $tpl->tplAssign('topic_num_msg', $topic_num_msg);
                
		        $tpl->tplSetNeeded('/by_page_top');
		        $tpl->tplSetNeeded('/by_page_bottom');
		    }
            
            if ($this->category_id) {
                $tpl->tplSetNeeded('/topic_num');
                $num = $manager->countEntriesPerCategory($this->category_id);
                $tpl->tplAssign('topic_num', $num[$this->category_id]['num_entry']);
            }
            
            $options = array('subscribe');
            if ($add_button) {
                $options[] = 'add';
                
                $v = array();
                $v['topic_add_link'] = $this->getLink('forums', $this->category_id, false, 'post');                
                $tpl->tplParse($v, 'topic_add_block_bottom');
            }
            
            if ($this->category_id) {
                $tpl->tplAssign('block_list_option_tmpl', $this->getBlockListOption($tpl, $manager, $options));
            }
        }

		$user_ids = $manager->getValuesString($rows, 'updater_id');
		if($user_ids) {
			$last_posters = $manager->getEntryLastPoster($user_ids);
		}
        
		$i = 0;
        
        $comments_per_page = $manager->getSetting('num_comments_per_page');
        
		foreach(array_keys($rows) as $k) {
			$row = $rows[$k];
			
            // icons
            $icon = 'topic';
            if ($row['active'] == 2) { $icon .= '_closed'; } // closed
            if (@$row['is_sticky']) { $icon .= '_pinned'; }
			
			$private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $icon);
			
			$row['formatted_date'] = $this->getFormatedDate($row['ts_updated'], 'datetime');
			$row['interval_date'] = $this->getTimeInterval($row['ts_updated'], true);			
			
			$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
			$row['entry_link'] = $this->getLink('topic', false, $entry_id);
            
            // $summary_limit = $this->getSummaryLimit($manager, $private);
            // $row['body'] = DocumentParser::getSummary($row['first_message'], $summary_limit);
            $row['body'] = '';
            
            //$row['last_post_date'] = $this->getFormatedDate($last_posters[$row['id']]['date_posted'], 'datetime'); 
			$row['last_poster'] = '';
			if(isset($last_posters[$row['updater_id']])) {
            	$row['last_poster'] = $last_posters[$row['updater_id']];
			}
            
            
            $pages_total = ceil($row['posts'] / $comments_per_page);
            $row['pages'] = '';
            
            if ($pages_total > 1) {
                $pages = array(1,2);
                
                if ($pages_total > 2) { // 3rd
                    $pages[] = 3;
                }
                
                if ($pages_total > 3) { // last
                    $pages[] = $pages_total;
                }
                
                foreach ($pages as $page_num) {
                    $link = $this->getLink('topic', false, $entry_id, false, array('bp' => $page_num));
                    $text = ($page_num > 4) ? $page_num . '&#x2192;' : $page_num;
                    $row['pages'] .= sprintf('<a href="%s" class="forumPagination">%s</a>', $link, $text);
                }
            }
            
			$tpl->tplParse(array_merge($this->msg, $row), 'row');
		}
       
        // more links
        if($more_link) {
            $tpl->tplAssign('more_link', $more_link);
        }
        
        
        // recent
        if (!empty($this->dynamic_limit)) {

            //xajax
            $ajax = &$this->getAjax('forum');
            $ajax->view = &$this;

            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('loadNextEntries', $ajax, 'loadNextEntries'));

            $tpl->tplSetNeeded('/dynamic_entries_scroll_loader');
            $tpl->tplAssign('dynamic_limit', $this->dynamic_limit);

            $context = ($manager->getSetting('view_format') == 'fixed') ? '#content' : 'false';
            $tpl->tplAssign('context', $context);

            $sname = sprintf($this->dynamic_sname, $this->dynamic_type);
            if (!empty($_SESSION[$sname])) {
                $dynamic_offset = $_SESSION[$sname];
                if ($dynamic_offset > $this->dynamic_reload_limit) {
                    $dynamic_offset = $this->dynamic_reload_limit;
                }

            } else {
                $dynamic_offset = $this->dynamic_limit;
            }

            $tpl->tplAssign('dynamic_offset', $dynamic_offset);
        }

		$tpl->tplAssign('page_by_page', $by_page);
		$tpl->tplAssign('list_title', $title);
        
        $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);
		$tpl->tplParse();

        return $tpl;
	}
    
    
    function parseEntryList($manager, $rows, $title, $by_page = '', $type = '', $add_button = false, $more_link = false) {
        $tpl = $this->_parseEntryList($manager, $rows, $title, $by_page, $type, $add_button, $more_link);
        return ($tpl instanceof tplTemplatez) ? $tpl->tplPrint(1) : '';
    }
    
    
    function getCategoryMoveToSelect($manager, $top_category_id) {
     
         $range_ = $manager->getCategorySelectRange($manager->categories, $top_category_id);
     
         $range = array();
         if(isset($manager->categories[$top_category_id])) {
             $range[$top_category_id] = $manager->categories[$top_category_id]['name'];
         }
         
         if (!empty($range_)) {
             foreach (array_keys($range_) as $cat_id) {
                 $range[$cat_id] = '-- '. $range_[$cat_id];
             }
         }
         
         $select = new FormSelect();
         $select->select_tag = false;
         $select->setRange($range);
         
         return $select->select($this->category_id);
     }
    
    
    function getPageByPageObj($class, $limit = false, $hidden = false, $action_page = false) {
        
        $msg = array(
            $this->msg['page_msg'], 
            $this->msg['record_msg'], 
            $this->msg['record_from_msg'],
            $this->msg['prev_msg'],
            $this->msg['next_msg']
        );

        $bp = PageByPage::factory('forum', $limit, $hidden, $action_page);
        $bp->setMsg($msg);
        
        return $bp;
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {
        
        $item = false;
        $in_section = $manager->isForumInSection($this->category_id);
        
        $this->msg['search_category_msg'] = ($in_section) ? $this->msg['search_section_msg'] : $this->msg['search_forum_msg'];
        $umsg = AppMsg::getMsgs('user_msg.ini', 'public');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];

        $tpl = new tplTemplatez($this->getTemplate('block_list_option.html'));
        $tpl->tplSetNeeded('/form');

        // subscribe to category
        if(in_array('subscribe', $options)) {
            
            if($this->isSubscriptionAllowed('allow_subscribe_entry', $manager)) {
                $item = true;
                
                $sub_type = $manager->entry_type_cat;

                $parents = $manager->categories_parent;
                if(!$parents) {
                    $parents = TreeHelperUtil::getParentsById($manager->categories, $this->category_id, 'name');
                }

                if(isset($parents[$this->category_id])) {
                    unset($parents[$this->category_id]);
                }
                $parents = array_keys($parents);
                $parents[] = 0; // all categories
                $parent_str = implode(',', $parents);

                // if subscribed to parent categories
                $subscribed_parent = false;
                if($manager->is_registered && $manager->isEntrySubscribedByUser($parent_str, $sub_type)) {
                    $subscribed_parent = true;
                }

                if(!$subscribed_parent) {
                    $tpl->tplSetNeeded('/view_subscribe');

                    //xajax
                    $ajax = &$this->getAjax('entry');
                    $ajax->view = &$this;
                    $xajax = &$ajax->getAjax($manager);
                    $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeForumResponse'));
                    
                    if($manager->is_registered && $manager->isEntrySubscribedByUser($this->category_id, $sub_type)) {
                        $tpl->tplAssign('subscribe_yes_display', 'none');
                        $tpl->tplAssign('subscribe_no_display', 'inline');
                    } else {
                        $tpl->tplAssign('subscribe_yes_display', 'inline');
                        $tpl->tplAssign('subscribe_no_display', 'none');
                    }

                } else {
                    $link = $this->getLink('member_subsc', false, false, false, array('type' => $sub_type));
                    $tpl->tplAssign('susbscription_link', $link);
                    $tpl->tplSetNeeded('/view_subscribe_parent');
                }
            }
        }
        
        if(in_array('add', $options)) {
            if ($item) {
                $tpl->tplAssign('add_delim', '|');
            }
            
            $item = true;
            $link = $this->getLink('forums', $this->category_id, false, 'post');
            $tpl->tplAssign('add_link', $link);
            $tpl->tplSetNeeded('/view_add_topic');
        }
        
        // search
        $sp = $this->_getSearchFormParams();
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        $tpl->tplAssign('advanced_search_link', $this->getLink('search', $this->category_id) . $sp['search_str']);

        $tpl->tplAssign('category_id', $this->category_id);
        $tpl->tplAssign('search_in', 'forum');
		$tpl->tplAssign('alert_empty_search', addslashes($this->msg['alert_empty_search_msg']));
        
        $tmpl->tplSetNeeded('/list_option_button');

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }
    
    
    function parseAttachments($row, $attachments, &$tpl, $update = false) {
        
        $images = array();
        $non_images = array();
        
        $attachment_caption_display = 'none';
        if (!empty($attachments)) { // this message has attachments
            $attachment_caption_display = 'block';
            
            foreach($attachments as $attach) {
                $a = array();
                $a['attachment_id'] = $attach['id'];
                $a['filename'] = $attach['filename'];

				$more = array('id' => $attach['id']);
                $link = $this->controller->getLink('topic', false, $this->entry_id, 'dfile', $more);
                $a['download_link'] = $link;
                
                $a['lightbox_attributes'] = '';
                if (in_array($attach['mime_type'], $this->image_mime_types)) { // it's an image
                    $a['lightbox_attributes'] = sprintf('class="gallery" rel="group_%s"', $row['id']);
                    $images[] = $a;
                    
                } else {
                    $non_images[] = $a;
                }
            }
        }
        
        $tpl->tplAssign('attachment_caption_display', $attachment_caption_display);
        
        foreach($images as $v) {
            $tpl->tplParse($v, 'attachment_type/attachment');
        }
        
        $tpl->tplSetNested('attachment_type/attachment');
        
        $v1['id'] = $row['id'];
        $v1['type'] = 'Image';
        $v1['delimiter'] = '';
        $tpl->tplParse($v1, 'attachment_type');
        
        
        foreach($non_images as $v) {
            $tpl->tplParse($v, 'attachment_type/attachment');
        }
        
        $tpl->tplSetNested('attachment_type/attachment');
        
        $v1['type'] = 'NonImage';
        $delim_str = '<div id="attachTypeDelim%s" style="border: 1px dashed #888888;margin-bottom: 10px;display: %s;"></div>';
        $display = (empty($images) || empty($non_images)) ? 'none' : 'block';
        $v1['delimiter'] = sprintf($delim_str, ($update) ? 'Update' . $row['id'] : $row['id'], $display);
        $tpl->tplParse($v1, 'attachment_type');
    }

}
?>