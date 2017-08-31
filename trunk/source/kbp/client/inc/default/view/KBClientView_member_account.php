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


class KBClientView_member_account extends KBClientView_member
{
    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['my_account_msg'];
        
        $link = $this->controller->getLink('member');
        $this->nav_title = array(
            $link => $this->msg['my_account_msg'], 
            $this->msg['member_account_msg']);
        
        $data = $this->getForm($manager, $this->msg['member_account_msg']);

        return $data;        
    }    


    function getForm($manager, $title = false) {
        
        $reg = &Registry::instance();
        $controller = &$reg->getEntry('controller');
        $controller->module = 'client';
        
        $view = new UserView_detail();
        $view->account_view = true;
        $view->admin_view = false;
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/'; 
        
        
        $tpl = new tplTemplatez($this->getTemplate('member_tmpl.html'));
        $tpl->tplAssign('member_menu', $this->getMemberMenu($manager));        
        $tpl->tplAssign('content_tmpl', $view->execute($this->obj_2, $this->manager_2, $title));
           
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>