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

class ArticleTemplateView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';  
    
    
    function execute(&$obj, &$manager) {
    
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = &$this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());        
        
        if($this->controller->getMoreParam('popup')) {
            $manager->setSqlParams('AND active = 1');
        }
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            // actions/links
            $links = array();
            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), true, $actions));
            
            $v = array_merge($obj->get(), $this->msg);
            $more = array('id' => $row['id']);
            $v['preview_link'] = $this->getLink('this', 'this', false, 'preview', $more);
            
            $v['popup'] = ($this->controller->getMoreParam('popup')) ? 2 : 1;
            
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj, $links) {
        $actions = array('clone', 'status', 'update', 'delete');
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);    
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);    
        
        $sort->setSortItem('id_msg', 'id', 'e.id',  $this->msg['id_msg']);
        $sort->setSortItem('key_msg', 'key', 'e.tmpl_key',  $this->msg['key_msg']);
        $sort->setSortItem('title_msg',  'title', 'e.title',   $this->msg['title_msg'], 1);
        $sort->setSortItem('description_msg',  'desc', 'e.description',   $this->msg['description_msg']);
        $sort->setSortItem('status_active_msg', 'status', 'e.active',  $this->msg['status_active_msg']);
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        $arr_select = array();
        $arr_from = array();
        @$values = $_GET['filter'];
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            $v = addslashes(stripslashes($v));
            // $arr_select[] = "MATCH (title, description, tmpl_key) AGAINST ('$v') AS score";
            // $arr[]  = "AND MATCH (title, description, tmpl_key) AGAINST ('$v' IN BOOLEAN MODE)";
            $arr[]  = "AND (title LIKE '%{$v}%' OR description LIKE '%{$v}%' OR tmpl_key LIKE '%{$v}%')";
        }
        
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(" \n", $arr_select);
        $arr['from'] = implode(" \n", $arr_from);
        
        return $arr;
    }
 
}
?>