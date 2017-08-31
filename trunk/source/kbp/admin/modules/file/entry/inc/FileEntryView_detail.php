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


class FileEntryView_detail extends AppView
{
    
    var $template = 'form_detail.html';
    
    var $draft_view = false;
    
    var $module = 'file';
    var $page = 'file_entry';
    
    
    function execute(&$obj, &$manager, $draft_data = false) {

        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');        
        
        $template_dir = APP_MODULE_DIR . 'file/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
            
        // tabs
        $prefix = ($this->controller->page == 'file_entry') ? 'Entry' : 'Draft';
        $class = sprintf('File%sView_common', $prefix);
        
        if ($draft_data) {
            list($draft_obj, $draft_manager) = $draft_data;
            $tpl->tplAssign('menu_block', FileDraftView_common::getEntryMenu($draft_obj, $draft_manager, $this, $manager));
            
            if ($draft_obj->get('entry_id')) {
                $tpl->tplSetNeeded('/entry_id2');
                $tpl->tplAssign('id2', $draft_obj->get('entry_id'));
            }
            
        } else {
            
            $tpl->tplSetNeededGlobal('entry_view');
            
            // tabs
            $tpl->tplAssign('menu_block', FileEntryView_common::getEntryMenu($obj, $manager, $this));
            
            // attached to
            $related_to_num = '';
            $related_to = $manager->getReferencedArticlesNum($obj->get('id'));
            if(!empty($related_to)) {
                $related_to_num = count($related_to);
                $more = array('filter[q]'=>'attachment:' . $obj->get('id'));
                $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                $tpl->tplAssign('attached_to_link', $link);
            }
            
            $tpl->tplAssign('attached_to_num', $related_to_num);
            
            // draft
            $draft_id = $manager->isEntryDrafted($obj->get('id'));
            if ($draft_id) {
                $tpl->tplSetNeeded('/draft');
                
                $more = array('id' => $draft_id);
                $link = $this->getLink('file', 'file_draft', false, 'detail', $more);
                $tpl->tplAssign('draft_link', $link);
            }
        }
        
        CommonEntryView::parseInfoBlock($tpl, $obj, $this);
        
        if ($obj->get('id')) {
            $date = $manager->getLastViewed($obj->get('id'));
            $tpl->tplAssign('last_viewed_formatted', $this->getFormatedDate($date, 'datetime'));
        }
                                       
        // categories
        $cat_records = $this->stripVars($manager->getCategoryRecords());
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        
        $category = array();
        foreach($obj->getCategory() as $category_id) {
            $category[] = $categories[$category_id];
        }
        $tpl->tplAssign('category', implode('<br>', $category));

        
        // tags
        $tpl->tplAssign('tags', implode(', ', $obj->getTag()));
        
        
        // custom
        $custom_rows = $manager->cf_manager->getCustomField($cat_records, $obj->getCategory());
        $custom_data = CommonCustomFieldView::getCustomData($obj->getCustom(), $manager->cf_manager, 'checkbox', '');
        foreach($custom_rows as $k => $v) {
            if (isset($custom_data[$k])) {
                $tpl->tplParse($custom_data[$k], 'custom_row');    
            } else {
                $tpl->tplParse($v, 'custom_row');    
            }
        }
        
                                                                      
        // status
        $status = $obj->get('active'); 
        $status_range = $manager->getListSelectRange('file_status', true, $status);
        
        if (!$this->draft_view) {
            $tpl->tplSetNeeded('/status');
            $tpl->tplAssign('status', $status_range[$status]);
        }
        
        // private        
        if ($obj->get('private')) {
            $roles_range = $manager->role_manager->getSelectRangeFolow();
            
            $roles = array(
                'read' => array($obj->get('id') => $obj->getRoleRead()),
                'write' => array($obj->get('id') => $obj->getRoleWrite())
            );
            $roles = CommonEntryView::parseEntryRolesMsg($roles, $roles_range, $this->msg);
            
            $category_roles = $manager->cat_manager->getRoleById($obj->get('category_id'), 'id_list');
            $category_roles = CommonEntryView::parseEntryCategoryRolesMsg($category_roles, $roles_range, $this->msg);
            
            $tpl->tplAssign('roles', CommonEntryView::getEntryPrivateMsg(@$roles[$obj->get('id')], @$category_roles[$obj->get('category_id')], $this->msg));
            $row = $obj->get();
            $row['category_private'] = $manager->cat_manager->isPrivate($obj->get('category_id'));
            $tpl->tplAssign(CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg));
        }
                
        // schedule
        foreach ($obj->getSchedule() as $v) {
            $schedule = $this->parseSchedule($v, $status_range);
            $tpl->tplParse(array_merge($schedule, $this->msg), 'schedule');
        }
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        if ($this->draft_view) {
            $tpl->tplAssign('id', $draft_obj->get('id'));
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseSchedule($schedule, $status_range) {
        $sh_status = (isset($schedule['st'])) ? $schedule['st'] : 0;
        $note = (isset($schedule['note'])) ? $schedule['note'] : '';
        $timestamp = (isset($schedule['date'])) ? $schedule['date'] : time();

        $a = array();
        $a['schedule_note'] = $note;
        $a['schedule_status'] = $status_range[$sh_status];
        $a['schedule_date'] = $this->getFormatedDate($timestamp, 'datetime');

        return $a;
    }
}
?>