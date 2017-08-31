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

class SubscriptionView_form_articles_cat extends SubscriptionView_form
{
        
    var $tmpl = 'form.html';
    var $admin_view = true;

    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // note message
        $tpl->tplAssign('user_note_msg', AppMsg::hintBoxCommon('note_category_subscribe'));
        
          
        $am = $this->getCategoryManager($manager);
        $am->cat_manager->setSqlParams("AND active = 1");
        // $categories = $this->stripVars($am->getCategoryRecords(), array(), 'addslashes');
        $categories = $this->stripVars($am->getCategoryRecords());
        $categories = $am->getCategorySelectRangeFolow($categories);
        
        $ids = $this->getIds($manager);
        $js = $this->createJsObj($categories, $ids);
        $js[0] = sprintf("{id: 0, title: \"%s\"},\n%s", $this->msg['subscribe_all_msg'], $js[0]);
        
        $tpl->tplAssign('categories', $js[0]);
        $tpl->tplAssign('disabled', $js[1]);
        
        // link to js files
        $path = APP_ADMIN_PATH;
        $tpl->tplAssign('path', $path);
        
        
        if($this->admin_view) {
            $link = $this->getActionLink('insert', null); 
            $clink = $this->getLink('this', 'this', false, false, array('type' => $_GET['type']));
        } else {   
            $link = $this->getClientActionLink();
            $clink = $this->getClientCancelLink();
        }
        
        $tpl->tplAssign('action_link', $link);
        $tpl->tplAssign('cancel_link', $clink);

        //$tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getCategoryManager($manager) {
        return $manager->getArticleManager();
    }
    
    function getClientActionLink() {
        return $_SERVER['PHP_SELF'] . '?View=member_subsc&action=insert&type=11';
    }
        
    function getClientCancelLink() {
        return $_SERVER['PHP_SELF'] . '?View=member_subsc&type=11';
    }
}
?>