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


class CustomFieldView_form_field_type extends AppView
{
    
    var $tmpl = 'form_field_type.html';
    
    
    function execute(&$obj, &$manager, $form_view) {
        
        $this->addMsg('custom_field_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        
        $skip_tabs = array();
        
        if(!$form_view->display_option) {
            $skip_tabs[] = 'display_options';
        }
        
        
        if (!$form_view->has_categories) {
            $skip_tabs[] = 'categories';
        }
        
        $tabs = $manager->getTabsRange($this->msg, $skip_tabs);
        foreach ($tabs as $id => $title) {
            $row['id'] = $id;
            $row['title'] = $title;
            $tpl->tplParse($row, 'tab_row');
        }
        
        $field_type = $manager->getFieldTypeSelectRange($this->msg);

        foreach ($field_type as $id => $field) {
            $row = $field;
            $row['create_link'] = $this->getActionLink('insert', false, array('input_id' => $id));

            $tpl->tplAssign($this->getViewListVarsRow());             
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }

        $tpl->tplAssign($this->msg);  
                
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>