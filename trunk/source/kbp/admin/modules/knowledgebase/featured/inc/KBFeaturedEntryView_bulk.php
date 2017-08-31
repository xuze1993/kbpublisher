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
   

class KBFeaturedEntryView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
        
        $this->addMsg('user_msg.ini');
            
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($manager->bulk_manager->getActionsAllowed())) . "'");
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // action
        @$val = $values['action'];
        $action_range = $manager->bulk_manager->getActionsMsg('bulk_featured');
        
        if (!empty($action_range['remove_from'])) {
            if ($view->category_id) {
                $categories = $manager->emanager->getCategoryRecords();
                $category_name = $categories[$view->category_id]['name'];
                
            } else {
                $category_name = $view->msg['index_page_msg'];
            }
            
            $action_range['remove_from'] = sprintf('%s - %s', $action_range['remove_from'], $category_name);
        }
        
        $select->setRange($action_range, array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select($val));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>