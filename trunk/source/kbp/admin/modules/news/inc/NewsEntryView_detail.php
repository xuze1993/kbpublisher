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
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryView_detail.php';


class NewsEntryView_detail extends KBEntryView_detail
{
    
    var $template = 'form_detail.html';
    
    
    function execute(&$obj, &$manager, $draft_data = false) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');        
        $template_dir = APP_MODULE_DIR . 'news/template/'; 
        
        
        $tpl = new tplTemplatez($template_dir . $this->template);

        if ($draft_data) {
    
            $tpl->tplSetNeededGlobal('draft_view');
            $tpl->tplAssign('formatted_date', $this->getTimeInterval($draft_data['date_saved']));

            // breadcrumb
            $nav = $this->getBreadcrumb('news', 'news_entry', 0, 'news_autosave');
            $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));
            
            // preview 
            $link = $this->getActionLink('preview', false, array('dkey' => $draft_data['id_key']));    
            $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $tpl->tplAssign('preview_link', $link);

            // links
            $links = $this->getDraftLinks('news', 'news_entry', 'news_autosave', $draft_data['dkey']);
            $tpl->tplAssign($links);

        } else {
    
            // tabs
            $tpl->tplAssign('menu_block', NewsEntryView_common::getEntryMenu($obj, $manager, $this));
    
            // info
            $tpl->tplSetNeededGlobal('entry_view');
            $tpl->tplAssign('formatted_date', $this->getFormatedDate($obj->get('date_posted'), 'date'));

            // preview
            $link = $this->getActionLink('preview', $obj->get('id'));    
            $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $tpl->tplAssign('preview_link', $link);
            
            // $date = $manager->getLastViewed($obj->get('id'));
            // $tpl->tplAssign('last_viewed_formatted', $this->getFormatedDate($date, 'datetime'));
         }

        
        // custom
        $custom_rows = $manager->cf_manager->getCustomField();
        $custom_data = CommonCustomFieldView::getCustomData($obj->getCustom(), $manager->cf_manager, 'checkbox', '');
        foreach($custom_rows as $k => $v) {
            if (isset($custom_data[$k])) {
                $tpl->tplParse($custom_data[$k], 'custom_row');    
            } else {
                $tpl->tplParse($v, 'custom_row');    
            }
        }
		
		
        // tags
        $tpl->tplAssign('tags', implode(', ', $obj->getTag()));
		
                                                                                          
        // status 
        $status = $obj->get('active'); 
        $status_range = array(
            1 => $this->msg['status_published_msg'],
            0 => $this->msg['status_not_published_msg']
            );
                    
        $tpl->tplAssign('status', $status_range[$status]);
        
        // private
        if ($obj->get('private')) {
            $roles_range = $manager->role_manager->getSelectRangeFolow();
            
            $roles = array(
                'read' => array($obj->get('id') => $obj->getRoleRead()),
                'write' => array($obj->get('id') => $obj->getRoleWrite())
            );
            $roles = CommonEntryView::parseEntryRolesMsg($roles, $roles_range, $this->msg);
            
            $tpl->tplAssign('roles', CommonEntryView::getEntryPrivateMsg(@$roles[$obj->get('id')], array(), $this->msg));
            
            $row = $obj->get();
            $row['category_private'] = false;
            $tpl->tplAssign(CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg));
        }
        

        // schedule
        foreach ($obj->getSchedule() as $v) {
            $schedule = $this->parseSchedule($v, $status_range);
            $tpl->tplParse(array_merge($schedule, $this->msg), 'schedule');
        }
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
}
?>