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

require_once 'core/common/CommonExportView.php';
require_once 'eleontev/CalendarUtil.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Item/PersonHelper.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class ReportEntryUserView_list extends AppView
{

    var $template = 'list.html';
    var $template_inverted = 'list_inverted.html';
    var $entry_type = 'all'; // default
    var $action_type = 'all'; // default
    var $default_period = 'this_week';
    var $entry_id = false;
    var $user_id = false;
    var $start_day;
    var $end_day;
    var $left_filter = true;


    function execute($obj, $manager) {
        $template = (!empty($_GET['filter']['invert'])) ? $this->template_inverted : $this->template;
        $tpl = $this->_executeTpl($obj, $manager, $template);

        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _executeTpl($obj, $manager, $template) {

        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        $this->addMsg('report_msg.ini');
        $this->addMsg('datetime_msg.ini');


        $this->template_dir = APP_MODULE_DIR . 'report/user/template/';
        $tpl = new tplTemplatez($this->template_dir . $template);

        if (!empty($_GET['filter']['e'])) {
            $this->entry_type = $_GET['filter']['e'];
        }

        if (!empty($_GET['filter']['t'])) {
            $this->action_type = $_GET['filter']['t'];
        }

        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);

        if ($this->left_filter) {
            $tpl->tplSetNeeded('/filter');
            $tpl->tplAssign('filter_block_tmpl', $this->getFilter($manager));
        }

        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        $hidden_fields = array(
            'module' => 'report',
            'page' => 'report_entry',
            'filter[t]' => $this->action_type);

        $entry_filter_params = $this->getEntryFilterParams();
        $hidden_fields = array_merge($hidden_fields, $entry_filter_params);

        $tpl->tplAssign('hidden_fields', http_build_hidden($hidden_fields));


        // navigation
        $period = (empty($_GET['filter']['p'])) ? $this->default_period : $_GET['filter']['p'];
        if ($period != 'all_period') {
            if (!$this->left_filter) {
                $entry_filter_params['action'] = 'activity';
                $entry_filter_params['id'] = $this->user_id;
            }
            $tpl->tplAssign('nav', $this->getNavigation($period, $entry_filter_params));
        }


        $entry_types = array('all' => '__') + $manager->getEntrySelectRange();
        $actions = $manager->getUserActionSelectRange('all');

        if (!empty($_GET['filter']['invert'])) {
            $rows = $this->stripVars($manager->getUnrelatedUsers());

            foreach($rows as $entry => $row) {

                $more = array('id' => $row['id']);
                $link = $this->getLink('user', 'user', false, 'detail', $more);

                $row['user'] = sprintf('<a href="%s">%s %s</a>', $link, $row['first_name'], $row['last_name']);
                $row['row_id'] = md5(serialize($row));

                $tpl->tplAssign($this->getViewListVars());

                $tpl->tplParse($row, 'row');
            }

        } else {
            if ($this->user_id) {
                if (!$manager->checkAllTimeActivities($this->user_id)) {
                    $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
                    $msgs = AppMsg::parseMsgsMultiIni($file);
                    $msg['title'] = '';
                    $msg['body'] = $msgs['note_report_user_no_activity'];

                    $tpl->tplSetNeeded('/no_report');
                    $tpl->tplAssign('hint', BoxMsg::factory('hint', $msg));

                    return $tpl;
                }
            }

            $tpl->tplSetNeeded('/report');

            $bp =& $this->pageByPage($manager->limit, $manager->getCountRecordsSql());
            $tpl->tplAssign('header', $bp->nav . '<br />');

            $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('extra_data'));

            $user_ids = $manager->getValuesString($rows, 'user_id');
            $users = (empty($rows)) ? array() : $manager->getUserByIds($user_ids);

            // titles
            $_data = array();
            foreach($rows as $row) {
                $_data[$row['entry_type']][] = $row['entry_id'];
            }
            
            $titles = $this->stripVars($manager->getEntryTitles($_data));

            $day = false;
            foreach($rows as $entry => $row) {
                $parts = explode(' ', $row['date_action']);
                $_day = $parts[0];

                if ($_day != $day) {
                    $tpl->tplSetNeeded('row/row_day');
                    $row['day'] = $this->getFormatedDate($row['date_action']);

                    $more = array(
                        'filter[p]' => 'custom_period',
                        'filter[date_from]' => date('Ymd', strtotime($row['date_action'])),
                        'filter[date_to]' => date('Ymd', strtotime($row['date_action'])),
                    );
                    $more += $entry_filter_params;
                    $row['day_filter_link'] = $this->getLink('this', 'this', false, false, $more);

                    $day = $_day;
                }

                if (empty($users[$row['user_id']])) {
                    $row['user'] = sprintf('-- (ID: %s)', $row['user_id']);

                } else {
                    $tpl->tplSetNeeded('row/user_details');

                    $user = $users[$row['user_id']];
                    $row['user'] = PersonHelper::getShortName($user);

                    $more = array('id' => $row['user_id']);
                    $row['user_details_link'] = $this->getLink('users', 'user', false, 'detail', $more);
                }

                $more = $entry_filter_params + array('filter[u]' => $row['user_id']);
                $row['user_filter_link'] = $this->getLink('this', 'this', false, false, $more);

                $row['date_formatted'] = $this->getFormatedDate($row['date_action'], 'time');

                $more = array(
                    'filter[e]' => $row['entry_type'],
                    'filter[q]' => $row['entry_id']
                );
                $more += $entry_filter_params;
                $row['entry_filter_link'] = $this->getLink('this', 'this', false, false, $more);
				if($this->controller->module == 'users') {
					$more += array('id'=>$obj->get('id'));
					$row['entry_filter_link'] = $this->getLink('this', 'this', false, 'activity', $more);
				}

                if (!empty($titles[$row['entry_type']][$row['entry_id']])) {
                    $row['entry_title'] = $titles[$row['entry_type']][$row['entry_id']];

                    foreach (UserActivityLog::$module as $module => $v) {
                        foreach ($v as $page => $entry_type_id) {
                            if ($entry_type_id == $row['entry_type']) {
                                $tpl->tplSetNeeded('row/entry_details');
                                $row['entry_details_link'] = $this->getLink($module, $page, false, 'detail', array('id' => $row['entry_id']));
                            }
                        }
                    }
                }

                $tpl->tplAssign($this->getViewListVars());

				// echo '<pre>$actions: ', print_r($actions, 1), '<pre>';
				// echo '<pre>$row[action_type]: ', print_r($row['action_type'], 1), '<pre>';

                $row['action'] = $actions[$row['action_type']];
                $row['entry_type'] = $entry_types[$row['entry_type']];

                $row['row_id'] = md5(serialize($row));

                if ($row['entry_id']) {
                    $tpl->tplSetNeeded('row/entry_id');
                }

                if ($row['extra_data']) {
                    $tpl->tplSetNeeded('row/extra_data');
                    $row['extra_data'] = $this->getExtraDataMessage($row['extra_data']);
                }

                $tpl->tplParse(array_merge($row, $this->msg), 'row');
            }
        }

        // export
        $export_types = array('xml', 'csv', 'xls');
        foreach ($export_types as $export_type) {
            $more = array('type' => $export_type);
            $export_links[$export_type] = $this->getActionLink('file', false, $more);
        }

        $tpl->tplAssign('export_block',
            CommonExportView::getExportFormBlock($obj, $manager, $export_links, false));


        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());

        return $tpl;
    }


    function getExtraDataMessage($data) {
        $data = unserialize($data);

        $str = array();

        if (!empty($data['bulk_action'])) {
            $line = '<b>Bulk Action</b>: ' . ucwords($data['bulk_action']);

            if (!empty($data['bulk_sub_action'])) {
                $line .= ' &rarr; ' . ucwords($data['bulk_sub_action']);
            }

            $str[] = $line;
        }



        if (!empty($data['ids'])) {
            $str[] = '<b>ID(s)</b>: ' . implode(', ', $data['ids']);
        }

        return implode('<br/>', $str);
    }

    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);

        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setDefaultSortItem('date', 1);

        $sort->setSortItem('date_msg', 'date', 'date_month, date_action', $this->msg['date_msg']);

        return $sort;
    }


    function getFilter($manager) {
        $tpl = $this->_executeFilterTpl($manager, 'form_filter.html');
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _executeFilterTpl($manager, $template) {
        @$values = $_GET['filter'];

        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }

        $tpl = new tplTemplatez($this->template_dir . $template);

		// types
        $entry_types = array('all' => '__') + $manager->getEntrySelectRange();
        foreach ($entry_types as $k => $v) {
            $_actions = array('all' => '__') + $manager->getUserActionSelectRange($k);
            $json_body = array();
            foreach ($_actions as $k1 => $v1) {
                $s = ($k1 == $this->action_type) ? 'true' : 'false';
                $json_body[] = sprintf('{"val": "%s", "text": "%s", "s": %s}', $k1, $v1, $s);
            }

            $json[] = sprintf("\"%s\": [\n%s\n]", $k, implode(",\n", $json_body));
        }

        $tpl->tplAssign('myOptionsJson', implode(",\n", $json));

        $popup_links = array();
        foreach (UserActivityLog::$module as $module => $v) {
            foreach ($v as $page => $id) {
                $link = $this->getLink($module, $page);
                $popup_links[] = sprintf('%d: "%s"', $id, $this->controller->_replaceArgSeparator($link));
            }
        }

        $tpl->tplAssign('popup_links', implode(',', $popup_links));


        $select = new FormSelect();
        $select->select_tag = false;

        $select->setRange($manager->getEntrySelectRange(), array('all' => '__'));
        $tpl->tplAssign('entry_select', $select->select($this->entry_type));

        $select->setRange($manager->getUserActionSelectRange(), array('all' => '__'));
        $tpl->tplAssign('action_select', $select->select($this->action_type));

        // period
        $select->setRange($manager->getReportPeriodSelectRange($this->msg));

        $tpl->tplAssign('daily_datepicker_display', 'block');

        $v = (empty($values['p'])) ? $this->default_period : $values['p'];
        $tpl->tplAssign('custom_display', ($v == 'custom_period') ? 'block' : 'none');
        $tpl->tplAssign('period_select', $select->select($v));

        if (empty($v) || $v != 'custom_period' ) {
            $date_from = time();
            $date_to = $date_from;
        } else {
            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));
        }

        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));

        $tpl->tplAssign('invert_checked', (empty($values['invert'])) ? '' : 'checked');

        $user_popup_link = $this->getLink('user', 'user');
        $tpl->tplAssign('user_popup_link', $user_popup_link);

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);

        if (!empty($values)) {
            $tpl->tplAssign($values);
        }

        return $tpl;
    }


    function getFilterSql($manager) {

        $arr = array();

        @$values = $_GET['filter'];

        @$v = $values['e'];
        if($v != 'all' && isset($values['e'])) {
            $v = (int) $v;
            $arr[] = "AND entry_type = '$v'";
            $this->entry_type = $v;
        }

        @$v = $values['t'];
        if($v != 'all' && isset($values['t'])) {
            $v = (int) $v;
            $arr[] = "AND action_type = '$v'";
            $this->action_type = $v;
        }

        // period
        $timespan = (empty($_GET['timespan'])) ? false : $_GET['timespan'];
        $v = (empty($values['p'])) ? $this->default_period : $values['p'];
        $arr[] = $this->getReportPeriodSql($v, $values, $timespan);
        if ($timespan) {
            $_GET['filter']['p'] = 'custom_period';
            $_GET['filter']['date_from'] = $this->start_day;
            $_GET['filter']['date_to'] = $this->end_day;
        }

        // user id
        @$v = $values['u'];
        if(!empty($v)) {
            $v = trim($v);

            if (is_numeric($v)) {
                $this->user_id = $v;

            } else {
                unset($_GET['filter']['u']);
            }
        }

        if ($this->user_id) {
            $arr[] = 'AND user_id = ' . $this->user_id;
        }

        // entry id
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearchStr($v)) {
                $this->entry_id = $ret['val'];

                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 'entry_id')) {
                    $arr[] = $sql['where'];
                }

            } else {
                unset($_GET['filter']['q']);
            }
        }


        // user ip
        @$v = $values['ip'];
        if(!empty($v)) {
            $v = trim($v);

            $s = strlen($v);
            if($v[$s - 1] == '.') {
                $v = substr($v, 0, $s - 1);
            }

            $ip = explode('.', $v);
            $c = count($ip);

            if ($c != 4) {

                $ip_mask_start = $ip;
                $ip_mask_end = $ip;

                for($i = 0; $i < 4; $i++) {
                    if (!isset($ip[$i])) {
                        $ip_mask_start[$i] = '0';
                        $ip_mask_end[$i] = '255';
                    }
                }

                $ip_mask_start = implode('.', $ip_mask_start);
                $ip_mask_end = implode('.', $ip_mask_end);

                $str = "AND user_ip BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
                $arr[] = sprintf($str, $ip_mask_start, $ip_mask_end);

            } else {

                $arr[] = sprintf("AND user_ip = INET_ATON('%s')", $v);
            }
        }

        $arr = implode(" \n", $arr);
        return $arr;
    }


    function isSpecialSearchStr($str) {
        $search['ids'] = "#^(?:ids:)?(\s?[\d,\s?]+)$#";
        return $this->parseSpecialSearchStr($str, $search);
    }


    function getReportPeriodSql($period, $values, $timespan) {

        if($period == 'all_period') {
            return;
        }

        $data = TimeUtil::getPeriodData($period, $values, $this->week_start);
        $this->start_day = $data['start_day'];
        $this->end_day = $data['end_day'];

        if($timespan) {
            $this->overwritePeriod($timespan);
        }

        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;

        $cal->setCalendar(strtotime($this->start_day));
        $start_month = $cal->year.TimeUtil::prefixZero($cal->month);

        $cal->setCalendar(strtotime($this->end_day));
        $end_month = $cal->year.TimeUtil::prefixZero($cal->month);


        $sql = '';
        $str = "AND date_month BETWEEN '%s' AND '%s' ";
        $sql = sprintf($str, $start_month, $end_month);

        $str = "AND date_action BETWEEN '%s 00:00:00' AND '%s 23:59:59'";
        $sql .= sprintf($str, $this->start_day, $this->end_day);
        // echo '<pre>', print_r($sql, 1), '</pre>';

        return $sql;
    }


    function overwritePeriod($timespan) {

        $cal = new CalendarUtil;
        $cal->week_start = $this->week_start;
        $cal->setCalendar();

        switch ($timespan) {
            case 'day':
            	$this->start_day = date('Y-m-d', strtotime($this->start_day));
                $this->end_day = date('Y-m-d', strtotime($this->start_day));
                break;

            case 'week':
                $day = ($this->week_start) ? 'Monday' : 'Sunday';
                $this->start_day = date('Y-m-d', strtotime('last ' . $day, strtotime($this->start_day)));
                $this->end_day = date('Y-m-d', strtotime($this->start_day) + 3600 * 24 * 6);
                break;

            case 'month':
            	$cal->setCalendar(strtotime($this->start_day));
                $this->start_day = date('Y-m-01', strtotime($this->start_day));
                $this->end_day = date('Y-m-' . $cal->cur_month_num_days, strtotime($this->start_day));
                break;

            case 'year':
                $this->start_day = date('Y-01-01', strtotime($this->start_day));
                $this->end_day = date('Y-12-31', strtotime($this->start_day));
                break;
        }
    }


    function getNavigation($period, $params) {

        $cal = new CalendarUtil();
        $cal->week_start = $this->week_start;
        $cal->setCalendar();


        $select = new FormSelect();
        $select->setSelectWidth(70);
        $select->setSelectName('timespan');
        $select->setOnChangeSubmit(true);

        $msg = AppMsg::getMsg('datetime_msg.ini', false, 'time_interval');
        $timespan_range = array(
            'day' => $msg['day_2'],
            'week' => $msg['week_2'],
            'month' => $msg['month_2'],
            'year' => $msg['year_2']
        );

        $title_attr = sprintf('%s - %s', $this->getFormatedDate($this->start_day), $this->getFormatedDate($this->end_day));

        $range = strtotime($this->end_day) - strtotime($this->start_day);

        $timespan = 'custom';
        if ($range == 0) {
            $timespan = 'day';
        }

        if ($range == 6 * 3600 * 24) {
            $timespan = 'week';

        } else {
            $cal->setCalendar(strtotime($this->end_day));
            if (date('d', strtotime($this->start_day)) == '01' && date('d', strtotime($this->end_day)) == $cal->cur_month_num_days) {
                if (date('m', strtotime($this->start_day)) == date('m', strtotime($this->end_day))) {
                    $timespan = 'month';
                }
            }

            if (date('dm', strtotime($this->start_day)) == '0101' && date('dm', strtotime($this->end_day)) == '3112') {
                $timespan = 'year';
            }
        }


        $params['filter[p]'] = 'custom_period';

        if (in_array($timespan, array('custom', 'day', 'week'))) {
            $_end_day = strtotime($this->start_day) - 3600 * 24;
            $params['filter[date_from]'] = date('Ymd', $_end_day - $range);
            $params['filter[date_to]'] = date('Ymd', $_end_day);

            $prev_link = $this->getLink($this->controller->module, $this->controller->page, false, false, $params);
            $prev_link_title = sprintf('%s - %s', $this->getFormatedDate($_end_day - $range), $this->getFormatedDate($_end_day));

            $_start_day = strtotime($this->end_day) + 3600 * 24;
            $params['filter[date_from]'] = date('Ymd', $_start_day);
            $params['filter[date_to]'] = date('Ymd', $_start_day + $range);

            $next_link_title = sprintf('%s - %s', $this->getFormatedDate($_start_day), $this->getFormatedDate($_start_day + $range));

            if ($timespan == 'day') {
                $title = $this->getFormatedDate($this->start_day);
                $title_attr = $title;
                $prev_link_title = sprintf('%s', $this->getFormatedDate($_end_day));
                $next_link_title = sprintf('%s', $this->getFormatedDate($_start_day));

            } else {
                $title = sprintf('%s - %s', $this->getFormatedDate($this->start_day), $this->getFormatedDate($this->end_day));

                if ($timespan == 'custom') {
                    $days_num = $range / (3600 * 24);
                    array_unshift($timespan_range, sprintf('%d %s', $days_num, $msg['day_2']));
                }
            }

        } elseif ($timespan == 'month') {

			$m = $cal->getTimestampValues();
			$_start_day = $m['next_month_start'];
			$title = strftime('%b %Y', $m['cur_month_start']);

            $params['filter[date_from]'] = date('Ymd', $m['prev_month_start']);
            $params['filter[date_to]'] = date('Ymd', $m['prev_month_end']);

            $prev_link = $this->getLink($this->controller->module, $this->controller->page, false, false, $params);
            $prev_link_title = sprintf('%s - %s', $this->getFormatedDate($m['prev_month_start']),
												  $this->getFormatedDate($m['prev_month_end']));

            $params['filter[date_from]'] = date('Ymd', $m['next_month_start']);
            $params['filter[date_to]'] = date('Ymd', $m['next_month_end']);

            $next_link_title = sprintf('%s - %s', $this->getFormatedDate($m['next_month_start']),
												  $this->getFormatedDate($m['next_month_end']));

        } elseif ($timespan == 'year') {

			$title = $cal->year;
			$_start_day = strtotime($cal->next_year . '0101');

			$params['filter[date_from]'] = $cal->prev_year . '0101';
            $params['filter[date_to]'] = $cal->prev_year . '1231';

            $prev_link = $this->getLink($this->controller->module, $this->controller->page, false, false, $params);
            $prev_link_title = sprintf('%s - %s', $this->getFormatedDate($cal->prev_year . '0101'),
												  $this->getFormatedDate($cal->prev_year . '1231'));

            $params['filter[date_from]'] = $cal->next_year . '0101';
            $params['filter[date_to]'] = $cal->next_year . '1231';

            $next_link_title = sprintf('%s - %s', $this->getFormatedDate($cal->next_year . '0101'),
												  $this->getFormatedDate($cal->next_year . '1231'));
        }


        if (($_start_day - time()) > 0) {
            $next_link = false;

        } else {
            $next_link = $this->getLink($this->controller->module, $this->controller->page, false, false, $params);
        }

        $_str = '<a href="%s" title="%s">%s</a>';

        $select->setRange($timespan_range);

        $nav = array();
        $nav[] = ($prev_link) ? sprintf($_str, $prev_link, $prev_link_title, '&larr; ' . $this->msg['prev_msg']) : '&larr; ' . $this->msg['prev_msg'];
        $nav[] = sprintf('<span title="%s">%s</span>', $title_attr, $title);

        if ($next_link) {
            $nav[] = sprintf($_str, $next_link, $next_link_title, $this->msg['next_msg'] . ' &rarr;');

        } else {
            $nav[] = sprintf('<span style="color: #888888;">%s &rarr;</span>', $this->msg['next_msg']);
        }

        $hidden = $_GET;
        unset($hidden['bp'], $hidden['submit']);
        $hidden = http_build_hidden($hidden);

        $html = '<table cellpadding="4" cellspacing="0" border="0" width="100%%">
        <tr style="background-color: #e7e7e7;">

            <td width="100%%" style="padding: 4px;">
                <form action="" style="margin: 0px;">
                    %s %s %s
                </form>
            </td>
            <td nowrap style="padding-right: 15px; text-align: right;"></td>
            <td style="background-color: #ffffff;padding: 1px;"></td>
            <td style="white-space: nowrap;padding: 5px 10px;">%s</td>
        </tr>
        </table>';

        return sprintf($html, $this->msg['navigate_by_msg'], $hidden, $select->select($timespan), implode(' | ', $nav));
    }


    function getEntryFilterParams() {
        @$values = $_GET['filter'];

        $params = array(
            'filter[e]' => (empty($values['e'])) ? $this->entry_type : $values['e'],
            'filter[t]' => (empty($values['t'])) ? $this->action_type : $values['t'],
            'filter[p]' => (empty($values['p'])) ? $this->default_period : $values['p'],
        );

        if (!empty($values['u'])) {
            $params['filter[u]'] = $values['u'];
        }

        if (!empty($values['q'])) {
            $params['filter[q]'] = $values['q'];
        }

        if (!empty($values['date_from'])) {
            $params['filter[date_from]'] = $values['date_from'];
        }

        if (!empty($values['date_to'])) {
            $params['filter[date_to]'] = $values['date_to'];
        }

        if (!empty($values['invert'])) {
            $params['invert'] = $values['invert'];
        }

        return $params;
    }

}
?>