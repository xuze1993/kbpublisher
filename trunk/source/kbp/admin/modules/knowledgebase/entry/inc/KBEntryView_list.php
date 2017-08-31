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


class KBEntryView_list extends AppView
{

    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    var $template_popup_featured = 'list_popup_featured.html';
    var $template_popup_review = 'list_popup_review.html'; // ??? 9 Oct, 2013 for what?
    var $child_categories = array();


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

        $popup = $this->controller->getMoreParam('popup');

        $add_button = ($popup) ? false :  true;
        $tmpl = ($popup) ? $this->template_popup :  $this->template;
        if($popup == 2) {
            $tmpl = $this->template_popup_review;
        }

        if($popup == 3) {
            $tmpl = $this->template_popup_featured;
        }

        $manager->show_bulk_sort = false;
        if(!empty($_GET['filter']['c'])) {
            if($_GET['filter']['c'] != 'all') {
                $manager->show_bulk_sort = true;
            }
        }


        $tpl = new tplTemplatez($this->template_dir . $tmpl);

        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);

        // autosave message
        if ($this->setting['entry_autosave'] && !$show_msg2) {
            if($manager->isAutosaved(0, '2011-01-01')) {
                $tpl->tplAssign('msg', KBEntryView_common::getDraftsMessage($this, 'kb_autosave'));
            }
        }


        // check
        $update_allowed = true;
        $bulk_allowed = array();
        $au = KBValidateLicense::getAllowedEntryRest($manager);
        if($au !== true) {
            if($au <= 0) {
                $key = ($au == 0) ? 'license_limit_entry' : 'license_exceed_entry';
                $tpl->tplAssign('msg', AppMsg::licenseBox($key));
                $add_button = false;

                if($key == 'license_exceed_entry') {
                    $update_allowed = false;
                    $bulk_allowed = array('delete');
                }
            }
        }


        // bulk
        $manager->bulk_manager = new KBEntryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'KBEntryView_bulk', $this));

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
        $button = ($add_button) ? CommonEntryView::getButtons($this, $xajax, 'kb_draft') : array();
        $tpl->tplAssign('header',
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories, $xajax), $button));


        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id');

        // categories
        $entry_categories = ($ids) ? $manager->getCategoryByIds($ids) : array();
        $entry_categories = $this->stripVars($entry_categories);
        // echo "<pre>"; print_r($entry_categories); echo "</pre>";

        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);
        //echo "<pre>"; print_r($full_categories); echo "</pre>";

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

        // setting/fields
        $setting = SettingModel::getQuick(100);
        if($setting['allow_rating'])   {
            $tpl->tplSetNeededGlobal('rating');
        }


        if($ids) {

            // comments
            if($setting['allow_comments']) {
                $comments_num = $manager->getCommentsNum($ids);
                $tpl->tplSetNeededGlobal('comment');
            }

            // attachments
            $attachments_num = $manager->getAttachmentsNum($ids);
            $tpl->tplSetNeededGlobal('attachment');
        }


        // schedule
        $schedule = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();

        // history
        $history = ($ids) ? $manager->getHistoryNum($ids) : array();

        // other
        $status = $manager->getEntryStatusData('article_status');
        $publish_status_ids = $manager->getEntryStatusPublished('article_status');
        $types = $this->stripVars($manager->getListSelectRange('article_type', false));
        $client_controller = &$this->controller->getClientController();


        $action_colspan = 0;
        if(in_array($popup, array(1, 'ckeditor'))) {
            $action_colspan ++;
            $tpl->tplSetNeededGlobal('insert');
        }

        if($popup && empty($_GET['no_attach'])) {
            $action_colspan ++;
            $tpl->tplSetNeededGlobal('attach');
        }

        $tpl->tplAssign('action_colspan', 1);

        if($popup == 3) {
            $featured_url = $this->getLink('knowledgebase', 'kb_featured', false, false);
            $featured_url = $this->controller->_replaceArgSeparator($featured_url);
            $tpl->tplAssign('featured_url', $featured_url);
        }

        // list records
        foreach($rows as $row) {

            if ($row['id'] == $this->controller->getMoreParam('exclude_id')) {
                continue;
            }

            $obj->set($row);
            $obj->set('sort_order', $row['real_sort_order']);

            if($row['entry_type']) {
                $obj->set('entry_type', $types[$row['entry_type']]);
                $tpl->tplAssign('entry_type_short', $this->getSubstringSignStrip($types[$row['entry_type']], 6));
            } else {
                $obj->set('entry_type', '-');
                $tpl->tplAssign('entry_type_short', '');
            }


            $title = $obj->get('title');
            $tpl->tplAssign('short_title', $this->getSubstringStrip($title, 30));
            $tpl->tplAssign('escaped_title', $this->getSubstringJsEscape($title, 100));// for popup window

            //$tpl->tplAssign('attachment_num', $row['attachment_num']);
            $tpl->tplAssign('votes', ($row['votes']) ? $row['votes'] . '&nbsp;/&nbsp;' : '');
            $tpl->tplAssign('rating_img', $this->getRatingImg($row['rating'], $row['votes']));


            // attachments
            $attachment_num = '--';
            if(isset($attachments_num[$row['id']])) {
                $n = $attachments_num[$row['id']];
                $str = '<a href="%s">%s</a>';
                $link = $this->getLink('file', 'file_entry', false, false, array('filter[q]'=>'attached:'.$row['id']));
                $attachment_num = sprintf($str, $link, $n);
            }
            $tpl->tplAssign('attachment_num', $attachment_num);


            // comments
            $comment_num = '--';
            if(isset($comments_num[$row['id']])) {
                $n = $comments_num[$row['id']];
                $str = '<a href="%s">%s</a>';
                $link = $this->getLink('knowledgebase', 'kb_comment', false, false, array('entry_id'=>$row['id']));
                $comment_num = sprintf($str, $link, $n);
            }
            $tpl->tplAssign('comment_num', $comment_num);


            // // rating comments
            // $rating_comment_num = '--';
            // if(isset($rating_comment_num[$row['id']])) {
            //     $n = $rating_comment_num[$row['id']];
            //     $str = '<a href="%s">%s</a>';
            //     $link = $this->getLink('knowledgebase', 'kb_rate', false, false, array('entry_id'=>$row['id']));
            //     $comment_num = sprintf($str, $link, $n);
            // }
            // $tpl->tplAssign('rating_comment_num', $rating_comment_num);


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
            if($popup == 3) {
                $more['popup'] = 3;
            }
            $tpl->tplAssign('category_filter_link', $this->controller->getLink('all', '', '', '', $more));

            // full categories
            $_full_categories = array();
            $first_row = true;
            foreach(array_keys($entry_categories[$obj->get('id')]) as $cat_id) {
                $_full_category = ($first_row) ? sprintf('<b>%s</b>', $full_categories[$cat_id]) : $full_categories[$cat_id];

                if ($this->priv->isPriv('update', 'kb_category')) {
                    $more = array(
                        'id' => $cat_id,
                        'referer'=> WebUtil::serialize_url($this->controller->getCommonLink()));
                    $update_link = $this->getLink('knowledgebase', 'kb_category', false, 'update', $more);

                    $_full_category = sprintf('<a href="%s">%s</a>', $update_link, $_full_category);
                }

                $_full_categories[] = RequestDataUtil::stripVars($_full_category, array(), true);
                $first_row = false;
            }

            $tpl->tplAssign('full_category', implode('<br />',  $_full_categories));


            // private&roles
            if($row['private'] || $row['category_private']) {
                $tpl->tplAssign('roles_msg',
                    $this->getEntryPrivateMsg(@$roles[$obj->get('id')],
                                              @$category_roles[$obj->get('category_id')]));
                $tpl->tplAssign($this->getEntryColorsAndRolesMsg($row));
                $tpl->tplSetNeeded('row/if_private');
            }


            // schedule
            if(isset($schedule[$obj->get('id')])) {
                $tpl->tplAssign('schedule_msg', $this->getScheduleMsg($schedule[$obj->get('id')], $status));
                $tpl->tplSetNeeded('row/if_schedule');
            }


            // status vars
            $st_vars = CommonEntryView::getViewListEntryStatusVars($obj->get(),
                                            $entry_categories[$obj->get('id')], $publish_status_ids,
                                            $status);
            $tpl->tplAssign($st_vars);

            // featured
            if (isset($row['featured_index_order'])) {
                $this->parseFeatured($tpl, $row);
            }


            // actions/links
            $links = array();
            $link = $this->getActionLink('preview', $obj->get('id'), array('detail_btn'=>1));
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $links['entry_link'] = $client_controller->getLink('entry', $obj->get('category_id'), $obj->get('id'));
            $links['history_link'] = $this->getActionLink('history', $obj->get('id'));

            // if some of categories is private
            // and user do not have this role so he can't update it
            $has_private = $manager->isCategoryNotInUserRole(array_keys($entry_categories[$obj->get('id')]));

            $actions = $this->getListActions($obj, $links, $manager,
                                                        $has_private, $st_vars['published'],
                                                        $update_allowed, $history);

            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $actions, $manager,
                                                                $has_private, $st_vars['published']));

            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }

        $tpl->tplAssign('do_confirm', ($popup == 'ckeditor') ? 'false' : 'true');

        if (in_array($popup, array(1, 'text', 'public'))) {
            $tpl->tplSetNeeded('/close_button');
        }

        // create an empty box for a message block
        if ($popup) {
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());

            $menu_msg = AppMsg::getMenuMsgs('knowledgebase');
            $tpl->tplAssign('popup_title', $menu_msg['kb_entry']);
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function parseTitle() {
        $values = array();
        $values['comment_num_msg'] = $this->shortenTitle($this->msg['comment_num_msg'], 3);
        $values['attachment_num_msg'] = $this->shortenTitle($this->msg['attachment_num_msg'], 3);
        return $values;
    }


    function parseFeatured(&$tpl, $row) {

        $a = array('category_id' => 0);

        $cats = array(
            'featured_index_order' => 0,
            'featured_cat_order' => $row['category_id']
            );

        foreach($cats as $k => $v) {
            $a = array();
            $a['category_id'] = $v;

            if ($row[$k]) {
                $a['new_value'] = 0;
                $a['assign_display'] = 'none';
                $a['remove_display'] = 'block';

            } else {
                $a['new_value'] = 1;
                $a['assign_display'] = 'block';
                $a['remove_display'] = 'none';
            }

            $tpl->tplParse($a, 'row/assign');
        }

        $tpl->tplSetNested('row/assign');
    }


    function getViewListVarsJsCustom($entry, $actions, $manager, $has_private, $is_published) {

        $own_record = ($entry['author_id'] == $manager->user_id);
        $status = $entry['active'];
        $row = $this->getViewListVarsJs($entry['id'], $status, $own_record, $actions);

        $row['entry_link'] = ($is_published) ? $actions['public']['link'] : $actions['preview']['link'];
        $row['attr'] = ($is_published) ? 'target="_blank"' : '';

        if($has_private) {
            $row['bulk_ids_ch_option'] = 'disabled';
        }

        // history
        $row['history_img'] = false;
        if (isset($actions['history'])) {
            $row['history_link'] = $this->getActionLink('history', $entry['id']);
            $row['history_img'] = $this->getImgLink($row['history_link'], 'history', $actions['history']['msg']);
        }

        // featured
        if($this->controller->getMoreParam('popup') == 3) {
            $more = array('id' => $entry['id'], 'popup' => 1);
            $row['featured_link'] = $this->getLink('this', 'kb_featured', false, 'insert', $more);
        }

        return $row;
    }


    function getListActions($obj, $links, $manager, $has_private, $is_published, $update_allowed, $history) {

        $record_id = $obj->get('id');
        $status = $obj->get('active');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        $actions = array('detail');

        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'],
            'link' => $links['preview_link'],
            'img'  => '');

        if($is_published) {
            $actions['public'] = array(
                'msg'  => $this->msg['entry_public_link_msg'],
                'link' => $links['entry_link'],
                'img'  => '');
        }

        if(!$has_private) {
            $actions[] = 'clone';
            $actions[] = 'update';
            $actions[] = 'trash';
        }

        // drafts
        $as_draft = false;
        if(!$has_private && $this->isEntryUpdateable($record_id, $status, $own_record)) {
            if($this->priv->isPriv('insert', 'kb_draft')) {
                $as_draft = true;
            }

            if($this->priv->isPrivOptional('update', 'draft')) {
                unset($actions[array_search('update', $actions)]);
                $as_draft = true;
            }
        }

        if($as_draft) {
            $link = KBEntryView_common::getUpdateAsDraftLink($this, $record_id);
            $actions['draft'] = array(
                'msg'  => $this->msg['update_as_draft_msg'],
                'link' => $link,
                'img'  => '');
        }

        if($this->priv->isPrivOptional('insert', 'draft')) {
            unset($actions[array_search('clone', $actions)]);
        }

        // license
        if($update_allowed == false) {
            unset($actions[array_search('update', $actions)]);
            unset($actions[array_search('clone', $actions)]);
            unset($actions[array_search('draft', $actions)]);
        }

        // history
        if(isset($history[$obj->get('id')]) && !$has_private) {
            $msg = sprintf('%s: %s', $this->msg['history_msg'], $history[$obj->get('id')] + 1);
            $actions['history'] = array(
                'msg'  => $msg,
                'link' => $links['history_link'],
                'img'  => '');
        }

        return $actions;
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('title', 1);
        $sort->setCustomDefaultOrder('sort_oder', 1);

        $article_sort_order = 'updated_desc';
        if(isset($this->setting['article_sort_order'])) {
            $article_sort_order = $this->setting['article_sort_order'];
        }

        $order = CommonEntryView::getSortOrderSetting($article_sort_order);
        $sort->setDefaultSortItem($order);

        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_posted_msg',  'datep', 'date_posted',  $this->msg['posted_msg']);
        $sort->setSortItem('date_updated_msg', 'dateu', 'date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('entry_title_msg',  'title', 'title',        $this->msg['entry_title_msg']);

        $sort->setSortItem('id_msg', 'id', 'e.id', $this->msg['id_msg']);
        $sort->setSortItem('votes_num_msg',  'votes', 'votes', $this->msg['votes_num_msg']);
        $sort->setSortItem('rating_msg',  'rating', 'rating', $this->msg['rating_msg']);
        $sort->setSortItem('hits_num_msg', 'hits', 'hits', array($this->msg['hits_num_msg'], 5));
        $sort->setSortItem('entry_type_msg', 'type', 'entry_type', $this->msg['entry_type_msg']);
        $sort->setSortItem('entry_status_msg','status', 'active', array($this->msg['entry_status_msg'], 6));

        $sort->setSortItem('category_msg', 'cat', 'main_category', $this->msg['category_msg']);
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


    function getFilter($manager, $categories, $xajax) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');


        $categories = $manager->getCategorySelectRangeFolow($categories); // private removed

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

        // type
        $select->setRange($manager->getListSelectRange('article_type', false),
                          array('all' => '__',
                                'none' => $this->msg['none_entry_type_msg'],
                                'any' => $this->msg['any_entry_type_msg'],
                                ));
        @$status = $values['et'];
        $tpl->tplAssign('type_select', $select->select($status));


        // status
        $select->setRange($manager->getListSelectRange('article_status', false),
                          array('all'=>'__'));
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));


        // custom
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));

        //if($this->controller->getMoreParam('popup') == 3) {
            $xajax->registerFunction(array('featureArticle', $this, 'ajaxFeatureArticle'));
        //}


        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }


    function getFilterSql(&$manager, $categories) {

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


        // type
        @$v = $values['et'];
        if($v == 'none') {
            $mysql['where'][] = "AND e.entry_type = 0";
            $sphinx['where'][] = "AND entry_type = 0";

        } elseif($v == 'any') {
            $mysql['where'][] = "AND e.entry_type != 0";
            $sphinx['where'][] = "AND entry_type != 0";

        }  elseif($v != 'all' && !empty($v)) {
            $v = (int) $v;

            $mysql['where'][] = "AND e.entry_type = '$v'";
            $sphinx['where'][] = "AND entry_type = $v";
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

                } elseif ($ret['rule'] == 'related') {
                    $type = strpos($v, 'inline') ? '2,3' : '1,2,3';
                    $type = strpos($v, 'attached') ? '1' : $type;

                    $related = $manager->getEntryToRelated($ret['val'], $type);
                    $related = ($related) ? implode(',', $related) : "'no_related'";
                    $mysql['where'][] = sprintf("AND e.id IN(%s)", $related);

                } elseif ($ret['rule'] == 'attachment') {
                    $type = strpos($v, 'inline') ? '2,3' : '1,2,3';
                    $type = strpos($v, 'attached') ? '1' : $type;

                    $related = $manager->getEntryToAttachment($ret['val'], $type);
                    $related = ($related) ? implode(',', $related) : "'no_attachment'";
                    $mysql['where'][] = sprintf("AND e.id IN(%s)", $related);

                } elseif ($ret['rule'] == 'trouble_related') {
                    $type = strpos($v, 'inline') ? '2,3' : '1,2,3';
                    $type = strpos($v, 'attached') ? '1' : $type;

                    $related = $manager->getEntryToTroubleRelated($ret['val'], $type);
                    $related = ($related) ? implode(',', $related) : "'no_related'";
                    $mysql['where'][] = sprintf("AND e.id IN(%s)", $related);
                }

            } else {

                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v') AS score";
                $mysql['where'][] = "AND MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v' IN BOOLEAN MODE)";

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


        // featured, not a filter actually, called in popup where set featured
        if($this->controller->getMoreParam('popup') == 3) {
            $category_id = (empty($values['c'])) ? 'e.category_id' : (int) $values['c'];

            $mysql['select'][] = 'ef.sort_order as featured_index_order, ef2.sort_order as featured_cat_order';
            $mysql['join'][] = "LEFT JOIN {$manager->tbl->entry_featured} ef
                ON e.id = ef.entry_id
                    AND ef.entry_type = 1
                    AND ef.category_id = 0

                LEFT JOIN {$manager->tbl->entry_featured} ef2
                ON e.id = ef2.entry_id
                    AND ef2.entry_type = 1
                    AND ef2.category_id = {$category_id}";
        }

        @$v = $values['q'];
        $options = array('index' => 'article', 'own' => 1, 'entry_private' => 1, 'cat_private' => 'main');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';

        return $arr;
    }


