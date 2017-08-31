<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

class CommonEntryView
{


    // ROLES MSG IN ENTRY LIST VIEW // --------------------------

    static function parseEntryRolesMsg($roles, $roles_range, $msg, $mkey = 'entry_msg') {
        $ret = array();
        foreach($roles AS $rule => $v) {
            foreach($v as $category_id => $data) {
                foreach($data as $role_id) {
                    $ret[$category_id][$rule][$role_id] = sprintf('%s (%s)', $roles_range[$role_id], $msg[$mkey]);
                }
            }
        }

        return $ret;
    }


    static function parseEntryCategoryRolesMsg($roles, $roles_range, $msg) {
        return CommonEntryView::parseEntryRolesMsg($roles, $roles_range, $msg, 'category_msg');
    }


    static function getEntryPrivateMsg($entry_roles, $category_roles, $msg) {
		
		if(empty($entry_roles)) {
			$entry_roles = array();
		}

		if(empty($category_roles)) {
			$category_roles = array();
		}

        $roles_msg = '';
        $entry_roles = RequestDataUtil::stripVars($entry_roles, array(), true);
        $category_roles = RequestDataUtil::stripVars($category_roles, array(), true);

        $str = '<div>%s</div><div>%s</div>';

        $map = array('read', 'write');
        foreach($map as $v) {

            if(!isset($entry_roles[$v])) {
                $entry_roles[$v] = array();
            }

            if(!isset($category_roles[$v])) {
                $category_roles[$v] = array();
            }
            $roles = array_merge($entry_roles[$v], $category_roles[$v]);
            // echo '<pre>entry_roles ', print_r($entry_roles[$v], 1), '</pre>';
            // echo '<pre>category_roles ', print_r($category_roles[$v], 1), '</pre>';
            // echo '<pre>roles ', print_r($roles, 1), '</pre>';
            // echo '<pre>', print_r("============", 1), '</pre>';

            if($roles) {
                $mkey = "private2_{$v}_msg";
                $r = ' - ' . implode('<br> - ', $roles);
                $roles_msg .= sprintf($str, _strtoupper($msg[$mkey]), $r);
            }
        }

        // return RequestDataUtil::jsEscapeString($roles_msg);
        return $roles_msg;
    }


    static function getEntryColorsAndRolesMsg($row, $msg) {
        $data = array();

        $read_entry_msg = BaseView::getPrivateTypeMsg($row['private'], $msg);
        $read_cat_msg = BaseView::getPrivateTypeMsg($row['category_private'], $msg);

        if($row['private'] && $row['category_private']) {
            $data['image_color'] = 'blue';
            $str = '%s (%s), %s (%s)';
            $data['private1_msg'] =  sprintf($str, $msg['private_entry_msg'], $read_entry_msg,
                                                   $msg['private_category_msg'], $read_cat_msg);
        } else {
            if($row['private']) {
                $data['image_color'] = 'orange';
                $data['private1_msg'] =  sprintf('%s (%s)', $msg['private_entry_msg'], $read_entry_msg);
            } else {
                $data['image_color'] = 'red';
                $data['private1_msg'] =  sprintf('%s (%s)', $msg['private_category_msg'], $read_cat_msg);
            }
        }

        return $data;
    }


    // STATUS // -----------------------------

    static function isCategoryPublished($categories) {
        $cat_published = 0;
        foreach($categories as $v) {
            if($v['active']) { $cat_published = 1; break; }
        }

        return $cat_published;
    }


    static function isEntryPublished($entry, $categories, $publish_status_ids) {
        $cat_published = CommonEntryView::isCategoryPublished($categories);
        $entry_published = (in_array($entry['active'], $publish_status_ids));

        $ret = ($entry_published && $cat_published) ? true : false;
        return $ret;
    }


    static function getViewListEntryStatusVars($entry, $categories, $publish_status_ids, $status) {

        $cat_published = CommonEntryView::isCategoryPublished($categories);
        $row['published'] = CommonEntryView::isEntryPublished($entry, $categories, $publish_status_ids);

        // rewrite status if category not published
        $st_color = $status[$entry['active']]['color'];
        if(!$cat_published) {
            $st_color = $st_color  . '; opacity: 0.4; filter: alpha(opacity=40)';
        }

        $row['status'] = $status[$entry['active']]['title'];
        $row['color'] = $st_color;

        return $row;
    }


    // SORT // ---------------------------

