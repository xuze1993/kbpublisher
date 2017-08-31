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


class FileRuleView_obj extends AppView
{
    
    var $template = 'form_obj.html';
    
    
    function execute(&$obj, &$manager) {
        
        $f = new FileEntryView_form();
        $tpl = $f->_executeTpl($obj, $manager, $this->template_dir . $this->template);
        
        
        $cat_records = $manager->getCategoryRecords();
        $deleted_categories = array();
        foreach($obj->getCategory() as $category_id) {
            if (empty($cat_records[$category_id])) {
                $bad_index = array_search($category_id, $obj->category);
                unset($obj->category[$bad_index]);
                $deleted_categories[] = $category_id;
                continue;
            }
        }
        
        if (!empty($deleted_categories)) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = '';
            $msg['body'] = $msgs['note_file_rule_incomplete'];
            $vars['ids'] = sprintf('<b>%s</b>', implode(' ,', $deleted_categories));
            $tpl->tplAssign('msg', BoxMsg::factory('error', $msg, $vars));
        }
        
        
        // author
        $author_id = $obj->get('author_id');
        if ($author_id) {
            $tpl->tplSetNeeded('/author');
            $author = $manager->cat_manager->getAdminUserByIds($author_id);
            
            $tpl->tplAssign('author_id', $author_id);
            $tpl->tplAssign('name', $author[$author_id]); 
        }
        
        // user link
        $more = array('filter[s]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }



/*
    function execute(&$obj, &$manager) {
        
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
        
        
        $deleted_categories = array();
        foreach($obj->getCategory() as $category_id) {
            if (empty($cat_records[$category_id])) {
                $bad_index = array_search($category_id, $obj->category);
                unset($obj->category[$bad_index]);
                $deleted_categories[] = $category_id;
                continue;
            }
        }
        
        if (!empty($deleted_categories)) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = '';
            $msg['body'] = $msgs['note_file_rule_incomplete'];
            $vars['ids'] = sprintf('<b>%s</b>', implode(' ,', $deleted_categories));
            $tpl->tplAssign('msg', BoxMsg::factory('error', $msg, $vars));
        }
        
        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, 'file', 'file_rule'));
        
        
        $select = new FormSelect();
        $select->select_tag = false;        
        
        // status
        $cur_status = ($this->controller->action == 'update') ? $obj->get('active') : false;
        $range = $manager->getListSelectRange('file_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;

        $select->resetOptionParam();
        $select->setRange($range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // tag
        $tags = $obj->getTag();
        if (!empty($tags)) {
            $ids = implode(',', $tags);
            $obj->setTag($manager->tag_manager->getTagByIds($ids));
        }
        
        $this->parseTagBlock($tpl, $xajax, $obj);
        
        // roles
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);

        // custom field
        $this->parseCustomField($tpl, $xajax, $obj, $manager, $cat_records);   
        
        // author
        $author_id = $obj->get('author_id');
        if ($author_id) {
            $tpl->tplSetNeeded('/author');
            $author = $manager->cat_manager->getAdminUserByIds($author_id);
            
            $tpl->tplAssign('author_id', $author_id);
            $tpl->tplAssign('name', $author[$author_id]); 
        }
        
        // user link
        $more = array('filter[s]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);    
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
*/
}
?>