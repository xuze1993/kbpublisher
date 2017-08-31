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


class KBFeaturedEntryView_list extends AppView
{

    var $tmpl = 'list.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);


        $categories = $manager->emanager->getCategoryRecords();
        $categories = $this->stripVars($categories, array(), false);

        $this->category_id = false;
        if (isset($_GET['filter']['t']) && $_GET['filter']['t'] != '') {
            $tpl->tplSetNeededGlobal('sort');

            $this->category_id = $_GET['filter']['t'];
            $this->category_name = ($this->category_id) ? $categories[$this->category_id]['name'] : $this->msg['index_page_msg'];

        } else {
            $manager->show_bulk_sort = false;
            $tpl->tplSetNeededGlobal('sort_all');
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
        if ($this->priv->isPriv('insert')) {
            if ($this->priv->isPriv('select', 'kb_entry')) {
                $msg = 'insert';
                $link = $this->getLink('knowledgebase', 'kb_entry');
                $link = "javascript: PopupManager.create('{$link}', 'r', 'r', 3);void(0)";
                $button = array($msg => $link);
            }
        }

        if ($this->priv->isPriv('update')) {
            $disabled = (count($rows) < 1 || $this->category_id === false);
            $button['...'][] = array(
                'msg' => $this->msg['reorder_msg'],
                'link' => sprintf("javascript:xajax_getSortableList('%s');void(0);", $this->category_id),
                'disabled' => $disabled
            );
        }

        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), $button));

        // bulk
        $manager->bulk_manager = new KBFeaturedEntryModelBulk;
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer',
                $this->controller->getView($obj, $manager, 'KBFeaturedEntryView_bulk', $this));
        }

        $star_html = '<img src="images/icons/check.svg" alt="" style="vertical-align:middle;">';
        $category_html = '<div style="float: left;margin-left: 15px;">%s</div><div style="float: right;margin-top: 1px;">(%s)</div>';

        foreach($rows as $entry_id => $row) {

            // actions/links
            $more = array('id' => $entry_id, 'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $links['detail_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);

            $link = $this->getActionLink('update', $entry_id);
            $links['update_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 3);", $link);

            $links['delete_category_link'] = $this->getActionLink('delete_category', $row['id']);

            $actions = $this->getListActions($links, $categories);

            $row['entry_id'] = $entry_id;

            if ($this->category_id === false) {
                $category_count = count($row['category']);

                if (empty($row['category'][0])) {
                    $row['index_img'] = '-';

                } else {
                    $row['index_img'] = $star_html;
                    $category_count --;
                }

                $row['categories'] = ($category_count) ? sprintf($category_html, $star_html, $category_count) : '-' ;

            } else {
                $row['sort_order'] = $row['category'][$this->category_id];
            }


            $tpl->tplAssign($this->getViewListVarsJs($entry_id, 1, 1, $actions));
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

        if ($this->priv->isPriv('select', 'kb_entry')) {
            $actions['detail'] = array(
                'link' => $links['detail_link']
            );
        }

        if ($this->category_id !== false) {
            $actions['delete_category'] = array(
                'msg' => sprintf('%s - %s', $this->msg['remove_from_msg'], $this->category_name),
                'link' => $links['delete_category_link'],
                'confirm_msg' => $this->msg['sure_common_msg']
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

        // type
        $type = (!isset($values['t'])) ? 'index' : $values['t'];

        $select = new FormSelect();
        $select->select_tag = false;


        // category
        $full_categories = $manager->emanager->cat_manager->getSelectRangeFolow($categories); // private here

        $categories = array();
        $categories[0] = $this->msg['index_page_msg'];

        $used_categories = $manager->getUsedCategories();
        foreach ($used_categories as $v) {
            $categories[$v] = $full_categories[$v];
        }

        $categories = $this->stripVars($categories, array(), 'stripslashes'); // for compability with other $js_hash

        if(isset($values['t']) && ($values['t'] !== '')) {
            $category_id = (int) $values['t'];
            $category_name = $this->stripVars($categories[$category_id]);

            $tpl->tplAssign('category_id', $category_id);
            $tpl->tplAssign('category_name', $category_name);
        }


        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('categories', $js_hash);

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
                $arr[] = "AND ef.category_id = '$v'";

            } elseif($v == 'index') {
                $arr[] = "AND ef.category_id = 0";
            }
        }

        $arr = implode(" \n", $arr);

        return $arr;
    }


    function ajaxGetSortableList() {

        // $params = $this->getFilterSql();
        // $this->manager->setSqlParams($params);

        // $sort = &$this->getSort();
        // $sort_order = $sort->getSql();
        // $this->manager->setSqlParamsOrder($sort_order);

        $additional_rows = 10;

        $limit = $this->bp->limit + $additional_rows;
        $offset = $this->bp->offset - ($additional_rows / 2);
        if ($offset < 0) {
            $offset = 0;
        }

        $rows = $this->manager->getRecords($limit, $offset);

        $sort_values = array();
        foreach ($rows as $entry_id => $v) {
            $sort_values[$entry_id]  = $v['category'][$this->category_id];
        }
        array_multisort($sort_values, SORT_ASC, $rows);


        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');

        $tpl->tplAssign('sort_values', implode(',', $sort_values));

        $lowest_sort_order = ($offset == 0) ? 1 : $sort_values[0];
        $tpl->tplAssign('lowest_sort_order', $lowest_sort_order);

        foreach($rows as $row) {
            $row['sort_order'] = $row['category'][$this->category_id];
            $tpl->tplParse($row, 'row');
        }

        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);

        $tpl->tplParse($this->msg);


        $objResponse = new xajaxResponse();
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort', $lowest_sort_order);

        return $objResponse;
    }
}
?>