    static function &populateSortSelect($manager, $obj, $category_id, $category_title, $xajax = false) {

        $entry_id = $obj->get('id');
        $limit = 10;

        $show_more_top = false;
        $show_more_bottom = false;

        // get all entries
        $entries = $manager->getSortRecords($category_id);
        $sort = $obj->getSortValues($category_id);
        $sort_val = self::getSortOrder($category_id, $entry_id, $sort, $entries, $xajax);
        $sort_val_num = '';

        // id sort_val entry
        $s_ids = explode('_', $sort_val);
        $s_id = $s_ids[0];

        $ids = array_keys($entries);

        // max num entries
        $_limit = ($limit * 2) + 2;

        // filter entries
        if (count($entries) > $limit) {

            $cur_pos = array_search($s_id, $ids);
            $offset = $cur_pos - $limit;

            if ($offset < 0) {
                $offset = 0;
            }

            if ($offset == 0) {
                $el = array_search($entry_id, $ids);
                $_limit = $el + $limit + 1;
            }

            $entries = array_slice($entries, $offset, $_limit, true);

            // show more top option
            if ($offset > 0) {
                $show_more_top = true;
            }

            // show more button option
            $keys = array_keys($entries);
            if (end($ids) != end($keys)) {
                $show_more_bottom = true;
            }
        }

        $html = array();
        $html[] = '<div class="sortOrderDiv">';
        //$html[] = '<script language="javascript">alert("' . $sort_val . '")</script>';
        $html[] = '<div style="padding-bottom: 3px;">'. $category_title .':</div>';
        $html[] = '<div style="padding-bottom: 6px;">';

        $start_num = array_search(key($entries), $ids);
        $range = self::getSortSelectRange($entries, $start_num, $entry_id, $show_more_top);

        // num options
        foreach ($range as $key => $val) {
            $not_num = array('sort_begin', 'sort_end', 'show_top');

            if (in_array($key, $not_num)) {
                $_range[$key] = $val;
            } else {

                $k = explode('_', $key);
                $num = array_search($k[0], $ids);

                $_range[$key . '_' . $num] = $val;

                if ($key == $sort_val) {
                    $sort_val_num = '_' . $num;
                }
            }
        }

        if ($show_more_bottom) {
            $_range['show_bottom'] = 'Show more ...';
        }

        $reg = &Registry::instance();
        $conf = $reg->getEntry('conf');
        $encoding = $conf['lang']['meta_charset'];

        $_range = RequestDataUtil::stripVarsBadUtf($_range, $encoding);
        $_range = RequestDataUtil::stripVars($_range, array(), true);

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($_range);

        $str = '<select name="sort_values[%d]" id="sort_values_%d" class="sort_values" style="width: 100%%;">';
        $html[] = sprintf($str, $category_id, $category_id);
        $html[] =  $select->select($sort_val . $sort_val_num);
        $html[] = '</select>';

        $html[] = '</div>';
        $html[] = '</div>';

        $html = implode("\n\t", $html);

        return $html;
    }


    static function ajaxGetNextCategories($mode, $val, $category_id, $manager) {

        $limit = 20;

        $id = substr($val, 0, strpos($val, '_'));
        $pos = substr($val, strrpos($val, '_') + 1);
        if ($pos < 0) $pos = 0;

        $objResponse = new xajaxResponse();


        $del_show_top = false;
        $del_show_bottom = false;

        switch ($mode) {

            case 'bottom':
                $entries = $manager->getSortRecords($category_id, $limit + 1, $pos + 1);

                if(count($entries) < ($limit + 1)) {
                    $del_show_bottom = true;
                } else {
                    array_pop($entries);
                }

                // first selected
                $is_selected = true;

                foreach($entries as $key => $value) {

                    $pos ++;
                    $opt_value = sprintf('%d_%d_%d', $key, $value['s'], $pos);

                    $objResponse->call('SortShowMore.addNextBottomArticle', $category_id, ($pos + 2) . '.  AFTER: ' . $value['t'], $opt_value, $is_selected);
                    $is_selected = false;
                }

                if($del_show_bottom) {
                    $objResponse->call('SortShowMore.deleteShow', $category_id, 'show_bottom');
                }

                break;

            case 'top':
                $pos_lim = $pos - $limit - 1;

                if ($pos_lim <= $limit) {
                    $limit = $pos - 1;
                    $pos_lim = 0;
                }

                $entries = $manager->getSortRecords($category_id, $limit + 1, $pos_lim);

                // is end, delete first if not
                if ($pos_lim == 0) {
                    $del_show_top = true;
                } else {
                    unset($entries[key($entries)]);
                }


                // first selected
                $is_selected = true;

                foreach(array_reverse($entries, true) as $key => $value) {

                    $pos --;
                    $opt_value = sprintf('%d_%d_%d', $key, $value['s'], $pos);

                    $objResponse->call('SortShowMore.addNextTopArticle', $category_id, ($pos + 2) . '.  AFTER: ' . $value['t'], $opt_value, $is_selected);
                    $is_selected = false;
                }

                if($del_show_top) {
                    $objResponse->call('SortShowMore.deleteShow', $category_id, 'show_top');
                }

                break;
        }

        return $objResponse;
    }


