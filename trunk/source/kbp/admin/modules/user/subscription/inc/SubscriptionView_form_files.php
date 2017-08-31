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

class SubscriptionView_form_files extends SubscriptionView_form
{
        
    var $tmpl = 'form.html';
    

    function execute(&$obj, &$manager) {
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl); 
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // $categories = $this->stripVars($manager->file_cat_manager->getSelectRecords(), array(), 'addslashes');                   
        $categories = $this->stripVars($manager->file_cat_manager->getSelectRecords());                   
        $categories = $manager->getCategorySelectRangeFolow($categories);
        
        $ids = $this->getIds($manager);
        $js = $this->createJsObj($categories, $ids);       
 
        $tpl->tplAssign('categories', $js[0]);
        $tpl->tplAssign('disabled', $js[1]);
                            
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }   
}
?>