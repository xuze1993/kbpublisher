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

class ForumMessageView_list extends AppView
{
    
    var $tmpl = 'list_message.html';
    
    
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
       
        $user = $manager->getUserById($obj->get('author_id'));
        $author = $user['first_name'] . ' ' . $user['last_name'];
        $tpl->tplAssign('author', $author);
        
        $tpl->tplAssign('date_posted_formatted',  $this->getFormatedDate($obj->get('date_posted'), 'datetime'));
        $tpl->tplAssign('date_updated_formatted',  $this->getFormatedDate($obj->get('date_updated'), 'datetime'));
        
        $client_controller = &$this->controller->getClientController();
        
        foreach($messages as $id => $row) {
                                      
            $tpl->tplAssign('content', nl2br($row['message']));
            
            $user_string = ($row['user_id']) ? $row['first_name'] . ' ' . $row['last_name'] : $row['from_email'];
            $tpl->tplAssign('user', $user_string);
            
            $tpl->tplAssign('date',  $this->getFormatedDate($row['date_posted'], 'datetime'));
            
            $edit_link = $this->controller->getLink('forum', 'forum_entry', false, 'message', array('id' => $id));
            $tpl->tplAssign('edit_message_link', $edit_link);

            // message has attachments
            if (isset($attachment[$id])) {        
             
                foreach($attachment[$id] as $attach) {
                    $a['filename'] = $attach['filename'];
                    $a['download_link'] = $client_controller->getLink('topic', false, $obj->get('id'), 'dfile', array('id' => $attach['id']));
                                                                                                 
                    $tpl->tplParse($a, 'row/attachment_block/attachment');
                }
                
                $tpl->tplSetNested('row/attachment_block/attachment'); 
                $tpl->tplParse($a, 'row/attachment_block');
            }
            
            $tpl->tplSetNested('row/original');
            $tpl->tplSetNested('row/attachment_block'); 
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }    
        
        $select = new FormSelect(); 
        $select->setFormMethod($_POST);
        $select->select_tag = true;
        

        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }  
}
?>