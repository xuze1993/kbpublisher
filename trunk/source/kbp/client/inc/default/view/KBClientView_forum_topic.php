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


class KBClientView_forum_topic extends KBClientView_forum
{
        
	function &execute(&$manager) {
	    
        $entry = &$manager->getEntryById($this->entry_id, $this->category_id);
		$entry = $this->stripVars($entry);

		// does not matter why no article, deleted, or inactive or private
		// always send 404, SE will exclude it from result, users will not see 404
		if(!$entry) {
			
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $this->controller->goAccessDenied('forums');
                }
            }
            
            $this->controller->goStatusHeader('404');
		}


		$this->home_link = true;
        $this->meta_title = $entry['title'];
		// $this->meta_keywords = &$row['meta_keywords'];
		// $this->meta_description = &$row['meta_description'];

		$title = $this->getSubstring($this->meta_title, 70);
		$this->nav_title = $entry['title'];
        
        // section, status
        $post_allowed = $manager->isPostAllowed($this->entry_id, $this->category_id, $entry['active']);
        
        // not logged, private, banned 
        if($post_allowed && !$manager->isPostAllowedByUser($this->entry_id, $this->category_id)) {
            $post_allowed = false;
        }
        
        $data = array();
        $data[] = $this->getMessageList($manager, $entry, $post_allowed);

        if ($manager->getSetting('allow_forum_tags')) {
            $data[] = $this->getEntryListTags($manager);
        }

        if ($post_allowed) {
            $data[] = $this->getForm($manager, $this->msg['reply_to_topic_msg']);
        }

        $data[] = $this->getEntryListManage($manager, $entry);
        $data = implode('', $data);

		return $data;
	}
    
    
    function getMessageList($manager, $entry, $add_button) {
        
        require_once APP_CLIENT_DIR . 'client/inc/DocumentParserForum.php';
        
        $entry_id = $this->controller->getEntryLinkParams($entry['id'], $entry['title'], $entry['url_title']);
        $bp_link = $this->getLink('topic', 0, $entry_id, false);
        $num = $manager->getSetting('num_comments_per_page');
        $bp = $this->pageByPage($num, $entry['posts'], $bp_link);

		$rows = &$manager->getEntryMessages($this->entry_id, $bp->limit, $bp->offset);
		$rows = $this->stripVars($rows, array('message'));


        $sticky_message = false;
        $has_sticked_message = false;
        if ($bp->offset != 0) {
            $sticky_message = $manager->getStickyMessage($this->entry_id);
            if ($sticky_message) {
                array_unshift($rows, $sticky_message);
                $has_sticked_message = true;
            }
        }

        $attachments = $manager->getMessageListAttachment($this->entry_id);
        $user = $manager->getUserInfo($entry['author_id']);


        $tpl = new tplTemplatez($this->getTemplate('forum_list_message.html'));
        
        // msg
        if(!$add_button) {
            if ($manager->no_write_reason == 'closed_topic') {
                $tpl->tplAssign('msg', $this->getActionMsg('hint', 'closed_topic'));
                
            } elseif($manager->no_write_reason == 'private_write') {
                $tpl->tplAssign('msg', $this->getActionMsg('hint', 'private_write_topic'));
            }
        }
        
        $options = array('rss', 'subscribe', 'print');
        if ($add_button) {
            $options[] = 'add';
            $tpl->tplSetNeeded('/post_link');
        }
        
        $topic_allowed = $manager->isTopicAddingAllowed($this->category_id); 
        if($topic_allowed && $manager->isTopicAddingAllowedByUser($this->category_id)) {
            $tpl->tplSetNeeded('/new_topic_link');
            $tpl->tplAssign('topic_add_link', $this->getLink('forums', $this->category_id, false, 'post'));
        }
        
        $tpl->tplAssign('post_num', $entry['posts']);
        
        $tpl->tplAssign('block_list_option_tmpl', 
                $this->getBlockListOption($tpl, $manager, $entry, $options));


        if ($bp->num_pages > 1) {
            $tpl->tplSetNeeded('/by_page_top');
            $tpl->tplSetNeeded('/by_page_bottom');
            $tpl->tplAssign('page_by_page', $bp->navigate());
        }

        $tpl->tplAssign('entry_title', $entry['title']);
        $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($entry['date_posted']));
        $tpl->tplAssign('ajax_update_url', 
                html_entity_decode($this->getLink('forums', false, $this->entry_id, 'update')));

        $author = '%s %s (%s)';
        $author = sprintf($author, $user['first_name'], $user['last_name'], $user['username']);
        $tpl->tplAssign('author', $author);

        $admin_path = $this->controller->getAdminJsLink();
        $tpl->tplAssign('config_path', $admin_path . 'tools/ckeditor_custom/ckconfig_forum.js');

        //xajax
        $ajax = &$this->getAjax('forum');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);

        $more = array('bp' => $bp->cur_page);
        $url = $this->controller->getAjaxLink('all', false, false, false, $more);
        $xajax->setRequestURI($url);
        
        $xajax->registerFunction(array('getQuotedMessage', $ajax, 'ajaxGetQuotedMessage'));
        $xajax->registerFunction(array('loadMessageForm', $ajax, 'ajaxLoadMessageForm'));
        $xajax->registerFunction(array('updateMessage', $ajax, 'ajaxUpdateMessage'));
        $xajax->registerFunction(array('deleteMessage', $ajax, 'ajaxDeleteMessage'));
        $xajax->registerFunction(array('deleteAttachmentToMessage', $ajax, 'ajaxDeleteAttachmentToMessage'));
        $xajax->registerFunction(array('stickMessage', $ajax, 'ajaxStickMessage'));
        $xajax->registerFunction(array('unstickMessage', $ajax, 'ajaxUnstickMessage'));
        
        if ($manager->getSetting('forum_allow_attachment')) {
            $tpl->tplSetNeededGlobal('attachment_js');
                        
            $file_upload_url = $this->controller->_replaceArgSeparator($this->getLink('forums', false, false, 'file_upload', array('id' => 'message_id')));
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
        
        $action_allowed = $manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
        
        // sticky
        $message_pinned = $manager->isMessageSticky($entry['id'], $entry['first_post_id']);
        
        $share_html = $this->getShareLinkBlockHtml($manager, $this->msg['share_post_msg'], $this->msg['link_to_post_msg']);
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];

			$row['anchor'] = 'post-' . $row['id'];
            $row['formated_date'] = $this->getFormatedDate($row['date_posted'], 'datetime');
            $row['interval_date_posted'] = $this->getTimeInterval($row['date_posted'], true);
            $row['style'] = '';
            
            $row['message_num'] = (($bp->cur_page - 1) * $num) + $k + 1;
            if ($has_sticked_message) {
                $row['message_num'] --;
            }

            if ($row['date_updated'] != $row['date_posted']) {
                $tpl->tplSetNeeded('row/updater');
                $row['interval_date_updated'] = $this->getTimeInterval($row['date_updated'], true);
            }
            
            // buttons
            $row['action_buttons'] = $this->getPostActionButtons($manager, $row, $entry, $add_button, $action_allowed, $message_pinned);
            
            if ($entry['first_post_id'] == $row['id'] && $message_pinned && $bp->offset != 0) {
                $row['style'] = 'style="background: #eaeaea;"';
                $row['message_num'] = 1;
            }
            
            // attachments
            $message_attachments = (isset($attachments[$row['id']])) ? $attachments[$row['id']] : array();
            $row['attachment_block'] = $this->getAttachmentBlock($row['id'], $message_attachments);
            
            $anchor = $row['anchor'] = 'c' . $row['id'];
            
            $more = (isset($_GET['bp'])) ? array('bp' => $bp->cur_page) : array();
            $entry_id = $this->controller->getEntryLinkParams($entry['id'], $entry['title'], $entry['url_title']);
            $row['anchor_link'] = $this->getLink('topic', 0, $entry_id, false, $more) . '#' . $anchor;
            
            // share link
            if($manager->getSetting('show_share_link') && $manager->getSetting('item_share_link')) {
                $more = array('message_id' => $row['id']);
                
                $share_link_full = $this->controller->getLinkNoRewrite('topic', false, $this->entry_id, false, $more);
                $share_block = str_replace('[full_url]', $share_link_full, $share_html);
                
                $more = array('message_id' => $row['id']);
                $share_link = $this->controller->getRedirectLink('topic', false, $this->entry_id, false, $more);
                $share_link = urlencode($share_link);
                
                $share_block = str_replace('[url]', $share_link, $share_block);
                $share_block = str_replace('[title]', urlencode($entry['title']), $share_block);
                
                $tpl->tplAssign('share_block', $share_block);
            }
            
            DocumentParser::parseCurlyBraces($row['message']);
            
            $tpl->tplParse($row, 'row');
        }

        if(DocumentParser::isCode2($tpl->parsed['row'])) {
            DocumentParser::parseCode2($tpl->parsed['row'], $this->controller);
        }

        $tpl->tplParse($entry);
        return $tpl->tplPrint(1);
	}
    
    
    function getPostActionButtons($manager, $row, $entry, $add_button, $action_allowed, $message_pinned) {
        
        $buttons = array();
        
        $stick_link = '<span id="sticky_status_link_%d" onclick="xajax_%sMessage(\'%d\', \'forumActionSpinner%s\');">%s</span>';
        $quote_link = '<a href="#message_form" onclick="quote(\'%s\');">%s</a>';
        $update_link = '<span class="update" id="update%s" onclick="%s">%s</span>';
        $delete_link = '<span onclick="deleteMessage(\'%s\');">%s</span>';
        
        // stick/unstick
        if($entry['first_post_id'] == $row['id'] && $entry['active'] == 1) {
            if (in_array('update', $action_allowed)) {
                $title = ($message_pinned) ? $this->msg['unstick_this_message_msg'] : $this->msg['stick_this_message_msg'];
                $func = ($message_pinned) ? 'unstick' : 'stick';
                
                $buttons[] = sprintf($stick_link, $row['id'], $func, $row['id'], $row['id'], $title);
            }
        }
        
        // quote
        if ($manager->user_id) {
            if ($entry['active'] == 1 && $add_button) {
                $buttons[] = sprintf($quote_link, $row['id'], $this->msg['quote_msg']);
            }
        }
        
        $blocks = array();
        
        // update
        if ($entry['first_post_id'] == $row['id']) {
            $link = $this->getLink('topic', false, $this->entry_id, 'update');
            $onclick = sprintf("location.href='%s';", $link);
            
        } else {
            $onclick = sprintf("update('%s');", $row['id']);
        }
                
        $blocks['update'] = sprintf($update_link, $row['id'], $onclick, $this->msg['update_msg']);
        $blocks['delete'] = sprintf($delete_link, $row['id'], $this->msg['delete_msg']);
        
        
        foreach ($action_allowed as $v) {
            if ($entry['active'] == 1) {
                $buttons[] = $blocks[$v];
            }
        }
        
        // action for regular user, not admin
		if ($row['user_id'] == $manager->user_id) {
		    $allowed = $manager->getPostActionsAllowedUser($row['user_id'], $row['date_posted'], $action_allowed);
            
			foreach ($allowed as $v) {
			    $buttons[] = $blocks[$v];
            }
        }
        
        return implode(' | ', $buttons);        
    }
    
    
    function getAttachmentBlock($message_id, $attachments) {
        if (empty($attachments)) {
            return '';
            
        } else {
            $tpl = new tplTemplatez($this->getTemplate('forum_attachment_block.html'));
            
            $row['id'] = $message_id;
            $this->parseAttachments($row, $attachments, $tpl);
            
            $tpl->tplParse(array_merge($this->msg, $row));
            return $tpl->tplPrint(1);
        }
    }
    
    
    function getMessageForm($message_id, $manager) {

        // actions
        /*$action_allowed = $manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
        if(!$action_allowed) {
			return;
		}*/

        $tpl = new tplTemplatez($this->getTemplate('forum_form_message_update.html'));
        
        $attachments = $manager->getMessageAttachment($message_id);
        
        if ($manager->getSetting('forum_allow_attachment')) {
            $tpl->tplSetNeeded('/new_attachment');
            
            
            $from_disk = '<a href="#" onclick="$(\'#input_file%s\').click();return false;" style="cursor: pointer;color: #aaaaaa;">$1</a>';
            $from_disk = sprintf($from_disk, $message_id);
            $choose_msg = preg_replace('/<a>(.*?)<\/a>/', $from_disk, $this->msg['drop_files_to_attach_msg']);
            
            $tpl->tplAssign('attachment_drop_file', $choose_msg);
            
            $choose_msg = str_replace('$1', $this->msg['drop_files_disabled_msg'], $from_disk);
            $tpl->tplAssign('attachment_drop_disabled', $choose_msg);
            
            
            $max_files_num = $manager->getSetting('file_num_per_post') - count($attachments);
            $tpl->tplAssign('class', ($max_files_num) ? '' : 'dz-max-files-reached');
            $tpl->tplAssign('dropzone_active_display', ($max_files_num) ? 'inline' : 'none');
            $tpl->tplAssign('dropzone_inactive_display', ($max_files_num) ? 'none' : 'inline'); 
        }
        
        $data = $manager->getMessageById($message_id);
        $tpl->tplAssign($data);
        
        $this->parseAttachments($data, $attachments, $tpl, true);
        
        $link = $this->getLink('forum_preview', $this->category_id, $this->entry_id, false, array('message_id' => $message_id));
        $tpl->tplAssign('preview_link', $link);

        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function _getCategoryFilterSelect($manager, $top_category_id, $disable_current = false) {

        $use_sections = $manager->getSetting('forum_sections');
        
        $range = array();
        foreach(array_keys($manager->categories) as $cat_id) {
            if ($manager->categories[$cat_id]['parent_id'] == 0) {
                $add = true;
                
                if ($use_sections) {
                    $children = $manager->getCategorySelectRange($manager->categories, $cat_id);
                    if (empty($children)) {
                        $add = false;
                    }
                }
                
                if ($add) {
                    $range[$cat_id] = $manager->categories[$cat_id]['name'];
                }
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range, array('all' => '__'));

        if ($disable_current) {
            if (!empty($range[$this->category_id])) {
                $children = $manager->getCategorySelectRange($manager->categories, $this->category_id);
                if (empty($children)) {
                    $select->setOptionParam($this->category_id, 'disabled');
                }
            }
        }

        return $select->select($top_category_id);
    }


    function getEntryListManage($manager, $entry) {

        // actions
        $action_allowed = $manager->getPostActionsAllowedAdmin($this->entry_id, $this->category_id);
        if(!$action_allowed) {
			return;
		}

        $tpl = new tplTemplatez($this->getTemplate('forum_entry_stuff_manage.html'));
        
        if (in_array('delete', $action_allowed)) {
            $tpl->tplSetNeeded('/delete_button');
        }
        
        if (in_array('update', $action_allowed)) {
            if ($entry['active'] == 1) {
                $tpl->tplSetNeededGlobal('published');
    
            } else {
                $tpl->tplSetNeededGlobal('closed');
            }
            
            $tpl->tplAssign('filter_select', $this->_getCategoryFilterSelect($manager, false, true));
        }

        $ajax = &$this->getAjax('category_filter');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getChildForums', $ajax, 'ajaxGetChildCategories'));

        $tpl->tplAssign('action_link', $this->getLink('topic', false, $this->entry_id, 'detail'));

        $tpl->tplAssign('category_id', $this->category_id);

        $sticky_date = $manager->getStickyDate($this->entry_id);
        $tpl->tplAssign('sticky_action', (strlen($sticky_date) > 0) ? $this->msg['forum_unstick_entry_msg'] : $this->msg['forum_stick_entry_msg']);

        $tpl->tplAssign('anchor', 'anchor_entry_manage');
        $tpl->tplAssign('title_msg', $this->msg['manage_msg']);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getEntryListTags($manager) {

        $tags = false;
        if ($manager->getSetting('show_tags')) {
            $tags = $manager->getTagByEntryId($this->entry_id);
            $tags = $this->stripVars($tags);
        }

        if (empty($tags)) {
            return;
        }

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_tag.html'));

        $tags = $this->getTagsArray($tags, 'forum');
        foreach ($tags as $tag) {
            $tpl->tplParse($tag, 'row');
        }

        $tpl->tplAssign('title_msg', $this->msg['tags_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1) . '<br/>';
    }


    function &getBlockListOption(&$tmpl, $manager, $entry, $options = array()) {

        $item = false;

        $umsg = AppMsg::getMsgs('user_msg.ini', 'public');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];
        $this->msg['search_category_msg'] = $this->msg['search_topic_msg'];

        $tpl = new tplTemplatez($this->getTemplate('block_list_option.html'));
        $tpl->tplSetNeeded('/forum_form');
        
        if(in_array('subscribe', $options)) {

            if($this->isSubscriptionAllowed('allow_subscribe_forum', $manager)) {
                $item = true;
                $tpl->tplSetNeeded('/view_subscribe');

                //xajax
                $ajax = &$this->getAjax('entry');
                $ajax->view = &$this;
                $xajax = &$ajax->getAjax($manager);
                $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeTopicResponse'));

                if($manager->is_registered && $manager->isEntrySubscribedByUser($this->entry_id, 4)) {
                    $tpl->tplAssign('subscribe_yes_display', 'none');
                    $tpl->tplAssign('subscribe_no_display', 'inline');
                } else {
                    $tpl->tplAssign('subscribe_yes_display', 'inline');
                    $tpl->tplAssign('subscribe_no_display', 'none');
                }
            }
        }
        
        
        if(in_array('rss', $options)) {
            if($manager->getSetting('rss_generate') != 'none') {
                if(!$this->isPrivateEntry($entry['private'], $entry['category_private'])) {
                    if ($item) {
                        $tpl->tplAssign('rss_delim', '|');
                    }
                    
                    $item = true;                
                    $link = $this->controller->kb_path . 'rss.php?f=%d';
                    $tpl->tplAssign('rss_link', sprintf($link, $this->entry_id));
                    $tpl->tplSetNeeded('/view_rss');
                }
            }
        }

        // print
        if(in_array('print', $options)) {
            if ($item) {
                $tpl->tplAssign('print_delim', '|');
            }
            
            $item = true;
            $tpl->tplAssign('print_link', $this->getLink('print-topic', false, $this->entry_id));
            $tpl->tplSetNeeded('/view_print');
        }

        // post
        if (in_array('add', $options)) {
            if ($item) {
                $tpl->tplAssign('post_delim', '|');
            }
            
            $item = true;  
            $link = "CKEDITOR.instances.message.focus();";
            $tpl->tplAssign('post_link', $link);
            $tpl->tplSetNeeded('/view_post_link');
        }

        // search
        $sp = $this->_getSearchFormParams();
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        $tpl->tplAssign('advanced_search_link', $this->getLink('search', $this->category_id) . $sp['search_str']);

        $tpl->tplAssign('category_id', $this->category_id);
        $tpl->tplAssign('search_in', 'forum');
		$tpl->tplAssign('alert_empty_search', addslashes($this->msg['alert_empty_search_msg']));
        $tpl->tplAssign('entry_id', $this->entry_id);

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }

}
?>