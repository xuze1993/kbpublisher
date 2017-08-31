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


class CustomFieldRangeValueView_list extends AppView
{
    
    var $template = 'list_value.html';
    var $template_popup = 'list_value_popup.html';
    
    
    function execute(&$obj, &$manager) {

        $this->addMsg('custom_field_msg.ini');
        
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        $tpl = new tplTemplatez($this->template_dir . $tmpl);

        // breadcrumb
        $more = array();
        if ($this->controller->getMoreParam('popup')) {
            $more['popup'] = 1;
        }

        $menu_msg = AppMsg::getMenuMsgs('field_tool'); 

        $list_link = $this->getLink('this', 'this', 'this', false, $more);
        
        $update_link = false;
        if($this->priv->isPriv('update')) {
            $more['id'] = $obj->get('range_id');
            $update_link = $this->getLink('this', 'this', 'this', 'update_group', $more);
        }

        $nav = array();
        $nav[1] = array('link' => $list_link, 'item'=>$menu_msg['ft_range']);
        $nav[2] = array('link' => $update_link, 'item'=>$obj->group_title);
        $nav[3]['item'] = $this->msg['range_values_msg'];
        
        $breadcrumb = $this->getBreadCrumbNavigation($nav);
        $tpl->tplAssign('nav', $breadcrumb);        


        // header
        $button = array();
        if ($this->controller->getMoreParam('popup')) {
            // $set_range_str = "javascript: window.top.setRange('%d'); PopupManager.close(); void(0);";
            // $button[$this->msg['set_range_msg']] = sprintf($set_range_str, $obj->get('range_id'));
			$tpl->tplAssign('range_id', $obj->get('range_id'));
            
        }
        
		$tpl->tplAssign('header', $this->commonHeaderList(false));
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
         
        // get records
        $rows = $this->stripVars($manager->getRecords());
        
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), 1, 1, array('update', 'delete')));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }                
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('', 'sort', 'sort_order', '', 1);
        
        return $sort;
    }

}
?>