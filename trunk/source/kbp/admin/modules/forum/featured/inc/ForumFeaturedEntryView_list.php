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

require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';


class ForumFeaturedEntryView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        
        $categories = $manager->emanager->getCategoryRecords();
        $categories = $this->stripVars($categories, array(), false);
        
        $this->category_id = false;
        if (isset($_GET['filter']['t']) && $_GET['filter']['t'] != '') {
            $tpl->tplSetNeededGlobal('sort');
            
            $this->category_id = $_GET['filter']['t'];
            $this->category_name = $categories[$this->category_id]['name'];
            
        } else {
            $manager->show_bulk_sort = false;
        }
        
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);    
        
        $bp =& $this->pageByPage($manager->limit, $manager->getCountRecords());
        $this->bp = $bp;
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records        
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $button = array();
        if ($this->priv->isPriv('update')) {
            if ($this->priv->isPriv('select', 'forum_entry')) {
                $msg = 'insert';
                $link = $this->getLink('forum', 'forum_entry');
                $link = "javascript: PopupManager.create('{$link}', 'r', 'r', 3);void(0)";
                $button = array($msg => $link);
            }

            $disabled = (count($rows) < 1 || $this->category_id === false);
            $button['...'][] = array(
                'msg' => $this->msg['reorder_msg'],
                'link' => sprintf("javascript:xajax_getSortableList('%s');void(0);", $this->category_id),
                'disabled' => $disabled
            );
        }

        
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), $button));
        
        // bulk
        $manager->bulk_manager = new ForumFeaturedEntryModelBulk;
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', 
                $this->controller->getView($obj, $manager, 'ForumFeaturedEntryView_bulk', $this));
        }
        
        foreach($rows as $row) {
            // actions/links
            $more = array('id' => $row['entry_id'], 'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $links['detail_link'] = $this->getLink('forum', 'forum_entry', false, 'detail', $more);
            
            $link = $this->getActionLink('update', $row['id']);
            $links['update_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 3);", $link);
            
            $links['delete_category_link'] = $this->getActionLink('delete_category', $row['id']);
            
            $actions = $this->getListActions($links, $categories);
            
            $row['date_formatted'] = (strtotime($row['date_to'])) ? $this->getFormatedDate($row['date_to']) : $this->msg['permanent_msg'];
            
            $tpl->tplAssign($this->getViewListVarsJs($row['id'], 1, 1, $actions));
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($links) {
        
        $actions = array(
            'update',
            'delete' => array(
                'msg' => $this->msg['remove_msg'],
                'confirm_msg' => $this->msg['sure_common_msg']
                ));
        
        if ($this->priv->isPriv('select', 'forum_entry')) {
            $actions['detail'] = array(
                'link' => $links['detail_link']
            );
        }
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        //$order = $this->getSortOrderSetting();
        $sort->setDefaultSortItem('sort_order', 1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('id_msg', 'id', 'e.id', $this->msg['id_msg']);
        $sort->setSortItem('hits_num_msg', 'hits', 'hits', $this->msg['hits_num_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_order', 'sort_order', $this->msg['sort_order_msg']);
        $sort->setSortItem('entry_title_msg', 'title', 'title', $this->msg['entry_title_msg']);
        
        return $sort;
    }
    
    
    function getFilter($manager, $categories) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        // category
        $categories = $this->stripVars($categories, array(), 'stripslashes'); // for compability with other $js_hash
        $categories = $manager->emanager->cat_manager->getSelectRangeFolow($categories); // private here
        
        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }
   
        $js_hash = implode(",\n", $js_hash);         
        $tpl->tplAssign('categories', $js_hash);
        
        if(isset($values['t']) && ($values['t'] !== '')) {
            $category_id = (int) $values['t'];
            $category_name = $this->stripVars($categories[$category_id]);
            
            $tpl->tplAssign('category_id', $category_id);
            $tpl->tplAssign('category_name', $category_name);
        }
        
        
        // status
        $select = new FormSelect();
        $select->select_tag = false;
        
        $range = array(
            'all' => '__',
            1 => $this->msg['status_active_msg'],
            0 => $this->msg['status_not_active_msg']);
        $select->setRange($range);
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql() {
        
        $arr = array();
        $arr_select = array();       
        
        @$values = $_GET['filter'];
        
        @$v = (!isset($values['t'])) ? 'all' : $values['t'];
        if($v != 'all') {
            if (is_numeric($v)) {
                $arr[] = "AND e.category_id = '$v'";
            }
        }
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            if ($v) {
                $arr[] = "AND (date_to > NOW() OR date_to IS NULL)";
                
            } else {
                $arr[] = "AND (date_to < NOW() AND f.date_to IS NOT NULL)";
            }
        }
        
        $arr = implode(" \n", $arr);

        return $arr;
    }
    
    
    function ajaxGetSortableList() {
        $objResponse = new xajaxResponse();
        
        $params = $this->getFilterSql();
        $this->manager->setSqlParams($params);
        
        $sort = &$this->getSort();
        $sort_order = $sort->getSql();
        $this->manager->setSqlParamsOrder($sort_order);
        
        
        $additional_rows = 10;
        
        $limit = $this->bp->limit + $additional_rows;
        $offset = $this->bp->offset - ($additional_rows / 2);
        if ($offset < 0) {
            $offset = 0;
        }
        
        $rows = $this->manager->getRecords($limit, $offset);
        
        $sort_values = array();
        foreach ($rows as $row) {
            $sort_values[$row['id']]  = $row['sort_order'];
        }
        array_multisort($sort_values, SORT_ASC, $rows);
        
        
        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');
        
        $tpl->tplAssign('sort_values', implode(',', $sort_values));
        
        $lowest_sort_order = ($offset == 0) ? 1 : $sort_values[0];
        $tpl->tplAssign('lowest_sort_order', $lowest_sort_order);
        
        foreach($rows as $row) {
            $tpl->tplParse($row, 'row');
        }
        
        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);
        
        $tpl->tplParse($this->msg);        
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        
        $objResponse->call('initSort', $lowest_sort_order);
    
        return $objResponse; 
    }
}
?>