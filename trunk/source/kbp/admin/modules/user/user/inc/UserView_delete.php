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

require_once APP_MODULE_DIR . 'user/user/inc/UserView_detail.php';


class UserView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('user_msg.ini');    
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        $eview = new UserView_detail;
        $eview->msg = $this->msg;
        $block_rows = $eview->getUserActivityArray($data, $obj->get('id'), 'red');
        
        foreach ($block_rows as $section_key => $section_str) {
            $row = array();
            $row['section_str'] = $section_str;
            
            if ($section_key == 'supervisor' && !$eview->critical_activity) {
                $tpl->tplSetNeeded('row/user_popup');
                $more = array('limit' => 1, 'close' => 1);
                $link = $this->getLink('users', 'user', false, false, $more);
                
                $tpl->tplAssign('user_popup_link', $link);
            }
                
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        if ($eview->critical_activity) {
            $error_key = 'nondeletable_user';
            $tpl->tplSetNeeded('/back_button');
        } else {
            $error_key = 'note_user_delete';
            $tpl->tplSetNeeded('/delete_button');
        }
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox($error_key));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>