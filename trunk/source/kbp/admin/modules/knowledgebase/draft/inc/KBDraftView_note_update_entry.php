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


class KBDraftView_note_update_entry extends AppView 
{
    
    var $template = 'note_update_entry.html';
 

    function execute(&$obj, &$manager, $allowed) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $box_type = ($allowed) ? 'hint' : 'error';
        $msg_body_key = (empty($_GET['vnum'])) ? 'note_entry_drafted' : 'note_entry_drafted_rollback';  
        $msg_body_key = ($obj->sent_to_approval) ? 'note_entry_drafted_submitted' : $msg_body_key;
        if (!$allowed) {
            $msg_body_key = 'note_entry_drafted_restricted';
        }
        
        $vars['draft_exist_ignore_msg'] = $this->msg['draft_exist_ignore_msg'];
        $vars['draft_exist_ignore_rollback_msg'] = $this->msg['draft_exist_ignore_rollback_msg'];
        if (!empty($_GET['vnum'])) {
            $vars['vnum'] = $_GET['vnum'];
        }
        
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = $msgs['title_entry_drafted'];
        $msg['body'] = $msgs[$msg_body_key];
        $tpl->tplAssign('msg', BoxMsg::factory($box_type, $msg, $vars));
        
        $tpl->tplAssign('date_posted_formatted', 
                $this->getFormatedDate($obj->get('date_posted'), 'datetime'));
        $tpl->tplAssign('date_updated_formatted', 
                $this->getFormatedDate($obj->get('date_updated'), 'datetime'));
        
        
        if($user = $manager->getUser($obj->get('author_id'))) {
            $tpl->tplAssign('user_name', $user['first_name'] . ' ' . $user['last_name']);
            $tpl->tplAssign($user);            
        }
        
        if ($allowed) {
            if ($obj->sent_to_approval) {
                $tpl->tplSetNeeded('/details_button');
                $update_link = $this->getActionLink('detail', $obj->get('id'));
                $tpl->tplAssign('detail_link',  $update_link);
                
            } else {
                $tpl->tplSetNeeded('/edit_buttons');
                $update_link = $this->getActionLink('update', $obj->get('id'));
                $tpl->tplAssign('update_link',  $update_link);
                
                $ignore_button_value_key = (empty($_GET['vnum'])) ? 'draft_exist_ignore_msg' : 'draft_exist_ignore_rollback_msg'; 
                $tpl->tplAssign('ignore_button_value', $this->msg[$ignore_button_value_key]);
            }
            
        } else {
            $this->msg['cancel_msg'] = $this->msg['back_msg'];
        }
        
        // title field
        $file = ($this->controller->module == 'file');
        $field_title = ($file) ?  $this->msg['filename_msg'] : $this->msg['title_msg'];
        $tpl->tplAssign('field_title', $field_title);
        
        // set more params to populate in cancel link        
        $vars = $this->setCommonFormVars($obj);
        
        // if referer
        if(isset($_GET['referer'])) {
            $link = array('entry', false, $obj->get('entry_id'));// this if from client
            $vars_ = $this->setRefererFormVars($_GET['referer'], $link);            
            $vars['cancel_link'] = $vars_['cancel_link'];
            
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>