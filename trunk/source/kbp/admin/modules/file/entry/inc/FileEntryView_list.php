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


class FileEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    var $child_categories = array();    
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        $popup = $this->controller->getMoreParam('popup');
        $tmpl = ($popup) ? $this->template_popup :  $this->template;
        
        if($popup == 1) {
            $manager->setSqlParams("AND cat.attachable = 1");
        }
        
        $manager->show_bulk_sort = false;
        if(!empty($_GET['filter']['c'])) {
            if($_GET['filter']['c'] != 'all') {
                $manager->show_bulk_sort = true;
            }
        }
        
        
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        if($popup && $popup != 'ckeditor') {
            $tpl->tplSetNeeded('/close_button');
        }

        $link_title = array(
            '1' => $this->msg['insert_as_attachment_title_msg'],
            'ckeditor' => $this->msg['insert_as_link_title_msg'],
            'text' => $this->msg['assign_msg']
        );
        
        $img_alt = array(
            '1' => $this->msg['insert_as_attachment_msg'],
            'ckeditor' => $this->msg['insert_as_link_msg'],
            'text' => $this->msg['assign_msg']
        );

        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);
        
        if($manager->setting['file_extract']) {
            $tpl->tplSetNeededGlobal('filetext');
        }
        
        // bulk
        $bulk_allowed = array(); // it should be defined in license check, not now in files
        $manager->bulk_manager = new FileEntryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'FileEntryView_bulk', $this));
            
            if($manager->show_bulk_sort) {
                $tpl->tplSetNeededGlobal('sort_order');
            }
        }
        
        
        // filter sql
        $categories = $manager->getCategoryRecords();
        $params = $this->getFilterSql($manager, $categories);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        $manager->setSqlParamsJoin($params['join']);
        $manager->entry_role_sql_group = $params['group'];
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // set force index date_updated
        /*if(strpos($sort_order, 'date_updated') !== false) {
            $manager->entry_sql_force_index = 'FORCE INDEX (date_updated)';
        }*/
        
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);

        // xajax
        $this->bp = $bp;
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // header generate
        $button = CommonEntryView::getButtons($this, $xajax, 'file_draft');
        if(in_array($this->controller->getMoreParam('popup'), array('ckeditor', 'text'))) {
            $button = false;
        }
        
        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), $button));
        

        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id', true);

        // categories
        $entry_categories = ($ids) ? $manager->getCategoryByIds($ids) : array();
        $entry_categories = $this->stripVars($entry_categories);

        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);

        $this->full_categories = $full_categories;

        // users
        $author_ids = $manager->getValuesArray($rows, 'author_id');
        $updater_ids = $manager->getValuesArray($rows, 'updater_id');
        $users = array();
        if($author_ids || $updater_ids) {
            $users = implode(',', array_unique(array_merge($author_ids, $updater_ids)));
            $users = $manager->getUser($users, false);
            $users = $this->stripVars($users);
        }        
        
        // roles to entry        
        $roles_range = $manager->getRoleRangeFolow();
        
        $roles = ($ids) ? $manager->getRoleById($ids, 'id_list') : array();
        $roles = $this->parseEntryRolesMsg($roles, $roles_range);
        
        $category_ids = $manager->getValuesString($rows, 'category_id', true);
        $category_roles = ($category_ids) ? $manager->cat_manager->getRoleById($category_ids, 'id_list') : array();
        $category_roles = $this->parseEntryCategoryRolesMsg($category_roles, $roles_range);
        
        // attached to articles
        $articles_num = ($ids) ? $manager->getReferencedArticlesNum($ids) : array();
        
        // schedule
        $schedule = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();        

        // other 
        $status = $manager->getEntryStatusData('article_status');
        $publish_status_ids = $manager->getEntryStatusPublished('file_status');
        // $client_controller = &$this->controller->getClientController();
        
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('sort_order', $row['real_sort_order']);
            $obj->set('filesize', WebUtil::getFileSize($obj->get('filesize')));
            
            $tpl->tplAssign('filetext', ($row['filetext']) ? $this->msg['yes_msg'] : '&nbsp;&nbsp;&nbsp;');
            $tpl->tplAssign('escaped_filename', addslashes($row['filename']));
            
            $tpl->tplAssign('size', $row['filesize']);
            
            // as attachments
            $attached_num = '--';
            if(isset($articles_num[$row['id']])) {
                $n = $articles_num[$row['id']];
                $str = '<a href="%s">%s</a>';
                $more = array('filter[q]'=>'attachment:'.$row['id']);
                $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                $attached_num = sprintf($str, $link, $n);
            } 
            $tpl->tplAssign('attached_num', $attached_num);
            
            
            // dates & user
            $user = (isset($users[$row['author_id']])) ? $users[$row['author_id']] : array();
            $formated_date_posted_full = $this->parseDateFull($user, $row['ts']);        
            
            $tpl->tplAssign('formated_date_posted', $this->getFormatedDate($row['ts']));
            $tpl->tplAssign('formated_date_posted_full', $formated_date_posted_full);      
            
            $formated_date_updated = '--';
            $formated_date_updated_full = '';
            $ddiff = $row['tsu'] - $row['ts'];
            if($ddiff > $manager->update_diff) {
                $user = (isset($users[$row['updater_id']])) ? $users[$row['updater_id']] : array();
                $formated_date_updated_full = $this->parseDateFull($user, $row['tsu']);        
                $formated_date_updated = $this->getFormatedDate($row['tsu']);
            }
            
            $tpl->tplAssign('formated_date_updated', $formated_date_updated);
            $tpl->tplAssign('formated_date_updated_full', $formated_date_updated_full);
                        
                        
            // category
            $cat_nums = count($entry_categories[$obj->get('id')]);
            $tpl->tplAssign('num_category', ($cat_nums > 1) ? "[$cat_nums]" : '');
            $tpl->tplAssign('category', $this->getSubstringSignStrip($row['category_title'], 20));
            
            $more = array('filter' => array('c' => $row['category_id']));
            $tpl->tplAssign('category_filter_link', $this->controller->getLink('all', '', '', '', $more));
            
            // full categories
            $_full_categories = array();
            $first_row = true;
            foreach(array_keys($entry_categories[$obj->get('id')]) as $cat_id) {
                $_full_categories[] = ($first_row) ? sprintf('<b>%s</b>', $full_categories[$cat_id]) : $full_categories[$cat_id];
                $first_row = false;
            }
            $tpl->tplAssign('full_category', implode('<br />',  $_full_categories));
            
            
            // private&roles
            if($row['private'] || $row['category_private']) {
                $tpl->tplAssign('roles_msg', $this->getEntryPrivateMsg(@$roles[$row['id']], @$category_roles[$row['category_id']]));
                $tpl->tplAssign($this->getEntryColorsAndRolesMsg($row));
                $tpl->tplSetNeeded('row/if_private');
            }
            
            // schedule
            if(isset($schedule[$obj->get('id')])) {
                $tpl->tplAssign('schedule_msg', $this->getScheduleMsg($schedule[$obj->get('id')], $status));
                $tpl->tplSetNeeded('row/if_schedule');
            }
            
            // popup actions
            if ($popup) {
                $tpl->tplAssign('link_title', $link_title[$popup]);
                $tpl->tplAssign('img_alt', $img_alt[$popup]);
            }
            
            // status vars
            $st_vars = CommonEntryView::getViewListEntryStatusVars($obj->get(), 
                                            $entry_categories[$obj->get('id')], $publish_status_ids, 
                                            $status);
            $tpl->tplAssign($st_vars);
            
            // actions/links
            $links = array();
            $links['file_link'] = $this->getActionLink('file', $obj->get('id'));
            $links['fopen_link'] = $this->getActionLink('fopen', $obj->get('id'));
            $links['draft_link'] = $this->getLink('this', 'file_draft', false, 'insert', array('entry_id' => $obj->get('id')));
            
            
            // if some of categories is private
            // and user do not have this role so he can't update it
            $categories = $entry_categories[$obj->get('id')];
            $has_private = $manager->isCategoryNotInUserRole(array_keys($categories));
            
            $actions = $this->getListActions($obj, $links, $manager, 
                                                            $has_private, $st_vars['published']);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $actions, $manager, 
                                                            $has_private, $st_vars['published']));
                        
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        
        // upload and attach - close the window immediately
        if ($this->controller->getMoreParam('popup') && $this->controller->getMoreParam('attach_id')) {
            $attach_file_id = (int) $this->controller->getMoreParam('attach_id');
            if($attach_file_id) {
                $file_to_attach = $manager->getById($attach_file_id);
                
                $tpl->tplSetNeeded('/upload_and_attach');
                
                $tpl->tplAssign('attach_id', $file_to_attach['id']);
                $tpl->tplAssign('attach_escaped_filename', addslashes($file_to_attach['filename']));
                $tpl->tplAssign('attach_size', $file_to_attach['filesize']);
            }
        }
        
        // create an empty box for a message block
        if ($this->controller->getMoreParam('popup')) {
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());
            
            $menu_msg = AppMsg::getMenuMsgs('file');
            $tpl->tplAssign('popup_title', $menu_msg['file_entry']);
            
            if (!empty($_GET['replace_id'])) {
                $tpl->tplSetNeeded('/replace');
                $tpl->tplAssign('replace_id', $_GET['replace_id']);
            }
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseTitle() {
        $values = array();
        $values['attached_num_msg'] = $this->shortenTitle($this->msg['attached_num_msg'], 3);
        return $values;
    }
    
    
    function getViewListVarsJsCustom($entry, $actions, $manager, $has_private, $is_published) {

        $own_record = ($entry['author_id'] == $manager->user_id);
        $status = $entry['active'];
        $row = $this->getViewListVarsJs($entry['id'], $status, $own_record, $actions);
        
        $row['filetext_link'] = $this->getActionLink('text', $entry['id']);
        $row['file_link'] = $actions['file']['link'];
        
        if($has_private) {
            $row['bulk_ids_ch_option'] = 'disabled';
            $row['filetext_link'] = '';
        }
        
        return $row;
    }
    

    function getListActions($obj, $links, $manager, $has_private, $is_published) {

        $record_id = $obj->get('id');
        $status = $obj->get('active');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        $actions = array('detail');

        $actions['file'] = array(
            'msg'  => $this->msg['download_msg'], 
            'link' => $links['file_link']);
        
        // $actions['text'] = array(
        //     'msg'  => $this->msg['filetext_msg'], 
        //     'link' => $links['filetext_link']);
        
        $actions['fopen'] = array(
            'msg'  => $this->msg['open_msg'], 
            'link' => $links['fopen_link'], 
            'link_attributes'  => 'target="_blank"');
        
        
        if(!$has_private) {
            $actions[] = 'clone';
            $actions[] = 'update';
            $actions[] = 'delete';
        }


        // drafts
        $as_draft = false;
        if(!$has_private && $this->isEntryUpdateable($record_id, $status, $own_record)) {
            if($this->priv->isPriv('insert', 'file_draft')) {
                $as_draft = true;
            }

            if($this->priv->isPrivOptional('insert', 'draft')) {
                unset($actions[array_search('update', $actions)]);
                $as_draft = true;
            }
        }
        
        if($this->priv->isPrivOptional('insert', 'draft')) {
            unset($actions[array_search('clone', $actions)]);
        }
        
        if($as_draft) {
            $rlink = $this->controller->getLink('all');
            $referer = WebUtil::serialize_url($rlink);
            $more = array('entry_id' => $obj->get('id'), 'referer' => $referer);
            $draft_link = $this->getLink('this', 'file_draft', false, 'insert', $more);

            $actions['draft'] = array(
                'msg'  => $this->msg['update_as_draft_msg'], 
                'link' => $draft_link, 
                'img'  => '');
        } 
        
             
        return $actions;
    }   
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('fname', 1);
        $sort->setCustomDefaultOrder('sort_oder', 1);
    
        $order = CommonEntryView::getSortOrderSetting($this->setting['file_sort_order']);
        $sort->setDefaultSortItem($order);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('date_posted_msg',  'datep',    'date_posted',  $this->msg['posted_msg']);
        $sort->setSortItem('date_updated_msg', 'dateu',    'date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('filename_msg',     'fname',    'filename',     $this->msg['filename_msg']);
        
        $sort->setSortItem('id_msg','id', 'e.id', $this->msg['id_msg']);
        //$sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg']);
        $sort->setSortItem('filesize_msg', 'comments', 'comment_num', $this->msg['filesize_msg']);
        $sort->setSortItem('filesize_msg', 'filesize', 'filesize', $this->msg['filesize_msg']);
        $sort->setSortItem('filetype_msg', 'filetype', 'filetype', $this->msg['filetype_msg']);
        $sort->setSortItem('filetext_msg', 'filetext', 'filetext', $this->msg['filetext_msg']);
        $sort->setSortItem('download_num_msg', 'dowload', 'downloads', array($this->msg['download_num_msg'], 5));
        $sort->setSortItem('entry_status_msg','status', 'active', array($this->msg['entry_status_msg'], 6));
        
        $sort->setSortItem('category_msg', 'cat', 'e.category_id', $this->msg['category_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_oder', 'real_sort_order', array($this->msg['sort_order_msg'], 5));
        
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);            
            }
        }        
        
        //echo '<pre>', print_r($sort->getSql(), 1), '</pre>';
        return $sort;
    }    
    
    
    function getFilter($manager, $categories) {

        @$values = $_GET['filter'];
        
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
    
        if(isset($values['f'])) {
            $values['f'] = RequestDataUtil::stripVars($values['f'], array(), true);
            $values['f'] = trim($values['f']);
        }        

        //xajax
        $xobj = null;
        $ajax = &$this->getAjax($xobj, $manager);
        $xajax = &$ajax->getAjax();
    
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        
        $categories = $manager->getCategorySelectRangeFolow($categories);  // private removed
                
        // category
        if(!empty($values['c'])) {
            $category_id = (int) $values['c'];
            $category_name = $this->stripVars($categories[$category_id]);
            $tpl->tplAssign('category_name', $category_name);
        } else {
            $category_id = 0;
        }
        
        $tpl->tplAssign('category_id', $category_id);
        
        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }
   
        $js_hash = implode(",\n", $js_hash);         
        $tpl->tplAssign('categories', $js_hash);
        
        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));
                


        $select = new FormSelect();
        $select->select_tag = false;    
    
        
        // status
        $select->setRange($manager->getListSelectRange('file_status', false), 
                          array('all'=>'__'));            
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        // custom 
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }    
    
    
    function getFilterSql($manager, $categories) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        
        // category roles
        // probably we should not apply it in pop up window
        $mysql['where'][] = 'AND ' . $manager->getCategoryRolesSql(false);
        
        // category
        @$v = $values['c'];
        if(!empty($v)) {
            $category_id = (int) $v;
            
            if(!empty($values['ch'])) {
                // need to group because one article could belong 
                // to parent and to child 
                $mysql['group'][] = 'GROUP BY e.id';
                
                $child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
                $child = implode(',', $child);
                $mysql['where'][] = "AND cat.id IN($child)";            
                
            } else {
                $mysql['where'][] = "AND cat.id = $category_id";
                $sphinx['where'][] = "AND category IN ($category_id)";
            }
            
            $sphinx['group'][] = 'GROUP BY e.id';
            
            $manager->select_type = 'category';
        }
        
        
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
                
                if($sql = CommonEntryView::getSpecialSearchSql($manager, $ret, $v)) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];    
                    }

                } elseif($sql = $this->getSpecialSearchSql($manager, $ret, $v)) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];
                    }
                
                } elseif ($ret['rule'] == 'attached') {
                    $type = strpos($v, 'inline') ? '2,3' : '1,2,3';
                    $related = $manager->getAttachmentToEntry($ret['val'], $type);
                    $related = ($related) ? implode(',', $related) : "'no_attached'";
                    $mysql['where'][] = sprintf("AND e.id IN(%s)", $related);    

                } elseif ($ret['rule'] == 'fname') {
                    $fname = addslashes(stripslashes($ret['val']));
                    $fname = str_replace('*', '%', $fname);
                    $mysql['where'][] = sprintf("AND e.filename LIKE '%s'", $fname);
                }
            
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.filename_index, e.meta_keywords, e.description, e.filetext) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (e.title, e.filename_index, e.meta_keywords, e.description, e.filetext) AGAINST ('$v' IN BOOLEAN MODE)";
                
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
        
        @$v = $values['q'];
        $options = array('index' => 'file', 'own' => 1, 'entry_private' => 1, 'cat_private' => 'main');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    

    // if some special search used
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }        
        
        $search = CommonEntryView::getSpecialSearchArray();
        
        // get all files that have link to article (where entry_id = '[attached:id]')
        $search['attached'] = "#^attached(?:-inline|-all)?:(\d+)$#";
        $search['fname'] = "#^fname:(.*?)$#";
        
        return $this->parseSpecialSearchStr($str, $search);
    }
    
    
    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if ($key == 'note_drafted_entries_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_entry_drafted'];
            $msg['body'] = $msgs['note_drafted_entries_bulk'];
            return BoxMsg::factory('error', $msg);            
        }
    }


    function getScheduleMsg($data, $status) {
        return CommonEntryView::getScheduleMsg($data, $status, $this);
    }
    
    function parseDateFull($user, $date) {
        return CommonEntryView::parseDateFull($user, $date, $this);
    }

    function parseEntryCategoryRolesMsg($roles, $roles_range) {
        return CommonEntryView::parseEntryCategoryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
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
    

    // Filter // -----------
        
    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }
    
    
    // SORT // -----------
    
    function ajaxGetSortableList() {
        return CommonEntryView::ajaxGetSortableList('filename', $this->manager, $this);
    }

}
?>
