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


class StuffEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';    
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');         
        $this->addMsgPrepend('common_msg.ini', 'file'); 
        
                                       
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;        
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
                                                          
                                                    
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);

        // header generate                                                                              
        $bp = &$this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
                                                            
        // sort generate
        $sort = &$this->getSort();
        $sort_order = $sort->getSql();
        $manager->setSqlParamsOrder($sort_order);        

        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'id');
        
        $categories = $manager->getCategories();
        
        // users
        $author_ids = $manager->getValuesArray($rows, 'author_id');
        $updater_ids = $manager->getValuesArray($rows, 'updater_id');
        $users = array();
        if($author_ids || $updater_ids) {
            $users = implode(',', array_unique(array_merge($author_ids, $updater_ids)));
            $users = $manager->getUser($users, false);
            $users = $this->stripVars($users);
        }        
            
                                         
        $client_controller = &$this->controller->getClientController();
                                         
        if ($this->controller->getMoreParam('popup')) {
            $tpl->tplAssign('opener_id', $this->controller->getMoreParam('field_name'));
        }
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('filesize', WebUtil::getFileSize($obj->get('filesize')));
            
            $tpl->tplAssign('escaped_filename', addslashes($row['filename']));
            
            // dates & user
            $user = (isset($users[$row['author_id']])) ? $users[$row['author_id']] : array();
            $formated_date_posted_full = $this->parseDateFull($user, $row['date_posted']);        
            
            $tpl->tplAssign('formated_date_posted', $this->getFormatedDate($row['date_posted']));
            $tpl->tplAssign('formated_date_posted_full', $formated_date_posted_full);            
            
            $formated_date_updated = '--';
            $formated_date_updated_full = '';
            $ddiff = strtotime($row['date_updated']) - strtotime($row['date_posted']);
            if($ddiff > $manager->update_diff) {
                $user = (isset($users[$row['updater_id']])) ? $users[$row['updater_id']] : array();
                $formated_date_updated_full = $this->parseDateFull($user, $row['date_updated']);        
                $formated_date_updated = $this->getFormatedDate($row['date_updated']);
            }
            
            $tpl->tplAssign('formated_date_updated', $formated_date_updated);
            $tpl->tplAssign('formated_date_updated_full', $formated_date_updated_full);
            
            if ($row['category_id']) {
                $tpl->tplAssign('category', $this->getSubstringSignStrip($categories[$row['category_id']], 20));                  
            }
            
            $more = array('filter' => array('c' => $row['category_id']));
            $tpl->tplAssign('category_filter_link', $this->controller->getLink('stuff', 'stuff_entry', false, false, $more));            
            
                
            $tpl->tplAssign($this->getViewListVarsCustom($obj->get('id'), $obj->get('active'),
                                                   			$obj->get(), $manager));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');    
            //$tpl->tplParse($obj->get(), 'row');
        }
        

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
                         
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
	 
    
    function getViewListVarsCustom($record_id = false, $active = false, $values, $manager) {
        
        $own_record = ($values['author_id'] == $manager->user_id);
        $row = parent::getViewListVars($record_id, $active, $own_record);
        
        $row['file_link'] = $this->getActionLink('file', $record_id);
        
        return $row;
    }
	
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('fname', 1);
    
        $sort->setDefaultSortItem('dateu');
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('date_posted_msg',  'datep',    'date_posted',  $this->msg['posted_msg']);
        $sort->setSortItem('date_updated_msg', 'dateu',    'date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('filename_msg',     'fname',    'filename',     $this->msg['filename_msg']);
        
        $sort->setSortItem('id_msg','id', 'id', $this->msg['id_msg']);
        $sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg']);
        $sort->setSortItem('filesize_msg', 'filesize', 'filesize', $this->msg['filesize_msg']);
        $sort->setSortItem('filetype_msg', 'filetype', 'filetype', $this->msg['filetype_msg']);
        $sort->setSortItem('entry_status_msg','status', 'active', array($this->msg['entry_status_msg'], 6));
        
        $sort->setSortItem('category_msg', 'cat', 'category_id', $this->msg['category_msg']);
        
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);            
            }
        }        
        
        //$sort->getSql();
        //$sort->toHtml()
        return $sort;
    }    
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
    
        if(isset($values['f'])) {
            $values['f'] = RequestDataUtil::stripVars($values['f'], array(), true);
            $values['f'] = trim($values['f']);
        }        
    
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
           

        $select = new FormSelect();
        $select->select_tag = false;    
    
        
        // category
        $select->setRange($manager->getCategories(),
                          array('all' => $this->msg['all_categories_msg'],
                                0 => $this->msg['no_category_msg']));

        @$category_id = $values['c'];
        $tpl->tplAssign('category_select', $select->select($category_id));
        
        // status
        @$status = $values['s'];
        $range = array('all'=> '__',
                          1 => $this->msg['status_published_msg'],
                          0 => $this->msg['status_not_published_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        if ($this->controller->getMoreParam('popup')) {
            $tpl->tplAssign('mode', $this->controller->getMoreParam('field_name'));
        }
        
        $tpl->tplParse(@$values);
        return $tpl->tplPrint(1);
    }    
    
    
    function getFilterSql($manager) {
        
        // filter
        $arr = array();
        $arr_select = array();
        @$values = $_GET['filter'];

        // category
        @$v = $values['c'];
        if($v != 'all' && isset($v)) {
            $id = (int) $v;
            $arr[] = "AND category_id = '{$id}'";
        }
        
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $arr[] = "AND active = '$v'";
        }        
        
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            
            $v = trim($v);
            if($ret = $this->isSpecialSearchStr($v)) {

                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 'id')) {
                    $arr[] = $sql['where'];
                }
            
            } else {
                $v = addslashes(stripslashes($v));
                $arr_select[] = "MATCH (title, description) AGAINST ('$v') AS score";
                $arr[]  = "AND MATCH (title, description) AGAINST ('$v' IN BOOLEAN MODE)";
            }
        }        
        
        
        @$v = $values['f'];
        if(!empty($v)) {
            $v = addslashes(stripslashes(trim($v)));
            $v = str_replace('*', '%', $v);
            $arr[] = "AND filename LIKE '{$v}'";
        }
        
        
        //echo '<pre>', print_r($arr, 1), '</pre>';
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(" \n", $arr_select);
        
        return $arr;
    }
    
    

    // if some special search used
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }        
        
        $search = CommonEntryView::getSpecialSearchArray();        
        return $this->parseSpecialSearchStr($str, $search);
    }

    
    function parseDateFull($user, $date) {
        return CommonEntryView::parseDateFull($user, $date, $this);
    }
    
}
?>