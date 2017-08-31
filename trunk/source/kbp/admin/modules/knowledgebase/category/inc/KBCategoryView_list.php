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

require_once 'core/common/CommonCategoryView.php';


class KBCategoryView_list extends AppView
{
        
    var $tmpl = 'list.html';
    var $padding = 15;
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $supervisor_id = false;
        if (!empty($_GET['filter']['supervisor_id'])) {
            $supervisor_id = $_GET['filter']['supervisor_id'];
            $tpl->tplSetNeeded('/supervisor_highlight');
        }
        
        //private
        $manager->setSqlParams($manager->getPrivateParams());
        
        // bulk
        $manager->bulk_manager = new KBCategoryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', 
                $this->controller->getView($obj, $manager, 'KBCategoryView_bulk', $this));
        }        
        
        // filter
        $manager->setSqlParams($this->getFilterSql($manager), 'filter');
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $tree_helper = &$manager->getTreeHelperArray($rows); //$top_category_id
        $ids = $manager->getValuesString($rows, 'id');
        
        $this->full_categories = $manager->getSelectRangeFolow($rows);
        
        // header generate
        $button = CommonCategoryView::getButtons($this);
        $tpl->tplAssign('header', $this->commonHeaderList(false, $this->getFilter($manager), $button));
        
        //echo '<pre>', print_r($rows, 1), '</pre>';
        //echo "<pre>"; print_r($tree_helper); echo "</pre>";
        
        //num entries per category
        $num_entry = ($ids) ? $manager->getEntriesNum($ids) : array();
        
        // role to category
        $roles_range = $manager->getRoleRangeFolow();
        $roles = ($ids) ? $manager->getRoleById($ids, 'id_list') : array();
        
        // supervisor
        $supervisor = ($ids) ? $manager->getAdminUserById($ids, 'id_list') : array();
        $cat_type_msg = $manager->getCategoryTypeSelectRange();
        
        // count childs cats
        $manager->setSqlParams(false, 'filter');
        $categories = $manager->getSelectRecords();
        $ids = array_keys($manager->getSelectRangeByParentId(0));
        $child = $manager->getChildCategories($categories, $ids);
        
        $highlighted_ids = array();
        
        foreach(array_keys($tree_helper) as $cat_id) {
            $level = $tree_helper[$cat_id];
            
            $all_children = $manager->getChildCategories($categories, $cat_id);
            $direct_children = array();
        
            foreach($all_children as $child_id) {
                if($categories[$child_id]['parent_id'] == $cat_id) {
                    $direct_children[$child_id] = $child_id;
                }
            }
            
            $actions = $this->getListActions($cat_id, $rows[$cat_id]['parent_id'], $direct_children);
            $tpl->tplAssign($this->getViewListVarsJs($cat_id, $rows[$cat_id]['active'], true, $actions));
            
            $v['num_subcat'] = '';
            if($rows[$cat_id]['parent_id'] == 0 && isset($child[$cat_id])) {
                $v['num_subcat'] = count($child[$cat_id]);
            }
            
            //$rows[$cat_id]['description'] = nl2br($rows[$cat_id]['description']);
            $v['num_entries'] = (isset($num_entry[$cat_id])) ? $num_entry[$cat_id] : '';
            $v['entry_link'] = $this->getLink('knowledgebase', 'kb_entry', false, false, array('filter[c]'=>$cat_id));
            $v['top_category_link'] = $this->getLink('knowledgebase', 'kb_category', false, false, array('filter[c]'=>$cat_id));
            
            $rows[$cat_id]['category_type'] = $cat_type_msg[$rows[$cat_id]['category_type']];
            
            
            // private&roles
            $v['private1_msg'] = CommonCategoryView::getListPrivatesMsg($rows[$cat_id]['private'], $this->msg);
            $v['roles_msg']  = CommonCategoryView::getListRolesMsg($roles, $cat_id, $roles_range, $this->msg);
            
            if($rows[$cat_id]['private']) {
                $tpl->tplSetNeeded('row/if_private');
            }
            
            // supervisor
            $v['admin_user'] = '';
            if(isset($supervisor[$cat_id])) {
                $v['admin_user'] = implode('<br />', $supervisor[$cat_id]);
                
                if (in_array($supervisor_id, array_keys($supervisor[$cat_id]))) {
                    $highlighted_ids[] = $cat_id;
                }
            }
            
            if($rows[$cat_id]['commentable']) {
                $tpl->tplSetNeeded('row/if_commentable');
            }
            
            if($rows[$cat_id]['ratingable']) {
                $tpl->tplSetNeeded('row/if_ratingable');
            }                               
            
            // $v['class'] = ($level == 0) ? 'trDarker' : 'trLighter';
            $v['padding'] = $this->padding*$level;
            $block = ($level == 0) ? 'level_0' : 'other_level';
            
            $tpl->tplSetNeeded('row/' . $block);
            $tpl->tplParse(array_merge($v, $rows[$cat_id]), 'row');
            unset($rows[$cat_id]);            
        }
        
        if (!empty($highlighted_ids)) {
            $tpl->tplAssign('highlighted_ids', implode(',', $highlighted_ids));
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));
        
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
    
    function parseTitle() {
        $values = array();
        // $values['commentable_msg'] = $this->shortenTitle($this->msg['commentable_msg'], 4, $this->msg['comment_msg']);
        // $values['ratingable_msg'] = $this->shortenTitle($this->msg['ratingable_msg'], 4, $this->msg['rating_msg']);

        return $values;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        //$order = $this->getSortOrderSetting();
        $sort->setDefaultSortItem('sort_order', 1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('name_msg',  'name', 'name', $this->msg['name_msg']);
        $sort->setSortItem('private_msg', 'private', 'private', $this->msg['private_msg']);
        $sort->setSortItem('commentable_msg','comment', 'commentable', $this->msg['commentable_msg']);
        $sort->setSortItem('ratingable_msg','rate', 'ratingable', $this->msg['ratingable_msg']);                
        $sort->setSortItem('category_type_msg','type', 'category_type', $this->msg['category_type_msg']);
        $sort->setSortItem('sort_order_msg','sort_order', 'sort_order', array($this->msg['sort_order_msg'], 5));
        $sort->setSortItem('status_msg','status', 'active', array($this->msg['status_published_msg'], 3));
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        return CommonCategoryView::getFilter($manager, $this);
    }
    
    
    function getFilterSql($manager) {
        return CommonCategoryView::getFilterSql($manager);
    }
    
    
    function getListActions($cat_id, $parent_id, $direct_children) {
        return CommonCategoryView::getCategoryListActions($cat_id, $parent_id, $direct_children, $this);
    }
    
    
    function ajaxGetSortableList($category_id, $alphabetical = false) {
        return CommonCategoryView::ajaxGetSortableList($category_id, $alphabetical, $this->manager, $this);
    }
    

}
?>