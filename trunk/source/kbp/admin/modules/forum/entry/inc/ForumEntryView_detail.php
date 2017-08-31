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

class ForumEntryView_detail extends AppView
{
    
    var $tmpl = 'form_detail.html';
    
    
    function execute(&$obj, &$manager, $row) {
        
        $this->addMsg('knowledgebase/common_msg.ini');
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $tpl->tplAssign('menu_block', ForumEntryView_common::getEntryMenu($obj, $manager, $this));
                         
        $messages = $manager->getMessages($obj->get('id'));   
        $attachment = $manager->getMessageListAttachment($obj->get('id'));
       
        $categories = $manager->getCategoryRecords();
        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        
        $entry = $full_categories[$obj->get('category_id')];
        $tpl->tplAssign('entry_title', $entry . ' -> ' . $obj->get('title'));
        
        /*$last_comment = $manager->getMessage($obj->get('last_post_id'));
        $tpl->tplAssign('last_comment', $last_comment['message']);*/
       
        $user = $manager->getUserById($obj->get('author_id'));
        $author = $user['first_name'] . ' ' . $user['last_name'];
        $tpl->tplAssign('author', $author);
        
        
        // status
        $status = $obj->get('active'); 
        $status_range = $manager->getListSelectRange('forum_status', true, $status);
        $tpl->tplAssign('status', $status_range[$status]);
        
        // categories
        $cat_records = $this->stripVars($manager->getCategoryRecords());
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        $tpl->tplAssign('category', $categories[$obj->get('category_id')]);
        
        // sticky
        $tpl->tplAssign('is_sticky', ($obj->getSticky()) ? $this->msg['yes_msg'] : $this->msg['no_msg']);
        
        // tags
        $tpl->tplAssign('tags', implode(', ', $obj->getTag()));
        

        $tpl->tplAssign('date_posted_formatted',  $this->getFormatedDate($obj->get('date_posted'), 'datetime'));
        $tpl->tplAssign('date_updated_formatted',  $this->getFormatedDate($obj->get('date_updated'), 'datetime'));
        

        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }  
}
?>