/*
    function getExtraSql($manager) {

        $arr = array();
        $arr_select = array();
        $arr_from = array();
        $arr_join = array();
        @$values = $_GET['filter'];

        // featured
        if($this->controller->getMoreParam('popup') == 3) {
            $category_id = (empty($values['c'])) ? 'e.category_id' : (int) $values['c'];

            $arr_select[] = 'ef.sort_order as featured_index_order,
                       ef2.sort_order as featured_cat_order';

            $arr_join[] = "LEFT JOIN {$manager->tbl->entry_featured} ef
                ON e.id = ef.entry_id
                    AND ef.entry_type = 1
                    AND ef.category_id = 0

                LEFT JOIN {$manager->tbl->entry_featured} ef2
                ON e.id = ef2.entry_id
                    AND ef2.entry_type = 1
                    AND ef2.category_id = {$category_id}";
        }

        // echo '<pre>', print_r($arr, 1), '</pre>';
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(", \n", $arr_select);
        $arr['from'] = implode(" \n", $arr_from);
        $arr['join'] = implode(" \n", $arr_join);

        return $arr;
    }*/



    // if some special search used
    function isSpecialSearchStr($str) {

        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }

        $search = CommonEntryView::getSpecialSearchArray();

        // get all articles that have link to searched article (where related_entry_id = '[relared:id]')
        $search['related'] = "#^related(?:-inline|-all)?:(\d+)$#";

        // get all articles that have link to file (where attachment_id = '[attached:id]')
        $search['attachment'] = "#^attachment(?:-inline|-attached|-all)?:(\d+)$#";

        $search['trouble_related'] = "#^trouble_related(?:-inline|-all)?:(\d+)$#";

        return $this->parseSpecialSearchStr($str, $search);
    }


    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if($key == 'note_remove_reference') {
            @$r = $this->isSpecialSearchStr($_GET['filter']['q']);
            $vars['article_id'] = $r['val'];
            $vars['delete_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'delete',
                                                        array('id' => $r['val']));

            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_references'];
            $msg['body'] = $msgs['note_remove_reference'];
            return BoxMsg::factory('error', $msg, $vars);

        } elseif ($key == 'note_remove_reference_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_references_bulk'];
            $msg['body'] = $msgs['note_remove_reference_bulk'];
            return BoxMsg::factory('error', $msg);

        } elseif ($key == 'note_drafted_entries_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_entry_drafted'];
            $msg['body'] = $msgs['note_drafted_entries_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }


    function getRatingImg($rate, $votes) {
        $str = '<img src="images/rating/rate_star/rate_%s.gif" alt="rate" />';
        $rate = round($rate);
        return ($votes) ? sprintf($str, $rate) : '';
    }


    function getRating($rate, $votes) {
        $rate = round($rate);
        return ($votes) ? $rate : '';
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


    // FILTER // -----------

    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }

    function ajaxFeatureArticle($id, $type, $num) {
        return CommonEntryView::ajaxFeatureArticle($id, $type, $num, $this);
    }


    // SORT // -----------

    function ajaxGetSortableList() {
        return CommonEntryView::ajaxGetSortableList('title', $this->manager, $this);
    }

}
?>