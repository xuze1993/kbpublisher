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

class KBEntryView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
        
        $this->addMsg('bulk_msg.ini', false, 'bulk_common');
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // tag
        $items = array('add', 'set', 'remove');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_tag');
        $select->setRange($range);
        $tpl->tplAssign('tag_action_select', $select->select());
        
        $link = $this->getLink('knowledgebase', 'kb_entry', false, 'tags');
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link));

        // private
        if($manager->bulk_manager->isActionAllowed('private')) {
            $tpl->tplSetNeeded('/private');
            $tpl->tplAssign('block_private_tmpl', PrivateEntry::getPrivateBulkBlock($obj, $manager));
            $xajax->registerFunction(array('loadRoles', $this, 'ajaxLoadRoles'));
        }
        
        // type
        $extra_range = array(0 => $this->msg['remove_entry_type_msg']);
        $range = $manager->getListSelectRange('article_type', true);
        $select->setRange($range, $extra_range);
        $tpl->tplAssign('type_select', $select->select());     
        
        // status
        $status_range = array();
        if($manager->bulk_manager->isActionAllowed('status')) {
            $range = $manager->getListSelectRange('article_status', true);
            $range = $this->priv->getPrivStatusSet($range, 'select');
            $status_range = &$range;
            
            if($range) {
                $select->setRange($range);
                $tpl->tplAssign('status_select', $select->select());                
            } else {
                $manager->bulk_manager->removeActionAllowed('status'); // no range, remove from actions
            }            
        }
        
        // schedule
        if($manager->bulk_manager->isActionAllowed('schedule') && $status_range) {
            $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range, true));
            
            $items = array('set', 'remove');
            $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_schedule');
            $select->setRange($range);
            $tpl->tplAssign('schedule_action_select', $select->select());
        } else {
            $manager->bulk_manager->removeActionAllowed('schedule');
        }

        // author
        if($manager->bulk_manager->isActionAllowed('author')) {
            $more = array('limit' => 1, 'close' => 1);
            $link = $this->getLink('users', 'user', false, false, $more);
            $tpl->tplAssign('user_popup_link', $link); 
        }

        // custom
        $filtered_cat = (isset($_GET['filter']['c'])) ? (int) $_GET['filter']['c'] : 0; 
        $tpl->tplAssign('filtered_cat', $filtered_cat);
        
        $xajax->registerFunction(array('parseCutomBulkAction', $this, 'ajaxParseCutomBulkAction'));
        $xajax->registerFunction(array('addTag', $this, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $this, 'ajaxGetTags'));
        
        // actions js 
        $actions_allowed = $manager->bulk_manager->getActionsAllowed();
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($actions_allowed)) . "'");
        
        // action
        @$val = $values['action'];
        $range = $manager->bulk_manager->getActionsMsg('bulk_kbentry');
        $select->setRange($range, array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select($val));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxParseCutomBulkAction($cat_id) {
        return CommonCustomFieldView::ajaxParseCutomBulkAction($cat_id, $this->manager, $this->msg);
    }
    
    
    function ajaxAddTag($string) {
        return CommonEntryView::ajaxAddTag($string, $this->manager);
    }
     
    
    function ajaxGetTags($limit = false, $offset = 0) {
        return CommonEntryView::ajaxGetTags($limit, $offset, $this->manager);
    }
    
    function ajaxLoadRoles($ids) {
        return PrivateEntry::ajaxLoadRoles($ids, $this->manager);
    }
    
}
?>