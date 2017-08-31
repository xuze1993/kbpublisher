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


class CustomFieldRangeValueView_form extends AppView
{
    
    var $tmpl = 'form_value.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('custom_field_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        // breadcrumb
        $more = array();
        if ($this->controller->getMoreParam('popup')) {
            $more['popup'] = 1;
        }
        
        $menu_msg = AppMsg::getMenuMsgs('field_tool'); 

        $list_link = $this->getLink('this', 'this', 'this', false, $more);
        
        $more['id'] = $obj->get('range_id');
        $update_link = $this->getLink('this', 'this', 'this', 'update_group', $more);

        unset($more['id']);
        $more['range_id'] = $obj->get('range_id');
        $list_link2 = $this->getLink('this', 'this', 'this', false, $more);

        $nav = array();
        $nav[1] = array('link' => $list_link, 'item'=>$menu_msg['ft_range']);
        $nav[2] = array('link' => $update_link, 'item'=>$obj->group_title);
        $nav[3] = array('link' => $list_link2, 'item'=>$this->msg['range_values_msg']);
        $nav[4]['item'] = ($this->controller->action == 'update') ? $obj->get('title') : $this->msg['add_new_msg'];
        
        $breadcrumb = $this->getBreadCrumbNavigation($nav);
        $tpl->tplAssign('nav', $breadcrumb);
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>
