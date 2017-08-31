<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class PrivView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        
        // header
        $button = array();
        $button[] = 'insert';

        if($this->priv->isPriv('update')) {
            $pmenu = array();
            $pmenu[] = array(
                'msg' => $this->msg['manage_priv_levels_msg'], 
                'link' => 'javascript:xajax_getSortableList();void(0);'
                );
            $button['...'] = $pmenu;
        }
        
        $tpl->tplAssign('header', $this->commonHeaderList('', '', $button, false));
        
        foreach($rows as $k => $row) {
            
            $obj->set($row);
            
            $user_num = ($row['user_num']) ? $row['user_num'] : '';
            $user_link = $this->getLink('users', 'user', false, false, array('filter[priv]' => $obj->get('id')));
  
            $tpl->tplAssign('user_num', $user_num);
            $tpl->tplAssign('user_link', $user_link);
  
            $tpl->tplAssign($this->getViewListVarsCustomJs($obj->get('id'), $obj->get('active'), $obj->get('editable')));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxSetSortableList'));
		
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('priv_level_msg', 'privl', 'sort_order', $this->msg['priv_level_msg'], 1);        
        //$sort->setSortItem('title_msg',  'title', 'n.name', $this->msg['title_msg']);
        $sort->setSortItem('users_msg','user_num', 'user_num', $this->msg['users_msg']);
        $sort->setSortItem('status_msg','status', 'active', $this->msg['status_msg']);
        
        return $sort;
    }
    
    
    function getViewListVarsCustomJs($record_id = false, $active = false, $editable = false) {
        
        $actions = array('clone', 'status', 'update', 'delete');

        if(!$editable) {
            $active = ($active == 0) ? 'not' : 'not_checked';
            unset($actions[0], $actions[3]);
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);

        return $row;
    }
    
    
    function ajaxSetSortableList() {
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');
        
        $tpl->tplAssign('hint', AppMsg::hintBoxCommon('privilege_level'));
        
        $rows = $this->manager->getRecords();
        
        foreach($rows as $row) {
            if ($row['sort_order'] == 1) {
                $tpl->tplAssign('admin_name', $row['name']);
                
            } else {
                $tpl->tplParse($row, 'row');
            }
        }
        
        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);
        
        $tpl->tplParse($this->msg);        
        $objResponse->addAssign('priv_list', 'innerHTML', $tpl->tplPrint(1));
        
        $objResponse->call('initSort');
    
        return $objResponse;    
    }
     
}
?>
