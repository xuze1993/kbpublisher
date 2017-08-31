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

require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';


class NewsEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('knowledgebase/common_msg.ini');
        
        $popup = $this->controller->getMoreParam('popup');
        
        $add_button = ($popup) ? false :  true;
        $tmpl = ($popup) ? $this->template_popup :  $this->template;
        
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        // draft message
        if ($this->setting['entry_autosave']) {
            if($manager->isAutosaved(0, '2011-01-01')) {
                $tpl->tplAssign('msg', $this->getDraftsMessage());
            }
        }        
        
        // bulk
        $manager->bulk_manager = new NewsEntryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'NewsEntryView_bulk', $this));
        }
        
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        $manager->setSqlParamsJoin($params['join']);
        
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id');

        // roles to entry        
        $roles_range = $manager->getRoleRangeFolow();
        $roles = ($ids) ? $manager->getRoleById($ids, 'id_list') : array();
        $roles = $this->parseEntryRolesMsg($roles, $roles_range);

        // schedule
        $schedule = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();
        $status[0]['title'] = $this->msg['status_not_published_msg'];
        $status[1]['title'] = $this->msg['status_published_msg'];
        
        $client_controller = &$this->controller->getClientController();

        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
                        
            $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($row['date_posted']));

            $title = $obj->get('title');
            $tpl->tplAssign('short_title', $this->getSubstringStrip($title, 60));

            // private&roles
            $row['category_private'] = false;
            if($row['private']) {
                $tpl->tplAssign('roles_msg', $this->getEntryPrivateMsg(@$roles[$obj->get('id')], array()));
                $tpl->tplAssign($this->getEntryColorsAndRolesMsg($row));
                $tpl->tplSetNeeded('row/if_private');
            }

            // schedule
            if(isset($schedule[$obj->get('id')])) {
                $tpl->tplAssign('schedule_msg', $this->getScheduleMsg($schedule[$obj->get('id')], $status));
                $tpl->tplSetNeeded('row/if_schedule');
            }

            // actions/links
            $links = array();
            $link = $this->getActionLink('preview', $obj->get('id'), array('detail_btn'=>1));    
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $links['entry_link'] = $client_controller->getLink('news', false, $obj->get('id'));    
            
            $entry_link = ($obj->get('active')) ? $links['entry_link'] : $links['preview_link'];
            $tpl->tplAssign('entry_link', $entry_link);

            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), true, $actions));
                                                   
            $tpl->tplParse($obj->get(), 'row');
        }
        
        if ($this->controller->getMoreParam('popup')) {
            $menu_msg = AppMsg::getMenuMsgs('news');
            $tpl->tplAssign('popup_title', $menu_msg['news_entry']);
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj, $links) {
        
        $actions = array('detail', 'clone', 'status', 'update', 'delete');

        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'], 
            'link' => $links['preview_link'], 
            'img'  => '');
        
        if($obj->get('active')) {
            $actions['public'] = array(
                'msg'  => $this->msg['entry_public_link_msg'], 
                'link' => $links['entry_link'], 
                'img'  => '');
        }        
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('datep', 2);
        $sort->setDefaultSortItem('datep', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('date_msg', 'datep', 'date_posted',  $this->msg['date_msg']);
        $sort->setSortItem('title_msg',  'title', 'title',  $this->msg['title_msg']);
        $sort->setSortItem('hits_num_msg',  'hits', 'hits',  $this->msg['hits_num_msg']);
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);            
            }
        }
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        //xajax
        $xobj = false;
        $ajax = &$this->getAjax($xobj, $manager);
        $xajax = &$ajax->getAjax();
                
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
        
        //status
        @$status = $values['s'];
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_published_msg'],
               0 => $this->msg['status_not_published_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        // custom 
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));        
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND e.active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }         
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearchStr($v)) {
                
                if($sql = CommonEntryView::getSpecialSearchSql($manager, $ret, $v, true)) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];    
                    }
                
                } elseif($sql = $this->getSpecialSearchSql($manager, $ret, $v)) { echo 1;
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];
                    }
                }
            
            } else {
                
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.body_index, e.meta_keywords) AGAINST ('$v') AS score";
                $mysql['where'][] = "AND MATCH (e.title, e.body_index, e.meta_keywords) AGAINST ('$v' IN BOOLEAN MODE)";
                
                $sphinx['match'][] = $v;
            }
        }
        
        
        // custom 
        @$v = $values['custom'];
        if($v) {
            $v = RequestDataUtil::stripVars($v);
            $sql = $manager->cf_manager->getCustomFieldSql($v);
            $mysql['where'][] = 'AND ' . $sql['where'];
            $mysql['join'][] = $sql['join'];
            
            $sql = $manager->cf_manager->getCustomFieldSphinxQL($v);
            if (!empty($sql['where'])) {
                $sphinx['where'][] = 'AND ' . $sql['where'];
            }
            $sphinx['select'][] = $sql['select'];
            $sphinx['match'][] = $sql['match'];
        }
        
        $options = array('index' => 'news', 'entry_private' => 1);
        $arr = $this->parseFilterSql($manager, $values['q'], $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
                
        return $arr;
    }   
    
    
    function getDraftsMessage() {
        $vars['link'] = $this->getLink('this', 'news_autosave', false, false);
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = ''; //$msgs['title_entry_autosave'];
        $msg['body'] = $msgs['note_entry_draft'];
        return BoxMsg::factory('hint', $msg, $vars);
    }
    
    
    function getScheduleMsg($data, $status) {
        return CommonEntryView::getScheduleMsg($data, $status, $this);
    }    
    
    function parseEntryRolesMsg($roles, $roles_range) {
        return CommonEntryView::parseEntryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
    }    
    
    function getEntryPrivateMsg($entry_roles, $category_roles) {
        return CommonEntryView::getEntryPrivateMsg($entry_roles, $category_roles, $this->msg);
    }
    
    function getEntryColorsAndRolesMsg($row) {
        return CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg);
    }
    
    
    // Filter // ------
    
    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }
         
}
?>