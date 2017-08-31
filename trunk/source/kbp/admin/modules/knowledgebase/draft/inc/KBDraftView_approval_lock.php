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


class KBDraftView_approval_lock extends AppView 
{
    
    var $template = 'form_approval_lock.html';
 

    function execute(&$obj, &$manager) {
        
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = $msgs['title_entry_locked'];
        $msg['body'] = $msgs['note_draft_locked'];
        $tpl->tplAssign('msg', BoxMsg::factory('error', $msg));

        $locked = $manager->getEntryLockedData($obj->get('id'));
        $tpl->tplAssign('date_period',  $this->getTimeInterval($locked['date_locked'], false));
        
        if($user = $manager->getUser($locked['user_id'])) {
            $tpl->tplAssign('user_name', $user['first_name'] . ' ' . $user['last_name']);
            $tpl->tplAssign($user);            
        }        
        
        $tpl->tplSetNeeded('/ignore');
        
        
        // set more params to populate in cancel link        
        $vars = $this->setCommonFormVars($obj);
        
        // if referer
        if(isset($_GET['referer'])) {
            $link = array('entry', false, $obj->get('id'));
            $vars_ = $this->setRefererFormVars($_GET['referer'], $link);            
            $vars['cancel_link'] = $vars_['cancel_link'];
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg); 

        
        $more = array('id'=>$obj->get('id'));
        if(isset($_GET['referer'])) {
            $more['referer'] = $_GET['referer'];
        }
         
        $link = $this->controller->getLink('this', 'this', false, 'approval', $more);
        $tpl->tplAssign('approval_link', $link);
          
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>