    static function ajaxPopulateSortSelect($category_id, $category_title, $manager, $obj) {

        $category_title = RequestDataUtil::stripVars($category_title, array(), true);
        $html = &CommonEntryView::populateSortSelect($manager, $obj, $category_id,
                                                      $category_title, true);

        $objResponse = new xajaxResponse();
        // $objResponse->addAlert($category_title);

        $objResponse->addAppend('writeroot_sort', 'innerHTML', $html);
        $objResponse->call('SortShowMore.init');

        return $objResponse;
    }


    static function getSortSelectRange($rows, $start_num, $entry_id = false, $show_more_top = false) {

        $search = array("#(\r\n|\n)#"); // "#\n+#",
        $data = array();

        $data['sort_begin'] = 'AT THE BEGINNING';
        $data['sort_end'] = 'AT THE END (default)';

        if ($show_more_top) {
            $data['show_top'] = 'Show more ...';
        }

        foreach(array_keys($rows) as $val => $id) {
            $v = $rows[$id];
            if($id == $entry_id) {
                $start_num ++;
                continue;
            }

            $title = preg_replace($search, '', $v['t']);
            $title = substr($title, 0, 100);
            $data[$id . '_' . $v['s']] = sprintf("%s. AFTER: %s", $start_num + 2, $title);
            $start_num ++;
        }

        //echo "<pre>"; print_r($rows); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";

        return $data;
    }


