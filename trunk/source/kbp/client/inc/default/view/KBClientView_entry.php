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


class KBClientView_entry extends KBClientView_common
{
    
    var $action_menu = array();
    var $entry = false; // to keep entry data if defined in action, need for emode 
    var $comment_form;
		

    function &execute(&$manager) {
        
        if($this->entry === false) { // defined in action, need for emode
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
        } else {
            $row = $this->entry;
        }
        
        $row = $this->stripVars($row);

        // does not matter why no article, deleted, or inactive or private
        if(!$row) { 
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $this->controller->goAccessDenied('index');
                }
            }
            
            $this->controller->goStatusHeader('404');
        }
                
        // entry_type
        $type = ListValueModel::getListRange('article_type', false);
        
        // related
        $related = &$manager->getEntryRelated($this->entry_id);
        // echo '<pre>', print_r($related, 1), '</pre>';
        
        $title = $row['title'];
        $this->meta_title = $this->getSubstring($title, 150);
        $this->meta_keywords = $row['meta_keywords'];
        $this->meta_description = $row['meta_description'];
        
        
        $this->nav_title = false;
        if($manager->getSetting('show_title_nav')) {
            $prefix = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);
            $this->nav_title = $prefix . $this->getSubstring($title, 70, '...');
        }
        
        // custom
        $custom_rows = $manager->getCustomDataByEntryId($row['id']);
        $custom_data = $this->getCustomData($custom_rows);
        
        // comments        
        if($manager->getSetting('comments_entry_page')) {
            
            $this->controller->loadClass('comment');
            $comment = new KBClientView_comment();
            $comment_list = $comment->getList($manager, $row);

            $comment_form = '';
            if($this->isCommentable($manager, $row['commentable'])) {
                if($comment_list) {
                    $comment_form = $comment->getForm($manager, $row, $this->msg['add_comment_msg'], 'entry');
					$this->comment_form = true;
                }
            }
        }
        
        
        $data = array();
        $data[] = $this->getEntry($manager, $row, $related['inline'], $custom_data);
        
        if (!$this->mobile_view) {
            $data[] = $this->getEntryListCustomField($custom_data[3]);
        }
        
        $data[] = $this->getEntryListTags($manager);
        $data[] = $this->getEntryListAttachment($manager);
        $data[] = $this->getEntryListRelated($manager, $type, $related['attached']);
        $data[] = $this->getEntryListPublished($manager, $type);
        $data[] = $this->getEntryListExternalLink($row, $type);
        
        
        if(isset($comment_list)) {

            $data['prev_next'] = $this->getEntryPrevNext($manager, $type);
            $data['comment_list'] =& $comment_list;
            $data['commant_form'] =& $comment_form;

            if($data['comment_list']) {
                $data[] = $data['prev_next'];
            }
                        
        } else {
            $data[] = $this->getEntryCommentsNum($manager);
            $data['prev_next'] = $this->getEntryPrevNext($manager, $type);
        }        
        
        $data[] = $this->getEntryListCategory($manager, $type);
        
        if ($this->mobile_view) {
            $data[] = $this->getEntryListCustomField($custom_data[3]);
        }
        
        $data = implode('', $data);
        
        if ($this->mobile_view) {
            $data .= '<br />';
        }

        return $data;        
    }
    
    
    function parseBody($manager, &$body, $related_inline) {
        $glossary_items = $manager->getGlossaryItems();
        if($glossary_items) {
            DocumentParser::parseGlossaryItems($body, $glossary_items, $manager);
        }
    
        if(DocumentParser::isTemplate($body)) {
            DocumentParser::parseTemplate($body, array($manager, 'getTemplate'));
        }        
    
        if(DocumentParser::isLink($body)) {
            DocumentParser::parseLink($body, array($this, 'getLink'), $manager, 
                $related_inline, $this->entry_id, $this->controller);    
        }
    
        if(DocumentParser::isCode($body)) {
            DocumentParser::parseCode($body, $manager, $this->controller);
        }
        
        if(DocumentParser::isCode2($body)) {
            DocumentParser::parseCode2($body, $this->controller);
        }
    
        DocumentParser::parseCurlyBraces($body);
        
        if($manager->getSetting('article_table_content')) {
            if($matches = DocumentParser::isTableContent($str)) {
                DocumentParser::parseTableContent($body, $matches);
            }
        }
        
        // DocumentParser::tidyCleanRepair($body, $this->encoding);
    }
    
    
    function getEntry(&$manager, &$row, $related_inline, $custom_data)  {
        
        $this->parseBody($manager, $row['body'], $related_inline);
		
        $tmpl = 'article.html';
        if($manager->getSetting('article_block_position') == 'bottom') {
            $tmpl = 'article_bb.html';
        }
        
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
        $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
            
        $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($row['ts_updated']));
        $tpl->tplAssign('updated_date', $this->getFormatedDate($row['ts_updated']));
        $tpl->tplAssign('article_block', $this->getEntryBlock($row, $manager));
		$tpl->tplAssign('rating_block', $this->getRatingBlock($manager, $row));
		
		$this->parseActionMenu($tpl); // populated in getEntryBlock
        
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    

    function parseActionMenu($tpl) {
        if (!empty($this->action_menu) && !$this->mobile_view) {
			$menu = $this->getActionMenu($this->action_menu);
            $tpl->tplSetNeeded('/admin_block_menu');
            $tpl->tplAssign('action_menu', $menu);
        }
    }
	
    
    function &getEntryBlock($data, $manager) {

        $ret = '';
        $display = false;
        
        $tmpl = 'article_block.html';
        if($manager->getSetting('article_block_position') == 'bottom') {
            $tmpl = 'article_block_bb.html';
        }
        
        // $this->addMsg('user_msg.ini');
        $umsg = AppMsg::getMsgs('user_msg.ini', 'public');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];
        $this->msg['private_msg'] = $umsg['private_msg'];
        $this->msg['public_msg'] = $umsg['public_msg'];        

        
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        $tpl->tplAssign('article_id', $data['id']);
        $tpl->tplAssign('category_id', $data['category_id']);
        
        // authors,  dates
        if($manager->getSetting('show_author')) {
            $display = true;
            
            $r = new Replacer();
            $r->s_var_tag = '[';
            $r->e_var_tag = ']';
            $r->strip_var_sign = '--';
            
            $str = $manager->getSetting('show_author_format');
            
            $by_msg = $this->msg['by_msg'];
            $user = $manager->getUserInfo($data['author_id']);
            if($user) {
                @$user['short_first_name'] = _substr($user['first_name'], 0, 1);    
                @$user['short_last_name'] = _substr($user['last_name'], 0, 1);
                @$user['short_middle_name'] = _substr($user['middle_name'], 0, 1); 
                $tpl->tplAssign('author', $r->parse($str, $user));    
            } else {
                $tpl->tplAssign('author', '--');
            }
            
            $user = $manager->getUserInfo($data['updater_id']);
            if($user) {
                @$user['short_first_name'] = _substr($user['first_name'], 0, 1);
                @$user['short_last_name'] = _substr($user['last_name'], 0, 1); 
                @$user['short_middle_name'] = _substr($user['middle_name'], 0, 1);
                $tpl->tplAssign('updater', $r->parse($str, $user));
            } else {
                $tpl->tplAssign('author', '--');
            }
            
            $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($data['ts_posted']));
            $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($data['ts_updated']));
                                    
            $tpl->tplSetNeeded('/author_block');
        }        


        // info block, last updated
        $display_entry = false;
        if($manager->getSetting('show_entry_block')) {
            $display = true;
            $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($data['ts_updated']));
            $tpl->tplAssign('revision', $manager->getRevisionNum($data['id']));
            
            $tpl->tplSetNeeded('/entry_block');
            
            // private 
            if($manager->getSetting('show_private_block') && $manager->is_registered) {
                
                $private = array();
                $private['cat'] = $data['category_private'];
                $private['cat_role'] = array();
                if($private['cat']) {
                    $private['cat_role'] = $manager->getPrivateInfo($this->category_id, 'category');
                }
            
                $private['entry'] = $data['private'];
                $private['entry_role'] = array();
                if($private['entry']) {
                    $private['entry_role'] = $manager->getPrivateInfo($this->entry_id, 'entry');
                }
            
                if($private['cat'] || $private['entry']) {

                    $role_full = array();
                    if(!empty($private['cat_role']) || !empty($private['entry_role'])) {
                        $role_manager = &$manager->getRoleModel();
                        $role_arr = $role_manager->getSelectRecords();
                        $role_full = &$role_manager->getSelectRangeFolow($role_arr, 0, ' :: ');    
                    }
                
                    $combined_roles = array_merge_recursive($private['cat_role'], $private['entry_role']);
                    $rread = array_unique($combined_roles['read']);
                    $rwrite = array_unique($combined_roles['write']);          
                
                    $private['read'] = $this->getEntryRoles($role_full, $rread);
                    $private['write'] = $this->getEntryRoles($role_full, $rwrite);
                                
                    $tmsg = &$this->_getPrivateToolTipMsg($private);
                    // $private_msg = '<span style="color: red;">%s</span>';
                    $private_msg = '<span style="cursor: pointer;">%s</span>';
                    $tpl->tplAssign('private_title', sprintf($private_msg, $umsg['private_msg']));
                
                    if (!$this->mobile_view) {
                        $tmsg['body'] = $this->stripVars($tmsg['body']);
                    }
                
                    $tpl->tplAssign('private_body', $tmsg['body']);                
                } else {
                    $tpl->tplAssign('private_title', $umsg['public_msg']);
                }
                
                $tpl->tplSetNeeded('/private');
            }
        }
        
        
        // print
        $action_block = false;
        if($manager->getSetting('show_print_link')) {
            $display = true;
            $action_block = ($action_block) ? '/print_link' : '/action_block/print_link';
            $tpl->tplSetNeeded($action_block);
            $tpl->tplAssign('print_link', $this->getLink('print', $data['category_id'], $data['id']));
        }
        
        // pdf
        if($manager->getSetting('show_pdf_link')) {
            if(BaseModel::isPluginPdf($manager->setting)) {
                $display = true;
                $action_block = ($action_block) ? '/pdf_link' : '/action_block/pdf_link';
                $tpl->tplSetNeeded($action_block);
                $pdf_link = $this->getLink('pdf', $data['category_id'], $data['id']);
            
                if ($this->controller->mod_rewrite == 3) {
                    $entry_link = $this->controller->getEntryLinkParams($data['id'], $data['title'], $data['url_title']);
                    $pdf_link = $this->getLink('entry', false, $entry_link);
                    $pdf_link = str_replace('.html', '.pdf', $pdf_link);
                }

                $tpl->tplAssign('pdf_link', $pdf_link);
            }
        }
        
        // subscribe
        if($this->isSubscriptionAllowed('allow_subscribe_entry', $manager)) {

            //xajax
            $ajax = &$this->getAjax('entry');
            $ajax->view = &$this;
            $xajax = &$ajax->getAjax($manager);
            
            $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeArticleResponse'));
            
            $visible_display = ($this->mobile_view) ? 'block' : 'inline';
            
            if($manager->is_registered && $manager->isEntrySubscribedByUser($data['id'], 1) ) {
                $tpl->tplAssign('subscribe_yes_display', 'none');
                $tpl->tplAssign('subscribe_no_display', $visible_display);
            } else {
                $tpl->tplAssign('subscribe_yes_display', $visible_display);
                $tpl->tplAssign('subscribe_no_display', 'none');                
            }            
    
            $display = true;
            $action_block = ($action_block) ? '/subscribe' : '/action_block/subscribe';
            $tpl->tplSetNeeded($action_block);        
        }
        
        // to friend
        if($manager->getSetting('show_send_link')) {
            $display = true;
            $action_block = ($action_block) ? '/send_link' : '/action_block/send_link';
            $tpl->tplSetNeeded($action_block);            
            $tpl->tplAssign('send_link', $this->getLink('send', $data['category_id'], $data['id']));        
        }
        
        // share link
        if($manager->getSetting('show_share_link') && $manager->getSetting('item_share_link')) {
            $display = true;
            $action_block = ($action_block) ? '/share_link' : '/action_block/share_link';
            $tpl->tplSetNeeded($action_block);
            
            
            $share_html = $this->getShareLinkBlockHtml($manager, $this->msg['share_article_msg'], $this->msg['link_to_article_msg']);
                
            $share_link_full = $this->controller->getLinkNoRewrite('entry', false, $data['id']);
            $share_block = str_replace('[full_url]', $share_link_full, $share_html);
            
            $share_link = $this->controller->getRedirectLink('entry', false, $data['id']);
            $share_link = urlencode($share_link);
            
            $share_block = str_replace('[url]', $share_link, $share_block);
            $share_block = str_replace('[title]', urlencode($data['title']), $share_block);
            
            $tpl->tplAssign('share_block', $share_block);
        }
        
        // hits
        $data_block = false;        
        if($manager->getSetting('show_hits')) {
            $display = true;
            $data_block = ($data_block) ? '/show_hits' : '/data_block/show_hits';
            $tpl->tplSetNeeded($data_block);
        }
        
        // pool
        if($manager->getSetting('show_pool_link')) {
            $display = true;
            $action_block = ($action_block) ? '/pool_link' : '/action_block/pool_link';
            $tpl->tplSetNeeded($action_block); 
            
            $_display = (!$this->mobile_view) ? 'inline' : 'block';
            $display_add_pool = $_display;
            $display_delete_pool = 'none';
            
            $ids = $this->getUserPool('pool');
            if (count($ids) > 0) {
                if (in_array($this->entry_id, $ids)) { // been added
                    $display_add_pool = 'none';
                    $display_delete_pool = $_display;
                }
            }
            
            $tpl->tplAssign('display_add_pool', $display_add_pool);        
            $tpl->tplAssign('display_delete_pool', $display_delete_pool);
        }
        
        
        // comments
        if($this->isCommentable($manager, $data['commentable'])) {
            $display = true;               
            
            if($manager->getSetting('show_comments')) {
                $data_block = ($data_block) ? '/show_comments' : '/data_block/show_comments';
                $tpl->tplSetNeeded($data_block);                    
            
                $comments_num = $manager->getCommentsNumForEntry($data['id']);
                $tpl->tplAssign('comment_num', ($comments_num) ? $comments_num[$data['id']] : 0);
            }
            
            $action_block = ($action_block) ? '/comment_link' : '/action_block/comment_link';
            $tpl->tplSetNeeded($action_block); 
            
            $comment_link = $this->getLink('comment', $data['category_id'], $data['id'], 'post');
            if ($this->comment_form) {
                $comment_link = 'javascript:slideToCommentForm();';
            }
            
            $tpl->tplAssign('comment_link', $comment_link);
        }
        
        
        // admin block - edit, add
        $display_admin_block = false;
        $updateable = $manager->isEntryUpdatableByUser($this->entry_id, $this->category_id, 
                                                       $data['private'], $data['category_private'], 
                                                       $data['active']);
        
        // update 
        if($updateable) {
            
            $display = true;
            $display_admin_block = true;
            $referer = 'client';
            
            // draft
            $more = array('entry_id'=>$this->entry_id, 'referer'=>$referer);
            $draft_link = $this->controller->getAdminRefLink('knowledgebase', 'kb_draft', false, 'insert', $more, false);

             if($updateable === 'as_draft') {
                 $this->action_menu[] = array($this->msg['update_entry_draft_msg'], $draft_link);

             } else {
                 
                 // no quick
                 if($updateable === true) {
                     $link = $this->getLink('entry', $this->category_id, $this->entry_id, false, array('em'=>1));
                     $this->action_menu[] = array($this->msg['update_entry_quick_msg'], (empty($_GET['em'])) ? $link : false);
                 }

                 $more = array('id'=>$this->entry_id, 'referer'=>$referer);
                 $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'update', $more, false);

                 $this->action_menu[] = array($this->msg['update_entry_msg'], $link);
                 $this->action_menu[] = array($this->msg['update_entry_draft_msg'], $draft_link);
             }

	         // duplicate
	         if($manager->isEntryAddingAllowedByUser($this->category_id)) {
	             $display = true;
	             $display_admin_block = true;
	             $referer = 'client';

	             $more = array('id'=>$this->entry_id, 'referer'=>$referer, 
	 						   'category_id'=>$this->category_id, 'show_msg'=>'note_clone');
	             $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'clone', $more, false);
	             $this->action_menu[] = array($this->msg['duplicate_msg'], $link);
	         }


            $more = array('id'=>$this->entry_id, 'referer'=>$referer);
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'detail', $more, false);
            $this->action_menu[] = array($this->msg['entry_detail_msg'], $link);
            
            $more = array('id'=>$this->entry_id, 'filter[c]'=>$this->category_id);
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, false, $more, false);
            $this->action_menu[] = array($this->msg['admin_category_list_msg'], $link);
            
        
            // delete
            $deleteable = $manager->isEntryDeleteableByUser($this->entry_id, $this->category_id, 
                                                           $data['private'], $data['category_private'], 
                                                           $data['active']);

            if($deleteable) {
                $display = true;
                $display_admin_block = true;
                $referer = 'client';

                $more = array('id'=>$this->entry_id, 'referer'=>$referer, 'category_id'=>$this->category_id);
                $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'delete', $more, false);
                $this->action_menu[] = array($this->msg['trash_msg'], $link, true);
            }
        }
		        
        
        if($display_admin_block) {
            $this->parseActionMenu($tpl);
        }
        
        
        if($display) {
            $tpl->tplParse($data);
            return $tpl->tplPrint(1);
        }
    }
    
    
    function getEntryRoles($all_roles, $entry_roles_ids) {
        $r = array();
        foreach($entry_roles_ids as $id) {
            $r[$id] = $all_roles[$id];
        }
        
        return $r;
    }
    
    
    function &_getPrivateToolTipMsg($arr, $user_msg = array()) {
        return $this->getPrivateToolTipMsg($arr['cat'], $arr['entry'], $arr['read'], $arr['write'], $user_msg);
    }
    
    
    function &getPrivateToolTipMsg($category, $entry, $read_role, $write_role, $umsg = array()) {
        
        require_once 'core/common/PrivateEntry.php';
        
        if(!$umsg) {
            $umsg = AppMsg::getMsgs('user_msg.ini', 'public');
        }
        
        if(($entry + $category) == 5) { // read + write
            $private = 1;
        } elseif($entry == 0 || $category == 0) {
            $private = max($entry, $category);
        } else {
            $private = min($entry, $category);
        }
        
        $private_msg = BaseView::getPrivateTypeMsg($private, $umsg);
                
        $msg['title'] = sprintf('%s', $private_msg);
        $msg['body'] = '';
        
        $str = '<b>%s:</b> <div style="padding-left: 15px;">%s</div>';
        
        if(PrivateEntry::isPrivateRead($private)) {
            $roles = ($read_role) ? implode('<br />', $read_role) : $umsg['no_roles_msg'];
            $msg['body'] .= sprintf($str, $umsg['private2_read_msg'], $roles);
        }

        if(PrivateEntry::isPrivateWrite($private)) {    
            $roles = ($write_role) ? implode('<br />', $write_role) : $umsg['no_roles_msg'];
            $msg['body'] .= sprintf($str, $umsg['private2_write_msg'], $roles);
        }
        
        if(!$msg['body']) {
            $msg['body'] = $umsg['no_roles_msg'];
        }
    
        return $msg;
    }
    
    
    function getEntryListCustomField($rows) {
    
        if(!$rows) { return; }

        $rows = DocumentParser::parseCurlyBracesSimple($rows);

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_custom.html'));
            
        foreach($rows as $id => $row) {
            $row['id'] = $id;
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_custom');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListAttachment(&$manager, $entry_id = false, $msg_id = false) {

        $entry_id = ($entry_id) ? $entry_id : $this->entry_id;
        
        $rows = $manager->getAttachmentList($entry_id);
        if(!$rows) { return; }

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_attachment.html'));
            
        foreach($rows as $file_id => $row) {
            
            $ext = substr($row['filename'], strrpos($row['filename'], ".")+1);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, false, 'file', $ext);
            $row['filesize'] = WebUtil::getFileSize($row['filesize']);
			
            $more = array('AttachID' => $file_id, 'f' => 1);
            $link = $this->controller->getLink('afile', false, $entry_id, $msg_id, $more, 1);
            $row['attachment_link'] = $link;
			
            $more = array('AttachID' => $file_id);
            $link = $this->controller->getLink('afile', false, $entry_id, $msg_id, $more, 1);
			$row['download_link'] = $link;
			
            $row['attachment_title'] = ($row['title']) ? $row['title'] : $row['filename'];
            $row['attachment_title'] = $this->stripVars($row['attachment_title']);
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_attachment');
        $tpl->tplAssign('title_msg', $this->msg['attachment_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListRelated($manager, $type, $rows) {
        
        if(!$rows) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        foreach($rows as $k => $row) {
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'article');
            $row['title'] = $this->stripVars($row['title']);    
            
            $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $this->category_id, $entry_id);
            $row['entry_id'] = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);
                                       
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_related');
        $tpl->tplAssign('title_msg', $this->msg['entry_related_title_msg']);
        $tpl->tplAssign('key', 'related');
        $tpl->tplAssign('icon_key', 'book');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListPublished(&$manager, $type) {
        
        $ret = false;
        if(!$manager->getSetting('entry_published')) {
            return $ret;
        }

        $rows = $this->stripVars($manager->getEntryCategories($this->entry_id, $this->category_id));
        if(!$rows) { 
            return $ret; 
        }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        // TODO: it could be category will not be in full path categories 
        // because top category is private, normally private category
        // should not have public parent categories
        $full_path = &$manager->getCategorySelectRangeFolow();
        foreach($rows as $k => $row) {
        
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $row['private'], true);
            $row['entry_link'] = $this->getLink('index', $row['category_id']);
            $row['title'] = $full_path[$row['category_id']];
                                            
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_published');
        $tpl->tplAssign('title_msg', $this->msg['entry_published_title_msg']);
        $tpl->tplAssign('key', 'also_listed');
        $tpl->tplAssign('icon_key', 'folder-open');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseExternalLink($data){
        $row = array();
        $num = 0;
        foreach(explode("\n", $data) as $v) {
            $num++;
            @list($link, $title) = explode("|", $v);
            $row[$num]['entry_link'] = trim($link);
            $row[$num]['title'] = ($title) ? trim($title) : trim($link);
            $row[$num]['item_img'] = $this->getItemImgIcon('article_out');
            $row[$num]['entry_id'] = '';
            $row[$num]['link_options'] = ' target="_blank"';
        }
        
        return $row;
    }
    
    
    function getEntryListExternalLink($entry, $type) {
        
        if(!$entry['external_link']) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        $links = $this->parseExternalLink($entry['external_link']);
        foreach($links as $row) {
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign('anchor', 'anchor_entry_external');
        $tpl->tplAssign('title_msg', $this->msg['entry_external_link_msg']);
        $tpl->tplAssign('key', 'external');
        $tpl->tplAssign('icon_key', 'new-window');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryCommentsNum(&$manager) {
        
        $num_comments = $manager->getCommentListCount($this->entry_id);
        if(!$num_comments) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        $row['entry_link'] = $this->getLink('comment', $this->category_id, $this->entry_id);
        $row['title'] = sprintf('%s %s', $num_comments, $this->msg['comment_title_msg']);
        $row['item_img'] = $this->getItemImgIcon('comment');
            
        $tpl->tplParse($row, 'row');
        
        $tpl->tplAssign('anchor', 'anchor_comment');
        $tpl->tplAssign('title_msg', $this->msg['comment_title_msg']);
        $tpl->tplAssign('key', 'comment');
        $tpl->tplAssign('icon_key', 'comment');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getEntryListCategory(&$manager, $type) {
        
        $ret = false;
        $limit_num = $manager->getSetting('num_entries_category');
        if(!$limit_num) {
            return $ret;
        }

        $limit = ($limit_num == 'all') ? -1 : $limit_num + 1;
        $limit = -1;
        
        $sort = $manager->getSortOrder();
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);        
        $rows = $manager->getCategoryEntries($this->category_id, $this->entry_id, $limit);
        $rows_num = count($rows);
        if(!$rows) { 
            return $ret; 
        }
        
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        if($limit_num != 'all' && ($rows_num > $limit_num)) {
            shuffle($rows);
            $rows = array_slice($rows, 0, $limit_num);
        
            $cat_name = $manager->categories[$this->category_id]['name'];
            $cat_id = $this->controller->getEntryLinkParams($this->category_id, $cat_name);
            $tpl->tplAssign('category_link', $this->getLink('index', $cat_id));
            $tpl->tplAssign('category_link_msg', $this->msg['more_entries_msg']);
            $tpl->tplSetNeeded('/category_link');
        }

        $rows = $this->stripVars($rows);
        
        foreach($rows as $k => $row) {
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $row['private'], 'list');
            
            $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $this->category_id, $entry_id);
            $row['entry_id'] = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);

            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_category');
        $tpl->tplAssign('title_msg', $this->msg['entry_category_title_msg']);
        $tpl->tplAssign('key', 'category');
        $tpl->tplAssign('icon_key', 'folder-open');
        
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
        
        
        $tags = $this->getTagsArray($tags, 'article');
        foreach ($tags as $tag) {
            $tpl->tplParse($tag, 'row');
        }

        $tpl->tplAssign('title_msg', $this->msg['tags_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getPrevNextValues(&$manager, $type) {
        
        $limit = 65; // words limit
        $sort = $manager->getSortOrder();
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        $category_id = $this->category_id;
        $categories = &$manager->categories;
        
        
        $nav['next'] = false;
        $nav['prev'] = false;
        
        $entries = $manager->getCategoryEntries($category_id, 0);
        while (list($k, $v) = each($entries)) {
            
            if($v['entry_id'] == $this->entry_id) {
                $num = $k+1;
                if(isset($entries[$num])) {
                    $row = $entries[$num];
                    $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
                    $link = $this->getLink('entry', $category_id, $entry_id);
                    $prefix = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);
                    $title = $this->stripVars($row['title']);
                    $short_title = $this->stripVars($this->getSubstring($row['title'], $limit));
                    
                    $nav['next'] = array('entry_id'     => $row['entry_id'],
                                         'category_id'     => $category_id,
                                         'title'        => $title,
                                         'short_title'    => $short_title,
                                         'prefix'        => $prefix,
                                         'link'            => $link
                                         );
                }
                
                $num = $k-1;
                if(isset($entries[$num])) {
                    $row = $entries[$num];
                    $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
                    $link = $this->getLink('entry', $category_id, $entry_id);
                    $prefix = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);    
                    $title = $this->stripVars($row['title']);
                    $short_title = $this->stripVars($this->getSubstring($row['title'], $limit));
                
                    $nav['prev'] = array('entry_id'     => $row['entry_id'],
                                         'category_id'     => $category_id,
                                         'title'        => $title,
                                         'short_title'    => $short_title,
                                         'prefix'        => $prefix,
                                         'link'            => $link
                                         );
                }                
                
                break;
            }
        }
        
        
        $next_cat = false;
        $prev_cat = false;
        if(empty($nav['next']) || empty($nav['prev'])) {
            
            $tree_helper = &$manager->getTreeHelperArray($categories);
            reset($tree_helper);
            while (list($cat_id, $sort_order) = each($tree_helper)) {
            
                if($cat_id == $category_id) {
                    
                    //next($tree_helper);
                    list($next_cat, $so) = each($tree_helper);                
                    break;    
                }
            }
        
            $prev_cat = $category_id;
            //echo "<pre>"; print_r($tree_helper); echo "</pre>";        
        }
        
        
        if(empty($nav['next'])) {
            if($next_cat) {
                $link = $this->getLink('index', $next_cat);
                $title = $this->stripVars($categories[$next_cat]['name']);
                $short_title = $this->stripVars($this->getSubstring($categories[$next_cat]['name'], $limit));                
                $nav['next'] = array('entry_id'     => false,
                                     'category_id'     => $next_cat,
                                     'title'        => $title,
                                     'short_title'    => $short_title,
                                     'prefix'        => '',
                                     'link'            => $link
                                     );
            }
        }
        
        if(empty($nav['prev'])) {
            if($prev_cat) {
                $link = $this->getLink('index', $prev_cat);
                $title = $this->stripVars($categories[$prev_cat]['name']);
                $short_title = $this->stripVars($this->getSubstring($categories[$prev_cat]['name'], $limit));                
                $nav['prev'] = array('entry_id'     => false,
                                     'category_id'     => $prev_cat,
                                     'title'        => $title,
                                     'short_title'    => $short_title,
                                     'prefix'        => '',                                     
                                     'link'            => $link
                                     );
            }
        }
        
        return $nav;
    }
    
    
    function &getEntryPrevNext(&$manager, $type) {
        
        $ret = '';
        // return $ret;

        $prev_next = $manager->getSetting('nav_prev_next');
        $others = $manager->getSetting('num_entries_category');
        
        if($prev_next == 'no') {
            return $ret;
            
        } elseif($prev_next == 'yes_no_others' && $others) {
            return $ret;
        }
        
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stuff_nextprev.html');
        
        $nav = &$this->getPrevNextValues($manager, $type);
        //echo "<pre>"; print_r($nav); echo "</pre>";
        
        $a['next_msg'] = '';
        if($nav['next']) {
            $a['next_link'] = $nav['next']['link'];
            $a['next_title'] = $nav['next']['title'];
            $a['next_msg'] = $this->msg['next_msg'];
            $a['next_prefix'] = $nav['next']['prefix'];
            $a['next_short_title'] = $nav['next']['short_title'];
        }
        
        $a['prev_msg'] = '';
        if($nav['prev']) {
            $a['prev_link'] = $nav['prev']['link'];
            $a['prev_title'] = $nav['prev']['title'];    
            $a['prev_msg'] = $this->msg['prev_msg'];
            $a['prev_prefix'] = $nav['prev']['prefix'];
            $a['prev_short_title'] = $nav['prev']['short_title'];            
        }
        
        $tpl->tplParse($a);
        return $tpl->tplPrint(1);
    }
}
?>