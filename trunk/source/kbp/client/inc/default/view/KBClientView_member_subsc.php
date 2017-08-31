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


class KBClientView_member_subsc extends KBClientView_member
{
    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['my_account_msg'];
        
        $link = $this->controller->getLink('member');
        $this->nav_title = array($link => $this->msg['my_account_msg'], $this->msg['member_subsc_msg']);        
        
        $data = &$this->getForm($manager, $this->msg['my_account_msg']);

        return $data;        
    }    
    
    
    function &getForm($manager) {        
        
        // remove entry
        if(!$manager->isSubscribtionAllowed('entry')) {
            unset($this->manager_2->types[1]);
            unset($this->manager_2->types[11]);
            unset($this->manager_2->types[2]);
            unset($this->manager_2->types[12]);        
        }        
        
        // remove files
        if(!$manager->getSetting('module_file')) {
            unset($this->manager_2->types[2]);
            unset($this->manager_2->types[12]);
        }
        
        // remove news
        if(!$manager->getSetting('module_news') || !$manager->isSubscribtionAllowed('news')) {
            unset($this->manager_2->types[3]);
        }
        
        // remove comment
        if(!$manager->getSetting('allow_comments')) {
            unset($this->manager_2->types[31]);
        }                
        
        $reg = &Registry::instance();
        $controller = &$reg->getEntry('controller');
        $controller->module = 'client';
        
        $view = new $this->viewContainer;
        $view->template_dir = APP_MODULE_DIR . 'user/subscription/template/';
        if($this->mobile_view) {
            $view->template_dir = APP_MODULE_DIR . 'user/subscription/template_mobile/';
        }
        $view->admin_view = false;    
                
        $tpl = new tplTemplatez($this->getTemplate('member_tmpl.html'));
        $tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
        $tpl->tplAssign('content_tmpl', $view->execute($this->obj_2, $this->manager_2));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}
?>