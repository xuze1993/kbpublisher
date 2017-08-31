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

class RoleView_list extends AppView
{
        
    var $tmpl = 'list.html';
    var $padding = 15;
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // header generate
        $tpl->tplAssign('header', $this->commonHeaderList(false, $this->getFilter($manager)));
        
        // filter
        $manager->setSqlParams($this->getFilterSql($manager));        
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $tree_helper = &$manager->getTreeHelperArray($rows);
        
        foreach($tree_helper as $cat_id => $level) {
            
            // $v = $this->getViewListVarsCategory($cat_id, $rows[$cat_id]['active'], $rows[$cat_id]['parent_id']);
            $actions = $this->getListActions($cat_id, $rows[$cat_id]['parent_id']);
            $v = $this->getViewListVarsJs($cat_id, $rows[$cat_id]['active'], $rows[$cat_id]['parent_id'], $actions);
            
            $v['user_num'] = ($rows[$cat_id]['user_num']) ? $rows[$cat_id]['user_num'] : '';
            $v['user_link'] = $this->getLink('users', 'user', false, false, array('filter[role]' => $rows[$cat_id]['id']));
                                    
            $v['class'] = ($level == 0) ? 'trDarker' : 'trLighter';
            $v['padding'] = $this->padding*$level;
            $block = ($level == 0) ? 'level_0' : 'other_level';
            
            $tpl->tplSetNeeded('row/' . $block);
            $tpl->tplParse(array_merge($v, $rows[$cat_id], $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($id, $parent_id) {
        
        $actions = array();
      
        if($this->priv->isPriv('insert')) {
            $link = $this->getActionLink('insert', false, array('parent_id' => $id));
            $actions['insert'] = array(
                'msg'  => $this->msg['new_child_role_msg'],
                'link' => $link
                );
        }
        
        
        $more = array('parent_id' => $parent_id);
        
        $link = $this->getActionLink('update', $id, $more);
        $actions['update'] = array('link' => $link);
        
        $link = $this->getActionLink('delete', $id, $more);        
        $actions['delete'] = array(
            'link' => $link,
            'confirm_msg' => $this->msg['sure_delete_category_msg']
        );
        
        return $actions;
    }    
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg'], 1);
        $sort->setSortItem('status_msg', 'status', 'active', $this->msg['status_msg']);
        $sort->setSortItem('users_msg', 'user_num', 'user_num', $this->msg['users_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_order', 'sort_order', $this->msg['sort_order_msg']);    
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // category
        $range = $manager->getSelectRangeByParentId(0);
        $select->setRange($range, array('all'=>$this->msg['all_roles_msg'], 
                                        'top'=>$this->msg['top_roles_msg']));

        @$category_id = $_GET['filter']['c'];
        $tpl->tplAssign('category_select', $select->select($category_id));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse(@$_GET['filter']);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
    
        // filter
        @$v = $_GET['filter']['c'];
        $arr = array();
        $top_category_id = 0;
        $categories = $manager->getSelectRecords();
        
        if($v == 'all') {
            $arr = array();
            
        } elseif($v == 'top') {
            $arr[] = "AND r.parent_id = '$top_category_id'";
        
        } elseif(!empty($v)) {
            $category_id = (int) $v;
            $child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
            $child = implode(',', $child);
            
            $arr[] = "AND r.id IN($child)";
        }
        
        return implode(" \n", $arr);
    }    
}
?>
