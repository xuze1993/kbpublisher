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


class ForumMessageView_form extends AppView
{
	
	var $template = 'form_message.html';
	
	
	function execute(&$obj, &$manager) {
		
		$this->addMsg('user_msg.ini');
	
		$tpl = new tplTemplatez($this->template_dir . $this->template);
		$tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $entry = $manager->getById($obj->get('entry_id'));
        $link = $this->getLink('forum', 'forum_entry', false, false, array('filter[q]' => 'id:' . $obj->get('entry_id')));  
        
        $tpl->tplAssign('entry_link', $link);
        $tpl->tplAssign('entry', $entry['title']);
        $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($obj->get('date_posted')), 'datetime');
        
        
        $attachment = $manager->getMessageAttachment($obj->get('id'));
        
        if (!empty($attachment)) {
            foreach($attachment as $attach) {
                $link = $this->getLink('forum', 'forum_entry', false, 'file', array('id' => $attach['id']));
                $attach['download_link'] = $link;
                                                                                                 
                $tpl->tplParse($attach, 'attachment_block/attachment');
            }
                
            $tpl->tplSetNested('attachment_block/attachment');
            $tpl->tplParse($this->msg, 'attachment_block');
        }
        
        $user = $manager->getUserById($obj->get('user_id'));

        $ustring = ' <a href="%s"><b>%s %s</b></a>';
        $link = $this->getLink('users', 'user', false, 'update', array('id' => $user['id']));
        $user_string = sprintf($ustring, $link, $user['first_name'], $user['last_name']);
        
        $tpl->tplAssign('user', $user_string);                                                             

		$tpl->tplAssign($this->setCommonFormVars($obj));
		$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        
        $tpl->tplAssign('cancel_link', $this->getLink('forum', 'forum_entry', false, 'detail', array('id' => $obj->get('entry_id')))); 
        
		$tpl->tplAssign($obj->get());
		$tpl->tplAssign($this->msg);
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
}
?>