    static function getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax = false) {

        // when wrong form submission
        if(!(empty($_POST['sort_values'])) && $ajax == false) {
            @$sort_order = $_POST['sort_values'][$category_id];

        } else {

            $found = false;
            if($sort_order != 'sort_begin' && $sort_order != 'sort_end') {
                foreach(array_keys($entries) as $id) {
                    $v = $entries[$id];
                    if($id == $entry_id) {
                        $found = true;
                        $sort_order = (isset($prev_id)) ? $prev_id : 'sort_begin'; //sort begin if it on first place
                        break;
                    }

                    $prev_id = $id . '_' . $v['s'];
                }
            }
        }

        return $sort_order;
    }


    static function getSortOrderSetting($setting_sort) {

        $sort = array(
            'name'         => array('title' => 1),
            'filename'     => array('fname' => 1),
            'added_desc'   => array('datep' => 2),
            'added_asc'    => array('datep' => 1),
            'updated_desc' => array('dateu' => 2),
            'updated_asc'  => array('dateu' => 1)
        );

        return (isset($sort[$setting_sort])) ? $sort[$setting_sort] : array();
    }


    static function ajaxGetSortableList($title_field, $manager, $view) {
        
        $category_id = (int) $_GET['filter']['c'];
        $rows = $manager->getRecords($view->bp->limit, $view->bp->offset);
        
        $sort_values = array();
        foreach ($rows as $k => $v) {
            $sort_values[$k]  = $v['real_sort_order'];
        }
        array_multisort($sort_values, SORT_ASC, $rows);

        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/list_sortable.html');

        $tpl->tplAssign('category_id', $category_id);
        $tpl->tplAssign('sort_values', implode(',', $sort_values));
        $tpl->tplAssign('title_category', $view->full_categories[$category_id]);

        foreach($rows as $row) {
            $row['title'] = $row[$title_field];
            $tpl->tplSetNeeded('row/icon');
            $tpl->tplParse($row, 'row');
        }

        $cancel_link = $view->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);

        $tpl->tplParse($view->msg);
        
        
        $objResponse = new xajaxResponse();        
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');

        return $objResponse;
    }


    // CATEGORY // ---------------------

    static function getCategoryBlock($obj, $manager, $categories, 
                                            $module = 'knowledgebase', 
                                            $page = 'kb_entry', $options = array()) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_category_entry.html');

        $range = array();
        if ($obj) {
            foreach($obj->getCategory() as $cat_id) {
                $range[$cat_id] = $categories[$cat_id];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);
        $tpl->tplAssign('category_select', $select->select());


        $no_button = (!empty($options['no_button'])) ? 'true' : 'false';
        $all_option = (!empty($options['all_option'])) ? 'true' : 'false';
        $default_button = (isset($options['default_button'])) ? $options['default_button'] : true;

        $popup_params = '';
        if(isset($options['popup_params'])) {
            if(is_array($options['popup_params'])) {
                $popup_params = http_build_query($options['popup_params']);
            } else {
                $popup_params = $options['popup_params'];
            }
        }

        if (!empty($options['limited'])) {
            $tpl->tplSetNeeded('/limited');
        }

        // default categories
        $default_categories = array();
        $setting_name = false;
        if($module == 'knowledgebase') {
            $setting_name = 'article_default_category';

        } elseif($module == 'file') {
            $setting_name = 'file_default_category';
        }

        if ($setting_name && $default_button) {
            if (isset($options['entry_categories'])) {
                $default_categories = array();
                foreach ($options['entry_categories'] as $v) {
                    $default_categories[$v] = $categories[$v];
                }
                $default_button_title = 'add_own_categories_msg';

            } else {
                $default_cat = SettingModel::getQuick(1, $setting_name);
                if ($default_cat != 'none') {
                    if (isset($categories[$default_cat])) {
                        $default_categories = array($default_cat => $categories[$default_cat]);

                        // in add article, add file we have role_skip_categories, apply it.
                        if(!empty($manager->role_skip_categories) &&
                           in_array($default_cat, $manager->role_skip_categories)) {
                               unset($default_categories[$default_cat]);
                        }
                    }
                }

                $default_button_title = 'add_default_category_msg';
            }

            $tpl->tplAssign('default_button_title', sprintf('{%s}', $default_button_title));

            $tpl->tplSetNeeded('/default_category_btn');
        }


        // default categories
        $default_categories = RequestDataUtil::stripVars($default_categories); // for compability with other $js_hash

        $js_hash = array();
        $str = '{value: %s, text: "%s"}';
        foreach($default_categories as $k => $v) {
            $js_hash[] = sprintf($str, $k, $v);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('default_categories', $js_hash);


        // 22.12.2015 move this block from ...View_form.php
        if(empty($options['hide_private'])) {

            $category_private_display = 'none';

            foreach($obj->getCategory() as $category_id) {
                $cat_title = $categories[$category_id];
                $a['category_private_info'] =
                    PrivateEntry::getCategoryPrivateInfo($category_id, $cat_title, $manager->cat_manager);

                if (strlen($a['category_private_info']) > 0) {
                    $category_private_display = 'block';
                }

                $tpl->tplParse($a, 'category_private_row');
            }

            $tpl->tplAssign('category_private_display', $category_private_display);
            $tpl->tplSetNeeded('/private_info');
        }
        //->


        $tpl->tplAssign('confirm', ($obj && $obj->get('id')) ? 'true' : 'false');

        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);
        $tpl->tplAssign('no_button', $no_button);
        $tpl->tplAssign('all_option', $all_option);
        $tpl->tplAssign('popup_params', $popup_params);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    static function getCategoryBlockSearch($manager, $categories, $add_option, 
                                             $referer, $module, $page, $controller = false) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_category_search.html');

        $categories = $manager->getCategorySelectRangeFolow($categories);  // private removed

        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('categories', $js_hash);

        $tpl->tplAssign('creation_allowed', ($add_option) ? 'true' : 'false');
        $tpl->tplAssign('referer', $referer);

        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);

        if ($controller) {
            $link = $controller->getFullLink($module, $page, false, 'insert');
            $tpl->tplAssign('popup_link', $controller->_replaceArgSeparator($link));
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    // SCHEDULE // ----------------------

    static function getScheduleBlock($obj, $status_range, $bulk = false) {

        require_once 'eleontev/HTML/DatePicker.php';

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_schedule_entry.html');

        $select = new FormSelect();
        $select->select_tag = false;

        $schedule = $obj->getSchedule();

        $datepicker_str = '<input type="text" id="%s" name="%s" class="schedule_date" />';
        $date_format = TimeUtil::getDateFormat();

        for ($i=1; $i<=2; $i++) {

            $select->setRange($status_range);
            $status = (isset($schedule[$i]['st'])) ? $schedule[$i]['st'] : 0;
            $note = (isset($schedule[$i]['note'])) ? $schedule[$i]['note'] : '';

            if($i == 2) {
                $status = (isset($schedule[$i]['st'])) ? $schedule[$i]['st'] : 1;
            }

            $tpl->tplAssign('status_select_' . $i, $select->select($status));

            $display = 'none';
            $checked = '';
            $timestamp = time();
            if(isset($schedule[$i]['date'])) {
                $timestamp = $schedule[$i]['date'];
                $display = 'block';
                $checked = 'checked';
            }

            $tpl->tplAssign('note_' . $i, $note);

            $tpl->tplAssign('div_schedule_' . $i . '_display', $display);
            $tpl->tplAssign('ch_schedule_on_' . $i, $checked);

            $tpl->tplAssign('date_format', $date_format);
            $tpl->tplAssign('date_format_formatted', str_replace('yy', 'yyyy', $date_format));

            $reg = &Registry::instance();
            $week_start = &$reg->getEntry('week_start');
            $tpl->tplAssign('week_start', $week_start);

            $tpl->tplAssign('datepicker_id_' . $i, 'schedule_' . $i . '_date');

            $date_parts = array('Y', 'm', 'd', 'H');
            $date_obj_params = array();
            foreach ($date_parts as $part) {
                $date_obj_param = date($part, $timestamp);
                if ($part == 'm') {
                    $date_obj_param --;
                }
                $date_obj_params[] = $date_obj_param;
            }
            $tpl->tplAssign('date_formatted_' . $i, implode(',', $date_obj_params));
            //$tpl->tplAssign('time_formatted_' . $i, date('H:i', $timestamp));

            $date = sprintf($datepicker_str, 'schedule_' . $i . '_date', 'schedule['.$i.'][date]');
            $tpl->tplAssign('date_picker_' . $i, $date);
        }

        $minDate = 0; // current
        if (isset($schedule[1]['date'])) {
            if (time() > $schedule[1]['date']) {

                $date_obj_params = array();
                foreach ($date_parts as $part) {
                    $date_obj_param = date($part, $schedule[1]['date']);
                    $date_obj_params[] = $date_obj_param;
                }
                $minDate = 'new Date("' . implode(',', $date_obj_params) . '")';
            }
        }

        $tpl->tplAssign('min_date', $minDate);


        // notify
        //$tpl->tplAssign('div_schedule_3_display', $display);
        //$tpl->tplAssign('ch_schedule_on_3', $checked);


        // diff in bulk view
        if($bulk) {
            $tpl->tplAssign('div_schedule_1_display', 'block');
        } else {
            $tpl->tplSetNeededGlobal('tpl_show_schedule1');
        }

        $msg = AppMsg::getMsgs('datetime_msg.ini', false, 'timepicker');
        $tpl->tplAssign($msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // in list view
    static function getScheduleMsg($data, $statuses, $view) {

        $msgs = array();
        $msgs2 = array();

        $str = '%s - %s<i>%s</i>';

        for ($i=1; $i<=2; $i++) {
            if(isset($data[$i])) {
                $v = $data[$i];

                $d = $view->getFormatedDate($v['date'], 'datetime');
                $m = $view->msg['entry_status_msg'];
                $s = RequestDataUtil::stripVars($statuses[$v['st']]['title'], array(), true);
                $n = (!empty($v['note'])) ? '<br />' . RequestDataUtil::stripVars($v['note'], array(), true) : '';
                //$n = $v['notify'];

                $msgs[] = sprintf($str, $d, $s, $n);
            }
        }

        // return RequestDataUtil::jsEscapeString(implode('<br />', $msgs));
        return implode('<br />', $msgs);
    }


    static function &getDateTimeSelect($timestamp, $name, $time_format = false) {

        require_once 'eleontev/HTML/DatePicker.php';

        // dates
        $picker = new DatePicker();
        //$picker->setFormName('trigger_form');        // set form name
        $picker->setFormMethod($_POST);                // set form method
        $picker->setSelectName($name);

        $picker->setYearRange(date('Y'), date('Y')+3);
        $picker->setDate($timestamp);

        //$date = $picker->js();                    // js function
        //$tpl->tplAssign('js_date_select', $date);

        $date = $picker->day();                    // select tag with days
        $date .= $picker->month();                // select tag with mohth
        $date .= $picker->year();                // select tag with years

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');

        $picker->time_format = $conf['lang']['time_format']; //$time_format;
        $picker->setTimeRange(8, 24, 60);

        $date .= '&nbsp;&nbsp;&nbsp;';
        $date .= $picker->time();

        return $date;
    }


    // user dates
    static function parseDateFull($user, $date, $view) {
        if($user) {
            $str = '%s %s %s';
            $str = sprintf($str, $view->getFormatedDate($date, 'datetime'),
                                 $view->msg['by_user_msg'],
                                 $user['first_name'] . ' ' . $user['last_name']);
        } else {
            $str = '%s';
            $str = sprintf($str, $view->getFormatedDate($date, 'datetime'));
        }

        return $str;
    }


    static function parseInfoBlock(&$tpl, $obj, $view) {

        $tpl->tplSetNeededGlobal('entry_id');

        $str_user = '{by_msg} {first_name} {last_name} (<a href="mailto:{email}">{email}</a>)';

        $a = array();
        $a['date_msg'] = $view->msg['date_posted_msg'];
        $a['by_msg'] = $view->msg['by_user_msg'];
        $a['formatted_date'] = $view->getFormatedDate($obj->get('date_posted'), 'datetime');
        if($user = $obj->getAuthor()) {
            $a['formatted_user'] = $tpl->tplParseString($str_user, array_merge($a, $user));
        }

        $tpl->tplParse($a, 'posted');

        if($obj->getUpdater()) {
            $a = array();
            $a['date_msg'] = $view->msg['date_updated_msg'];
            $a['by_msg'] = $view->msg['by_user_msg'];
            $a['formatted_date'] = $view->getFormatedDate($obj->get('date_updated'), 'datetime');
            if($user = $obj->getUpdater()) {
                $a['formatted_user'] = $tpl->tplParseString($str_user, array_merge($a, $user));
            }

            $tpl->tplParse($a, 'posted');
        }
    }


    // AUTOSAVE // -----------------

    static function ajaxAutoSave($data, $id_key, $obj, $view, $manager, $entry_type = false) {

        $objResponse = new xajaxResponse();

        // disabled because there is no error even with bad utf
        // it could be FCK fix it somehow
        $utf_replace = false;
        if(strtolower($view->encoding) != 'utf-8') {
            $utf_replace = false;
        }

        if($utf_replace) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';

            if(!utf8_compliant($data['title'])) {
                $data['title'] = utf8_bad_replace($data['title'], '?');
            }

            if(!utf8_compliant($data['body'])) {
                $data['body'] = utf8_bad_replace($data['body'], '?');
            }
        }

        $obj->populate($data, $manager);

        if(!empty($data['history_comment'])) { // article
	        $obj->set('history_comment', $data['history_comment']);
        }

        // $objResponse->addAlert(print_r($data, 1));
        // $objResponse->addAlert(print_r($obj));

        $entry_id_field = ($entry_type == 7) ? 'draft[id]' : 'id';
        $entry_id = (!empty($data[$entry_id_field])) ? $data[$entry_id_field] : 0;
        $entry_obj = addslashes(serialize($obj));
        $manager->autosave($id_key, $entry_id, $entry_obj, $entry_type);

        $date_format = $view->conf['lang']['date_format'];
        $time_format = $view->conf['lang']['time_format'];
        
        $time_msg = AppMsg::getMsg('datetime_msg.ini', false, 'time_interval');
        $info_msg = sprintf(
            '%s: <span title="%s %s">%s</span>',
            $view->msg['autosave_draft_saved_msg'],
            strftime($date_format),
            strftime($time_format),
            $time_msg['just_now_msg']
        );
            
        $msg = array(
            'ago' => $time_msg['ago_msg'],
            'minute' => $time_msg['minute_3'],
            'hour' => $time_msg['hour_3'],
        );
        $objResponse->call('showAutosaveBlock',$info_msg, $id_key, $msg);

        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        $growl_cmd = sprintf($growl_cmd, '', $view->msg['autosave_draft_saved_msg']);
        $objResponse->script($growl_cmd);

        return $objResponse;
    }


    static function ajaxDeleteAutoSave($id_key, $manager) {
        $objResponse = new xajaxResponse();
        $manager->deleteAutosaveByKey($id_key);

        return $objResponse;
    }


    static function getAutosaveValues($obj, $manager, $view) {

        if(!empty($_POST['id_key']))  {
            $id_key = $_POST['id_key'];

        } elseif($id_key = $view->controller->getMoreParam('dkey')) {
            $id_key = addslashes($id_key);

        } else {
            $id_key = array($manager->user_id, 1, $obj->get('id'), $view->controller->action);
            if ($view->controller->action != 'update') {
                $id_key[] = time();
            }

            $id_key = md5(serialize($id_key));
        }

        $data = array();
        $data['autosave_key'] = $id_key;

        $autosave_period = 60000 * $view->setting['entry_autosave'];
        $data['autosave_period'] = $autosave_period;

        return $data;
    }


    // TAG // ----------------------

    static function getTagBlock($tag, $popup_link = '', $msg = array(), $is_public = 0) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_tag_entry.html');

        $tags = array();
        $str = '[%d, "%s"]';

        $tag = RequestDataUtil::stripVars($tag, array(), true);

		if($is_public) {
			$tpl->tplSetNeeded('/add_tag_msg');
		} else {
			$tpl->tplSetNeeded('/add_tag_btn');
		}

        // set tags
        foreach($tag as $tag_id => $title) {
            $data = array();
            $data['tag_id'] = $tag_id;
            $data['tag_title'] = $title;

            $tags[] = sprintf($str, $data['tag_id'], $data['tag_title']);
			
            if (empty($msg)) {
                $tpl->tplSetNeeded('tag_row/tag_span');

            } else { // client area
                $tpl->tplSetNeeded('tag_row/tag_link');

                $more = array('s' => 1, 'q' => $title, 'in' => 'article_keyword');

                $reg = &Registry::instance();
                $controller = &$reg->getEntry('controller');

                $data['tag_link'] = $controller->getLink('search', false, false, false, $more);
            }

            $tpl->tplParse($data, 'tag_row');
        }

        $v['tags'] = implode(',', $tags);

        $creation_allowed = SettingModel::getQuick(1, 'allow_create_tags');
        $v['creation_allowed'] = $creation_allowed;

        // source url
        $link = AppController::getAjaxLinkToFile('suggest_tag');
        $v['tag_suggest_link'] = $link;


        $v['is_public'] = $is_public;

        $tpl->tplParse(array_merge($v, $msg), 'js');


        $tag_hint = ($creation_allowed) ? 'tag_hint_msg' : 'tag_hint2_msg';
        $tag_hint_msg = ($msg) ? $msg : AppMsg::getMsg('common_msg.ini');
        $tag_hint_msg = str_replace("'", '"', $tag_hint_msg[$tag_hint]);
        $tpl->tplAssign('tag_hint', $tag_hint_msg);

        $tpl->tplAssign('tag_popup_link', $popup_link);
        $tpl->tplAssign('delete_tag_button_display', ($tag) ? 'inline' : 'none');

        $tpl->tplParse($msg);

        if (!empty($msg)) {
            return $tpl;
        }

        return $tpl->tplPrint(1);
    }


    static function ajaxAddTag($string, $manager) {

        $objResponse = new xajaxResponse();


        if (_strlen($string) == 0) {
            return $objResponse;
        }

        $creation_allowed = SettingModel::getQuick(1, 'allow_create_tags');
        if (!$creation_allowed) {
            return $objResponse;
        }

        $titles = $manager->tag_manager->parseTagString($string);
        $manager->tag_manager->saveTag($titles);

        $tags = $manager->tag_manager->getTagArray($titles);
        $tags = RequestDataUtil::stripVars($tags, array(), true);

        $objResponse->addScriptCall('TagManager.createList', $tags);
        $objResponse->addAssign('tag_input', 'value', '');

        return $objResponse;
    }


    static function ajaxGetTags($limit = false, $offset = 0, $manager) {

        $objResponse = new xajaxResponse();


        if ($limit) {
            $limit ++;
        }

        $tags = $manager->tag_manager->getSuggestList($limit, $offset);
        $tags = RequestDataUtil::stripVars($tags, array(), true);

        $end_reached = !$limit || (count($tags) < $limit);
        if (!$end_reached) {
            array_pop($tags);
        }

        $data = array();
        foreach($tags as $v) {
            $data[] = array($v['id'], $v['title']);
        }

        $objResponse->addScriptCall('TagManager.updateSuggestList', $data);

        if ($end_reached) {
            $objResponse->addScriptCall('TagManager.hideAllButtons');

        } else {
            $objResponse->addScriptCall('TagManager.showAllButtons');
        }

        return $objResponse;
    }


    // FEATURED // ----------------------

    static function ajaxFeatureArticle($id, $category_id, $value, $view) {

        $objResponse = new xajaxResponse();

        if ($value) {
            require_once APP_MODULE_DIR . 'knowledgebase/featured/inc/KBFeaturedEntry.php';
            require_once APP_MODULE_DIR . 'knowledgebase/featured/inc/KBFeaturedEntryModel.php';
            
            $view->manager->increaseFeaturedEntrySortOrder($category_id);
            
            $obj = new KBFeaturedEntry;
            $obj->set('entry_type', $view->manager->entry_type);
            $obj->set('entry_id', $id);
            $obj->set('category_id', $category_id);
            
            $f_manager = new KBFeaturedEntryModel;
            $f_manager->save($obj);
            
            $new_value = 0;
            $show_class = 'featured_img_remove';
            $hide_class = 'featured_img_assign';

        } else {
            $new_value = 1;
            $show_class = 'featured_img_assign';
            $hide_class = 'featured_img_remove';

            $view->manager->deleteFeaturedEntry($id, $category_id);
        }

        $ajax = sprintf("xajax_featureArticle(%s, '%s', '%s'); return false;", $id, $category_id, $new_value);

        $objResponse->script(sprintf('$("#featured_link_%s_%s").attr("onclick", "%s");', $category_id, $id, $ajax));
        $objResponse->script(sprintf('$("#featured_link_%s_%s").find("img.%s").show();', $category_id, $id, $show_class));
        $objResponse->script(sprintf('$("#featured_link_%s_%s").find("img.%s").hide();', $category_id, $id, $hide_class));

        return $objResponse;
    }


    // SPECIAL SEARCH // -------------------

    // if some special search used
    static function getSpecialSearchArray() {
        $search = array();
        $search['author'] = "#^author(?:_id)?:(\d+)$#";
        $search['updater'] = "#^updater(?:_id)?:(\d+)$#";
        $search['scheduled'] = "#^scheduled:(\w+)?$#";
        $search['private'] = "#^private(?:-all|-entry|-cat)?:(\w+)?$#";
        //$search['public'] = "#^public(?:-all|-entry|-cat)?:?$#";
        $search['tag'] = "#^tag:(.*?)$#";
        $search['tag2'] = "#^\[(.*?)\]$#";

        return $search;
    }


    static function getSpecialSearchSql($manager, $ret, $string, $entry_only = false) {

        $arr = array();

        if($ret['rule'] == 'id') {
            $arr['where'] = sprintf("AND e.id = '%d'", $ret['val']);

        } elseif($ret['rule'] == 'ids') {
            $arr['where'] = sprintf("AND e.id IN(%s)", $ret['val']);

        } elseif($ret['rule'] == 'author') {
            $arr['where'] = sprintf("AND e.author_id IN(%s)", $ret['val']);

        } elseif($ret['rule'] == 'updater') {
            $arr['where'] = sprintf("AND e.updater_id IN(%s)", $ret['val']);

        } elseif($ret['rule'] == 'scheduled') {
            $arr['from'] = ", {$manager->tbl->entry_schedule} sch";
            $arr['where'] = "AND sch.entry_id = e.id
                                  AND sch.entry_type = '{$manager->entry_type}'
                                  AND sch.num = 1";


        } elseif ($ret['rule'] == 'tag' || $ret['rule'] == 'tag2') {

            $tags = explode(',', addslashes(stripslashes($ret['val'])));
            foreach($tags as $k => $v) {
                $tags[$k] = trim($v);
            }

            $ids = $manager->tag_manager->getTagIds($tags);
            $ids = ($ids) ? implode(',', $ids) : 0;

            $arr['from'] = ", {$manager->tag_manager->tbl->tag_to_entry} tag_to_e";
            $arr['where'] = "AND tag_to_e.entry_id = e.id
                             AND tag_to_e.entry_type = '{$manager->entry_type}'
                             AND tag_to_e.tag_id IN ({$ids})";

        } elseif($ret['rule'] == 'private') {

            // all
            $pr = implode(',', array_merge($manager->private_rule['read'], $manager->private_rule['write']));

            if(empty($ret['val']) ||  $ret['val'] == 'yes' || $ret['val'] == 'y' ) {

            } elseif($ret['val'] == 'write') {
                $pr = implode(',', $manager->private_rule['write']);
            } elseif($ret['val'] == 'read') {
                $pr = implode(',', $manager->private_rule['read']);
            } elseif($ret['val'] == 'none' || $ret['val'] == 'no' || $ret['val'] == 'n') {
                $pr = 0;
            }

            if(strpos($string, 'private-entry') !== false || $entry_only) {
                $arr['where'] = "AND e.private IN({$pr})";

            } elseif(strpos($string, 'private-cat') !== false) {
                $arr['where'] = "AND cat.private IN({$pr})";

            } else {
                $arr['where'] = "AND (e.private IN({$pr}) OR cat.private IN({$pr}))";
            }
        }

        return $arr;
    }


    static function getChildCategoriesFilterSelectRange($categories, $parent_id, $manager) {

        $range = array();
        $range_ = $manager->getCategorySelectRange($categories, $parent_id);

        if(isset($categories[$parent_id])) {
            $range[$parent_id] = $categories[$parent_id]['name'];
        }

        if (!empty($range_)) {
            foreach (array_keys($range_) as $cat_id) {
                $range[$cat_id] = '-- ' . $range_[$cat_id];
            }
        }

        return $range;
    }


    static function getButtons($view, $xajax, $draft_page) {

        $button = array();

        // change link and msg for drafts
        // if only drafts allowed
        if($view->priv->isPrivOptional('insert', 'draft')) {
            $button[$view->msg['add_as_draft_msg']] = $view->getLink('this', $draft_page, '', 'insert');

        } elseif($view->priv->isPriv('insert')) {
            $button['insert'] = $view->getActionLink('insert');
        }

        $category_id = (empty($_GET['filter']['c'])) ? 0 : $_GET['filter']['c'];
        $children_on_display = (!empty($_GET['filter']['ch']));
        $disabled = (!$category_id || $children_on_display);

        if($view->priv->isPriv('update')) {
            $button['...'] = array(array(
                'msg' => $view->msg['reorder_msg'],
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
            ));
        }
        
        $xajax->setRequestURI($view->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $view, 'ajaxGetSortableList'));

        return $button;
    }

}
?>