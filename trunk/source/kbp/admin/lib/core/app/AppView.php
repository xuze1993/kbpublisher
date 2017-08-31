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

class AppView extends BaseView
{

    var $popup = false;
    var $popup_var = 'popup';
    var $form_val = array();
    var $statusable;
    var $status_active = true; // so if user no status priv all new record will be inactive
                               // false = inactive, true = active;

    var $inactive_style = 'color: #5F5F5F;';

    var $append_str = '';
    var $controller;
    var $msg = array();
    var $use_detail = false;

    var $encoding;
    var $week_start = 0;
    var $date_convert;


    function __construct() {

        $reg = &Registry::instance();
        $this->controller = &$reg->getEntry('controller');
        $this->conf = &$reg->getEntry('conf');
        $this->priv = &$reg->getEntry('priv');
        $this->extra = &$reg->getEntry('extra');
        $this->setting = &$reg->getEntry('setting');

        $this->template_dir = $this->controller->working_dir.'template/';
        $this->setCommonMsg();
        $this->setModuleMsg();
        $this->setPopup(@$_GET[$this->popup_var]);

        $this->encoding = $this->conf['lang']['meta_charset'];
        $this->date_convert = $this->getDateConvertFrom($this->conf['lang']);
        if(isset($this->conf['lang']['week_start'])) {
            $this->week_start = $this->conf['lang']['week_start'];
            $reg->setEntry('week_start', $this->week_start);
        }
    }


    function getLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        return $this->controller->getLink($module, $page, $sub_page, $action, $more);
    }

    // set common msg
    function setCommonMsg() {
        $file = AppMsg::getCommonMsgFile('common_msg.ini');
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file, false, false));

        // escape for js
        $this->escapeMsg(array('sure_common_msg', 'no_checked_msg'));
    }

    // set concrete module msg
    function setModuleMsg($module = false) {
        $module = (!$module) ? @$this->controller->module : $module;
        $file = AppMsg::getModuleMsgFile($module, 'common_msg.ini');
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file));
    }


    // in table header
    function shortenTitle($title, $num_signs, $title2 = false) {
        $short_title = ($title2) ? $title2 : $title;
        $short_title = _substr($short_title, 0, $num_signs);
        return sprintf('<div title="%s">%s</div>', $title, $short_title);
    }


    // when we need to add additonal lang file
    function addMsg($file_name, $module = false, $section = false) {
        $this->msg = array_merge($this->msg, AppMsg::getMsg($file_name, $module, $section));
    }


    // when we need to add additonal lang file
    function addMsgPrepend($file_name, $module = false, $section = false) {
        $this->msg = array_merge(AppMsg::getMsg($file_name, $module, $section), $this->msg);
    }


    // we call it view where we call module from diff place
    function addMsgOnOtherModule($file_name, $module) {
        if($this->controller->module != $module) {
            $this->addMsg($file_name, $module);
        }
    }


    // when we need to add additonal lang file
    function addMsgData($msg) {
        $this->msg = array_merge($this->msg, $msg);
    }

    // setting pop_up, if window popup or not to display
    // appropriate template for some lists
    function setPopup($val = false) {
        $this->popup = $val;
    }


    // create buttons, add new, ...
    function getButton($link, $msg, $new_window = false) {

        // $link = (!$new_window) ? sprintf('<a href="%s" class="btnLink">%s</a>', $link, $msg)
                               // : sprintf("<a href=\"javascript:OpenWin('%s', '123123123', '730', '550', 'yes', false,1)\" class=\"btnLink\">%s</a>",  $link, $msg);

        // $html =
        // '<table cellpadding="0" cellspacing="0"><tr>
        // <td><img src="images/button/btn_left.gif" width="20" height="20"></td>
        // <td style="background: url(\'images/button/btn_bgr.gif\') repeat-x;
        //     padding: 0px 10px 2px 10px; white-space:nowrap;">'.$link.'</td>
        // <td><img src="images/button/btn_right.gif" width="2" height="20"></td>
        // </tr></table>' . "\n";


        if($new_window) {
            $link_str = "OpenWin('%s', '123123123', '730', '550', 'yes', false, 1);";
            $link = sprintf($link_str, $link);
        } else {

			if(strpos($link, 'javascript:') !== false) {
				$link_str = str_replace('javascript:', '', $link);
				$link = sprintf("%s;", $link_str);
			} else {
				$link = sprintf("location.href='%s';", $link);
			}
        }

        $title = ($msg == '+') ? $this->msg['add_new_msg'] : $msg;
        $class = ($msg == '+') ? 'button2_add_new_plus' : 'button2_add_new';
        $html = '<input type="button" value="%s" class="button2 %s" title="%s"
                    style="margin: 0px;" onClick="%s">';
        $html = sprintf($html, $msg, $class, $title, $link);

        return $html;
    }


    function getButtons($button_msg) {

        $links = &$this->getHeaderLinks();
		$td_str = '<td style="padding-left: 3px;">%s</td>';
        $buttons = array();
        $buttons[] = '<table cellpadding="0" cellspacing="0"><tr>';
        foreach($button_msg as $msg => $link) {

            $button_msg = (!$msg || $msg == 'insert') ? '+' : $msg;

            if($link == 'insert') {
                if($this->priv->isPriv('insert')) {
                    // $button_msg = (!$msg) ? $this->msg['add_new_msg'] : $msg;
                    $buttons[] = sprintf($td_str, $this->getButton($links['add_link'], $button_msg));
                }

            // dropdown menu, $link array with items in menu
            } elseif(is_array($link)) {
                $buttons[] = sprintf($td_str, $this->getButtonMenu($link, $button_msg));

            } else {
                $buttons[] = sprintf($td_str, $this->getButton($link, $button_msg));
            }
        }

        $buttons[] = '</tr></table>';

        return implode('', $buttons);
    }


    // create buttons, add new, ...
    function getButtonMenu($items, $msg) {

        // $link = sprintf('<a href="..." class="btnLink">%s</a>', $msg);
        //
        // $html = array();
        //
        // $html[] =
        // '<div data-dropdown="#button_menu"><table cellpadding="0" cellspacing="0"><tr>
        // <td><img src="images/button/btn_left_no_arrow.gif" width="2" height="20"></td>
        // <td style="background: url(\'images/button/btn_bgr.gif\') repeat-x;
        //     padding: 0px 10px 2px 10px; white-space:nowrap;">'.$link.'</td>
        // <td><img src="images/button/btn_right.gif" width="2" height="20"></td>
        // </tr></table></div>' . "\n";

        $html = array();

        $title = '';

        $btn = '<input type="button" value="%s" title="%s"
                    class="button2 button2_more"
                    onClick="" data-dropdown="#button_menu">';
        $html[] = sprintf($btn, $msg, $title);


        $html[] = '<div id="button_menu" class="dropdown dropdown-tip dropdown-anchor-right">';
        $html[] = '<ul class="dropdown-menu">';

        // $item_str = '<li><a href="%s">%s</a><li class="dropdown-divider"></li>';
        $action_item_str = '<li><a href="%s">%s</a></li><li class="dropdown-divider"></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li><li class="dropdown-divider"></li>';

        foreach ($items as $item) {
            if($item['link'] === false || !empty($item['disabled'])) {
                $html[] = sprintf($disabled_item_str, $item['msg']);
            } else {
                $html[] = sprintf($action_item_str, $item['link'], $item['msg']);
            }
        }

        $html[] = '</ul></div>';

        return implode('', $html);
    }


    function getFormatedDate($timestamp, $format = false) {

        if($format === false || $format === 'date') {
            $format = $this->conf['lang']['date_format'];
        } elseif($format === 'datetime') {
            $format = $this->conf['lang']['date_format'] . ' ' . $this->conf['lang']['time_format'];
        } elseif($format === 'datetimesec') {
            $format = $this->conf['lang']['date_format'] . ' ' . $this->conf['lang']['sec_format'];
        } elseif($format === 'time') {
            $format = $this->conf['lang']['time_format'];
        }

        return $this->_getFormatedDate($timestamp, $format);
    }


    // generate link for update, delete, etc.
    function getImgLink($path, $img, $msg, $confirm_msg = false) {

        $confirm = ($confirm_msg) ? sprintf("onClick=\"return window.confirm('%s')\"", $confirm_msg) : '';
        $img_tag = sprintf('<img src="images/icons/%s.svg" alt="%s" />', $img, $msg);
        $link = ($path) ? sprintf('<a href="%s" title="%s" %s>%s</a>', $path, $msg, $confirm, $img_tag)
                        : $img_tag;
        return $link;
    }


    function _getActionLink($action_value, $record_id = false, $more_params = array()) {

        $arg = $this->controller->arg_separator;
        $common_link = $this->controller->getCommonLink();
        $action_key = $this->controller->getRequestKey('action');

        $id_param = ($record_id) ? sprintf("%s%s=%d", $arg, $this->controller->id_key, $record_id) : '';

        $action_val = $this->controller->getActionValue($action_value);
        $action_value = ($action_val) ? $action_val : $action_value;
        $more_params = ($more_params) ?  $arg . http_build_query($more_params) : '';

        $str = '%s%s%s=%s%s%s';
        $str = sprintf($str, $common_link, $arg, $action_key, $action_value, $id_param, $more_params);

        return $str;
    }


    // $more_params - array(key=value, key=value);
    function getActionLink($action_val, $record_id = false, $more_params = array()) {
        return $this->_getActionLink($action_val, $record_id, $more_params);
    }


    function &getHeaderLinks() {
        $arg = $this->controller->arg_separator;
        $row['common_link'] = $this->controller->getCommonLink();
        $common_link = $row['common_link'];
        $action_key = $this->controller->getRequestKey('action');

        $row['add_link'] = sprintf('%s%s%s=%s', $row['common_link'], $arg, $action_key,
                                                $this->controller->getActionValue('insert'));

        return $row;
    }


    function getViewListVarsRow($active = NULL) {
        static $i = 0;

        $row = array();
        $row['class'] = ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors
        $row['style'] = ($active !== null && !$active) ? $this->inactive_style : ''; // style for not active

        return $row;
    }


    function _getViewListVarsUpdateData($record_id, $active, $own_record, $row) {

        $row = array();
        $row['update_link'] = $this->controller->getCurrentLink();
        $row['update_img'] = false;

        if($this->isEntryUpdateable($record_id, $active, $own_record)) {
            $row['update_link'] = $this->getActionLink('update', $record_id);
            $row['update_img'] = $this->getImgLink($row['update_link'], 'update', $this->msg['update_msg']);

        } elseif($this->use_detail) {
            $row['update_link'] = $this->getActionLink('detail', $record_id);
            $row['update_img'] = $this->getImgLink($row['detail_link'], 'load', $this->msg['detail_msg']);
        }

        return $row;
    }


    function isEntryUpdateable($record_id, $active, $own_record) {

        $ret = false;

        if($this->priv->isPriv('update')) {

            // with status
            if($this->priv->isPrivStatusAction('update', false, $active)) {
                $ret = true;
            }

            // self update
            if($ret) {
                if($this->priv->isSelfPriv('update') && !$own_record) {
                    $ret = false;
                }
            }
        }

        return $ret;
    }


    function _getViewListVarsStatusData($record_id, $active, $own_record) {

        $row = array();

        $act = $active;
        if(!$this->priv->isPriv('status')) {
        // if(!$this->priv->isPriv('status') || !$this->priv->isPrivStatusAction('status', false, $active)) {
            $act = ($act == 0) ? 'not' : 'not_checked';
        }

        $row['active_link'] = '';
        if($act == 'not') {
            $row['active_img'] = $this->getImgLink('', 'active_d_0', '');

        } elseif($act == 'not_checked') {
            $row['active_img'] = $this->getImgLink('', 'active_d_1', '');

        } else {
            $active_var = ($act == 0) ? '1' : '0';
            $active_img = ($act == 0) ? 'active_0' : 'active_1';
            $active_msg = ($act == 0) ? 'set_active_msg' : 'set_inactive_msg';
            // $active_msg = 'set_status_msg'
            $row['active_link'] = $this->getActionLink('status', $record_id, array('status' => $active_var));
            $row['active_img'] = $this->getImgLink($row['active_link'], $active_img,
                                                   $this->msg[$active_msg],
                                                   $this->msg['sure_status_msg']);
        }

        return $row;
    }


    // used it to set links such as delete, update
    function getViewListVars($record_id = false, $active = NULL, $own_record = true) {

        $row = $this->getViewListVarsRow($active);

        // active link
        $status = $this->_getViewListVarsStatusData($record_id, $active, $own_record);
        $row['active_link'] = $status['active_link'];
        $row['active_img'] = $status['active_img'];

        // detail
        $row['detail_link'] = $this->getActionLink('detail', $record_id);
        $row['detail_img'] = $this->getImgLink($row['detail_link'], 'load', $this->msg['detail_msg']);

        // bulk
        $row['bulk_ids_ch_option'] = '';

        // update
        $update = $this->_getViewListVarsUpdateData($record_id, $active, $own_record, $row);
        $row['update_link'] = $update['update_link'];
        $row['update_img'] = $update['update_img'];

        // delete
        $row['delete_link'] = false;
        $row['delete_img']    = false;
        if($this->priv->isPriv('delete')) {
            if($this->priv->isPrivStatusAction('delete', false, $active)) {
                $row['delete_link'] = $this->getActionLink('delete', $record_id);
                $row['delete_img']    = $this->getImgLink($row['delete_link'], 'delete',
                                                        $this->msg['delete_msg'],
                                                        $this->msg['sure_delete_msg']);
            }

            // self delete
            if($this->priv->isSelfPriv('delete') && !$own_record) {
                $row['delete_link'] = false;
                $row['delete_img'] = false;
            }
        }

        return $row;
    }


    function getViewListVarsJs($record_id = false, $active = NULL, $own_record = true,
                                $actions = array('status', 'update', 'delete')) {

        $row = $this->getViewListVarsRow($active);

        // active link
        $status = $this->_getViewListVarsStatusData($record_id, $active, $own_record);
        $row['active_link'] = $status['active_link'];
        $row['active_img'] = $status['active_img'];

        // bulk
        $row['bulk_ids_ch_option'] = '';

        // we need default update link as it used in dblclick on tr
        // $row['update_link'] = false; //$this->controller->getCurrentLink();
        $row['update_link'] = $this->controller->getCurrentLink(); // 2017-01-19 eleontev

        // actions
        $prev_action = false;
        $action_order = $this->getActionsOrder();
        $action_items = array();
        // $action_item_str = '<li><a href="%s" style="background-image: url(%s);" %s>%s</a></li>';
        $action_item_str = '<li><a href="%s" %s %s>%s</a></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="dropdown-divider"></li>';
        $img_path = 'images/icons/%s.gif';

        foreach ($action_order as $k => $action) {

            $link = false;
            $img = false;
            $msg = false;
            $confirm_msg = false;
            $link_attributes = '';

            $action_values = $action;
            if($action != 'delim') {
                if(in_array($action, $actions, true)) {
                    $action_values = $action;

                // use key for action $actions['update'] = ...
                } elseif(isset($actions[$action])) {

                    // remove emty actions in case if we use it like
                    // $actions['update'] = false;
                    if(empty($actions[$action])) {
                        continue;
                    }

                    if(is_array($actions[$action])) {
                        $action_values = $actions[$action];
                    }

                } else {
                    continue;
                }
            }

            switch ($action) {
                case 'detail':
                    $link = $this->getActionLink('detail', $record_id);
                    $msg = $this->msg['detail_msg'];
                    $img = '';
                    $row['detail_link'] = $link;
                    $row['update_link'] = $link; // by default for dbl click on tr

                    break;

                case 'status':
                    if($row['active_link']) {
                        $link = $row['active_link'];
                        // $msg = $this->msg['set_status_msg'];

                        if($active === NULL) {
                            $msg = $this->msg['set_status_msg'];
                        } else {
                            $msg_key = ($active) ? 'set_inactive_msg' : 'set_active_msg';
                            $msg = $this->msg[$msg_key];
                        }

                        $img = 'load';
                        $confirm_msg = $this->msg['sure_status_msg'];
                    }

                    break;

                case 'clone':
                    if($this->priv->isPriv('insert')) {
                        $more = array('show_msg'=>'note_clone');
                        $link = $this->getActionLink('clone', $record_id, $more);
                        $msg = $this->msg['duplicate_msg'];
                        $img = 'clone';
                    }

                    $row['clone_link'] = $link;

                    break;

                case 'update':

                    if($this->isEntryUpdateable($record_id, $active, $own_record)) {
                        $link = $this->getActionLink('update', $record_id);
                        $msg = $this->msg['update_msg'];
                        $img = 'update';

                    } elseif($this->use_detail) {
                        $link = $this->getActionLink('detail', $record_id);
                        $msg = $this->msg['detail_msg'];
                        $img = 'load';
                    }

                    // we need update link as it is used in dblclick
                    if($link) {
                        $row['update_link'] = $link;
                    }

                    break;

                case 'delete':
                case 'trash':
                    if($this->priv->isPriv('delete')) {
                        if($this->priv->isPrivStatusAction('delete', false, $active)) {
                            $link = $this->getActionLink('delete', $record_id);
                            $msg = $this->msg['delete_msg'];
                            $img = 'delete';
                            $confirm_msg = $this->msg['sure_delete_msg'];
                        }

                        // self delete
                        if($this->priv->isSelfPriv('delete') && !$own_record) {
                            $link = false;
                        }

                        // trash
                        if($action == 'trash') {
                            $msg = $this->msg['trash_msg'];
                            $confirm_msg = $this->msg['sure_common_msg'];
                        }
                    }

                    $row['delete_link'] = $link;

                    break;
            }


            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('clone', 'status', 'update', 'trash', 'delete'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];

                        // reasign update_link to have it valid on dbl click
                        if($action == 'update') {
                            $row['update_link'] = $link;
                        }
                    }
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['img'])) {
                    $img = $action_values['img'];
                }

                if (!empty($action_values['msg'])) {
                    $msg = $action_values['msg'];
                }

                if (!empty($action_values['confirm_msg'])) {
                    $confirm_msg = $action_values['confirm_msg'];
                }

                if (!empty($action_values['link_attributes'])) {
                    $link_attributes = $action_values['link_attributes'];
                }
            }


            if ($link) {
                $confirm = '';
                if($confirm_msg) {
                    $confirm_msg = str_replace(array('\n', '\r'), '', $confirm_msg);
                    $confirm_msg = addslashes($confirm_msg);
                    $confirm = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $link);
                }

                if (!empty($action_values['disabled']) && $action_values['disabled'] === true) {
                    $action_items[] = sprintf($disabled_item_str, $msg);

                } else {
                    $action_items[] = sprintf($action_item_str, $link, $link_attributes, $confirm, $msg);
                }
            }

            // delim
            $last_key = count($action_items)-1;
            if($action == 'delim' && $action_items && $action_items[$last_key] != $divider_str) {
                $action_items[] = $divider_str;
            }
        }


        $options_img = '
        <div data-dropdown="#actions%s" id="trigger_actions%s" style="cursor: pointer;">
            <a href="#">
                <img src="images/icons/action.svg" height="14" alt="action" style="border: 0px;" />
            </a>
        </div>
        <div id="actions%s" class="dropdown dropdown-tip">
            <ul class="dropdown-menu">%s</ul>
        </div>';


        // remove last delims
        if (isset($action_items[$last_key])) {
           if($action_items[$last_key] == $divider_str) {
               unset($action_items[$last_key]);
           }
        }

        $row['options_img'] = false;
        if (!empty($action_items)) {
            $row['options_img'] = sprintf($options_img, $record_id, $record_id, $record_id, implode('', $action_items));
        }

        return $row;
    }


    function getActionsOrder() {
        $order = array(
            'approve',                          'delim',
            'preview', 'public',                'delim',
            'fopen', 'file',                    'delim', // for files
            'detail', 'history', 'activity',    'delim',
            'insert', 'sort',                   'delim', // for category
            'load', 'login',                    'delim',
            'status',                           'delim',
            'clone', 'clone_tree', 'restore',   'delim',
            'draft', 'move_to_draft', 'update', 'delim',
            'trash', 'delete', 'delete_category'
            );

        return $order;
    }


    // this is for form
    function setCommonFormVars(&$obj, $add_msg = false, $update_msg = false) {

        $add_msg = ($add_msg !== false) ? $add_msg : $this->msg['add_new_msg'];
        $update_msg = ($update_msg !== false) ? $update_msg : $this->msg['update_msg'];

        $row['action_title'] = ($this->controller->getAction() == 'insert') ? $add_msg : $update_msg;
        $row['action_link'] = $this->controller->getCurrentLink();
        $row['cancel_link'] = $this->controller->getCommonLink();

        $row['full_cancel_link'] = sprintf("location.href='%s'", $this->controller->getCommonLink());
        if($this->popup) {
            $row['full_cancel_link'] = 'PopupManager.close()';
        }

        $row['required_sign'] = '<span class="requiredSign">*</span>';

        // hidden fields in form to remember a state
        $arr = array();
        foreach($obj->hidden as $v) {
            $arr[$v] =& $obj->properties[$v];
        }

        $row['hidden_fields'] = http_build_hidden($arr);


        // hints
        $file = AppMsg::getCommonMsgFile('tooltip_msg.ini');
        $hint_msg = AppMsg::parseMsgsMultiIni($file);

        if($hint_msg) {
            $hint_js = '<img src="../client/images/icons/help.svg" alt="help" style="cursor: help;width: 16px;height: 16px;" class="_tooltip" title="%s" />';

            foreach(array_keys($hint_msg) as $k) {
                $row[$k] = sprintf($hint_js, $this->stripVars(trim($hint_msg[$k])));
            }
        }

        // ck drag and drop upload
        $row['ck_drop_upload_url'] = $this->controller->getAjaxLinkToFile('ck_upload');

        return $row;
    }


    function setRefererFormVars($referer, $client_link = array()) {
        $row = array();
        if(!empty($referer)) {
            if(strpos($referer, 'client') !== false) {
                $row['cancel_link'] = $this->controller->getClientLink($client_link);

            } elseif(strpos($referer, 'emode') !== false) {
                $row['cancel_link'] = $this->controller->getClientLink($client_link);

            } else {
                $row['cancel_link'] = WebUtil::unserialize_url($referer);
            }

            $row['full_cancel_link'] = sprintf("location.href='%s'", $row['cancel_link']);
            if($this->popup) {
              $row['full_cancel_link'] = 'PopupManager.close()';
            }
        }

        // echo '<pre>', print_r($row['cancel_link'], 1), '</pre>';
        return $row;
    }


    // this is for filter form
    function setCommonFormVarsFilter() {
        $params = $this->controller->full_page_params;
        // $params = $this->controller->getFullPageParams();

        unset($params['filter']);
        unset($params['bp']); // new filter should start from new page

        $row['hidden_fields'] = http_build_hidden($params, true);
        $row['action_link'] = $this->controller->getCurrentLink();
        return $row;
    }


    // to set field active in form depends on user priv
    function setStatusFormVars($active, $use_priv = true, $disabled = false) {

        $action_key = $this->controller->getRequestVar('action');

        if($action_key == 'insert') {
            $is_statusable = ($use_priv && $this->priv) ? $this->priv->isPriv('status') : true;
            $active = ($is_statusable) ? $active : 0;
        } else {
            // checking for self status
            $is_statusable = ($use_priv && $this->priv) ? $this->priv->checkPrivAction('status') : true;
        }

        $is_statusable = ($disabled) ? false : $is_statusable;

        // echo '<pre>', print_r($this->priv, 1), '</pre>';
        // echo '<pre>', print_r($is_statusable, 1), '</pre>';


        $str = '';
        $data = array();
        if(!$is_statusable || !$active) {
            $str = '<input type="hidden" name="active" value="%s">';
            $str = sprintf($str, $active);
        } elseif($active) {
            $str = '<input type="hidden" name="active" value="%s">';
            $str = sprintf($str, 0);
        }

        $disabled = ($is_statusable) ? '' : 'disabled';
        $checked = ($active) ? 'checked' : '';

        $checkbox = '<input type="checkbox" name="active" id="active" value="%s" %s %s>
        <label for="active">%s</label>';
        $str .= sprintf($checkbox, 1, $disabled, $checked, $this->msg['yes_msg']);
        $data['status_checkbox'] = $str;

        return $data;
    }


    // return statuses range depends on user priv
    // we use it where select for statuses - users, articles etc
    function getStatusFormRange($range, $current_status) {

        $rest_range = $this->priv->getPrivStatusSet($range, $this->controller->getAction());
        if(!$rest_range) {
            if(isset($range[$current_status])) {
                $rest_range[$current_status] = $range[$current_status];
            }
        }

        return $rest_range;
    }


    // function &pageByPage($limit, $sql, $per_page = true, $limit_range = array(), $class = 'form', $get_name = false) {
    // changed to $options, 2016-08-23 eleontev
    function &pageByPage($limit, $sql, $options = array()) {

       $per_page = (!empty($options['per_page'])) ? $options['per_page'] : true;
       $limit_range = (!empty($options['limit_range'])) ? $options['limit_range'] : array(10,20,40);
       $class = (!empty($options['class'])) ? $options['class'] : 'form';
       $get_name = (!empty($options['get_name'])) ? $options['get_name'] : false;


        $msg = array(
            $this->msg['page_msg'],
            $this->msg['record_msg'],
            $this->msg['record_from_msg']
        );

        $bp = PageByPage::factory($class, $limit, $_GET);

        if ($get_name) { // new URL param
            $bp->get_name = $get_name;
            unset($bp->query[$get_name]);
            $bp->setGetVars();
        }

        $bp->setMsg($msg);
        $bp->setPerPageMsg($this->msg['per_page_msg']);
        $bp->per_page_range = $limit_range;

        $reg = &Registry::instance();
        $db = &$reg->getEntry('db');

        $bp->countAll($sql, $db);
        $bp->nav = $bp->navigate($per_page);

        return $bp;
    }


    // generate page by page navigation, some buttons, ...
    function &commonHeaderList($nav = '', $left_side = '', $button_msg = true, $bulk_form = true, $tmpl = false) {

        $links =& $this->getHeaderLinks();
        $template = ($tmpl) ? $tmpl : 'common_list_header.html';
        $tpl = new tplTemplatez(APP_TMPL_DIR . $template);
        $tpl->tplAssign('bulk_form_action', $this->getActionLink('bulk'));

        if(is_array($button_msg)) {
            if(!$this->priv->isPriv('insert')) {
                $key = array_search('insert', $button_msg);
                unset($button_msg[$key]);
            }

            $tpl->tplAssign('add_link', $this->getButtons($button_msg));

        } elseif($button_msg !== false && $this->priv->isPriv('insert')) {
            // $button_msg = ($button_msg === true) ? $this->msg['add_new_msg'] : $button_msg;
            $button_msg = ($button_msg === true) ? '+' : $button_msg;
            $tpl->tplAssign('add_link', $this->getButton($links['add_link'], $button_msg));

        } else {
            $tpl->tplAssign('add_link', '');
        }

        if($nav) {
            $tpl->tplSetNeeded('/by_page');
            $tpl->tplAssign('by_page_tpl', $nav);
        }

        if($left_side) {
            $tpl->tplAssign('left_side', $left_side);
        }

        if($bulk_form) {
            $tpl->tplSetNeeded('/bulk_form');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function &titleHeaderList($nav = '', $title = '', $button_msg = true) {
        $tmpl = 'title_list_header.html';
        $ret = $this->commonHeaderList($nav, $title, $button_msg, false, $tmpl);
        return $ret;
    }


    function getBulkForm() {
        return;
    }


    function getBreadCrumbNavigation($data) {
        $nav = array();
        foreach($data as $k => $v) {
            if(!empty($v['link'])) {
                $nav[] = sprintf('<a href="%s">%s</a>', $v['link'], $v['item']);
            } else {
                $nav[] = sprintf('%s', (isset($v['item'])) ? $v['item'] : $v);
            }
        }

        return implode(' -> ', $nav);
    }


    // errors from validate class
    function getErrorJs($errors, $script_block = true) {

        if(!$errors) { return; }

        $js = array();
        $ids = array();

        foreach(array_keys($errors) as $type) {
            foreach(array_keys($errors[$type]) as $k) {
                $f = $errors[$type][$k]['field'];
                if($f) {
                    if(is_array($f)) {
                        foreach($f as $fv) {
                            $ids[] = $fv;
                        }
                    } else {
                        $ids[] = $f;
                    }
                }
            }
        }

        if($ids) {
            $ids = "'" . implode("', '", $ids) . "'";
            $js[] = ($script_block) ? '<script language="javascript" type="text/javascript">' : '';
            $js[] = "function kbpParseFormErrors() {";
            $js[] = sprintf("var errorFields = new Array(%s);", $ids);
            $js[] = "
            var f = document.getElementById(errorFields[0]);
            if(f) {
                f.focus();
                f.select();
            }

            for (var i = errorFields.length - 1; i >= 0; i--){
                f = document.getElementById(errorFields[i]);
                if(f) {
                    f.style.border = 'solid 1px red';
                    f.style.backgroundColor = '#F9E5E5';
                }
            };
            ";

            $js[] = '}';
            $js[] = 'kbpParseFormErrors();';
            $js[] = ($script_block) ? '</script>' : '';
        }

        return implode("\n", $js);
    }


    // if some special search used,
    function isSpecialSearchStr($str) {

        // $str = urldecode($str); 2016-09-06 moved to parseSpecialSearchStr
        // and removed by reference isSpecialSearchStr(&$str) {
        // parse $str and change by reference and in all child "isSpecialSearchStr" it will be ok

        $search = array();
        $search['id'] = "#^(?:id:)?(\d+)?$#"; // "#^(?:\[id:)?(\d+)\]?$#";
        $search['ids'] = "#^(?:ids:)?(\s?[\d,\s?]+)$#";
        $search['custom_range_id'] = "#^custom_range_id:(\s?[\d,\s?]+)$#";
        $search['custom_id'] = "#^custom_id:(\s?[\d,\s?]+)$#";

        return $this->parseSpecialSearchStr($str, $search);
    }


    function parseSpecialSearchStr($str, $preg_arr) {

        $str = urldecode($str);
        $str = trim($str);

        foreach ($preg_arr as $k => $v) {
            preg_match($v, $str, $match);
            // echo '<pre>', print_r($match, 1), '</pre>';

            if(!empty($match[0])) {
                $ret['rule'] = $k;
                $ret['val'] = (isset($match[1])) ? $match[1] : false;

                if($ret['rule'] == 'id') {
                    $ret['val'] = (int) $ret['val'];

                } elseif($ret['rule'] == 'ids') {
                    $ret['val'] = preg_replace(array("#,$#", "#^,#", "#[\s]+#"), "", $ret['val']);
                }

                return $ret;
            }
        }

        return false;
    }


    function getSpecialSearchSql($manager, $ret, $string, $id_field = 'e.id') {

        $arr = array();

        if($ret['rule'] == 'id') {
            $arr['where'] = sprintf("AND {$id_field} = '%d'", $ret['val']);

        } elseif($ret['rule'] == 'ids') {
            $arr['where'] = sprintf("AND {$id_field} IN(%s)", $ret['val']);

        } elseif ($ret['rule'] == 'custom_id') {
            $where = array();
            $where[] = sprintf('AND ecd.field_id IN (%s)', $ret['val']);
            $where[] = 'AND ecd.entry_id = e.id';

            $arr['where'] = implode("\n ", $where);
            $arr['from'] = ", {$manager->tbl->custom_data} ecd";
        }

        return $arr;
    }


    // function getPeriodSql($period, $values, $field, $week_start) {
    //
    //     $cal = new CalendarUtil();
    //     $cal->week_start = $week_start;
    //     $cal->setCalendar();
    //
    //     $sql = '';
    //     $str = "AND $field BETWEEN '%s' AND '%s'";
    //     $str_date = '%s-%s-%s';
    //
    //     switch ($period) {
    //     case 'this_day': // ------------------------------
    //         $start_day = date('Y-m-d');
    //         $end_day = date('Y-m-d', strtotime($start_day) + 86400);
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'previous_day': // ------------------------------
    //         $end_day = date('Y-m-d');
    //         $start_day = date('Y-m-d', strtotime($end_day) - 86400);
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'this_month': // ------------------------------
    //         $start_day = date('Y-m-01');
    //         $end_day = date('Y-m-' . $cal->cur_month_num_days);
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'previous_month': // ------------------------------
    //         $d = $cal->setMonth();
    //         $start_day = date('Y-m-d', $d['prev']);
    //         $end_day = date('Y-m-d', $d['prev']+(($cal->prev_month_num_days-1)*$cal->sek_in_day));
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'this_week': // ------------------------------
    //         $d = $cal->setWeek();
    //         $start_day = date('Y-m-d', $d['start_day']);
    //         $end_day = date('Y-m-d', $d['end_day']);
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //         $this->date_range = array_unique($this->date_range);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'previous_week': // ------------------------------
    //         $d = $cal->setWeek();
    //         $start_day = date('Y-m-d', $d['prev']);
    //         $end_day = date('Y-m-d', $d['prev']+$cal->sek_in_day*6);
    //
    //         $this->start_day = $start_day;
    //         $this->end_day = $end_day;
    //         $this->date_range = TimeUtil::getDateRange($start_day, $end_day);
    //         $this->date_range = array_unique($this->date_range);
    //
    //         $sql = sprintf($str, $start_day, $end_day);
    //         break;
    //
    //     case 'all_period': // ------------------------------
    //         $this->start_day = '2009-01-01';
    //         $this->end_day = date('Y-12-31');
    //         $this->date_range = range(2009, date('Y'));
    //         break;
    //
    //     case 'custom_period': // ------------------------------
    //         $date_from = strtotime(urldecode($values['date_from']));
    //         $date_to = strtotime(urldecode($values['date_to']));
    //
    //         $str = "AND $field BETWEEN '%s 00:00:00' AND '%s 23:59:59'";
    //
    //         if (!$date_from && $date_to) {
    //             $this->end_day = date('Y-m-d', $date_to);
    //             $sql = sprintf("AND $field < '%s 23:59:59'", $this->end_day);
    //         }
    //
    //         if ($date_from && !$date_to) {
    //             $this->start_day = date('Y-m-d', $date_from);
    //             $sql = sprintf("AND $field > '%s 00:00:00'", $this->start_day);
    //         }
    //
    //         if ($date_from && $date_to) { // both dates are valid
    //             $this->start_day = date('Y-m-d', $date_from);
    //             $this->end_day = date('Y-m-d', $date_to);
    //             $sql = sprintf($str, $this->start_day, $this->end_day);
    //         }
    //
    //         if (!$date_from && !$date_to) { // both dates are missing
    //             $this->start_day = date('Y-m-d', time());
    //             $this->end_day = date('Y-m-d', time());
    //             $sql = sprintf($str, $this->start_day, $this->end_day);
    //         }
    //
    //         // echo '<pre>', print_r($sql, 1), '</pre>';
    //         break;
    //     }
    //
    //     return $sql;
    // }


    // it works in all list views for filtering, excluding reports
    function getPeriodSql($period, $values, $field, $week_start) {

        $data = TimeUtil::getPeriodData($period, $values, $week_start);

        $this->start_day = $data['start_day'];
        $this->end_day = $data['end_day'];

        $str = "AND $field BETWEEN '%s' AND '%s'";
        $sql = sprintf($str, $data['start_day'], $data['end_day']);

        if($period == 'custom_period') {

            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));

            $str = "AND $field BETWEEN '%s 00:00:00' AND '%s 23:59:59'";

            if (!$date_from && $date_to) {
                $this->end_day = date('Y-m-d', $date_to);
                $sql = sprintf("AND $field < '%s 23:59:59'", $this->end_day);
            }

            if ($date_from && !$date_to) {
                $this->start_day = date('Y-m-d', $date_from);
                $sql = sprintf("AND $field > '%s 00:00:00'", $this->start_day);
            }

            if ($date_from && $date_to) { // both dates are valid
                $this->start_day = date('Y-m-d', $date_from);
                $this->end_day = date('Y-m-d', $date_to);
                $sql = sprintf($str, $this->start_day, $this->end_day);
            }

            if (!$date_from && !$date_to) { // both dates are missing
                $this->start_day = date('Y-m-d', time());
                $this->end_day = date('Y-m-d', time());
                $sql = sprintf($str, $this->start_day, $this->end_day);
            }

        }

        return $sql;
    }


    // ajax
    function &getAjax(&$obj = false, &$manager = false, $view = false) {

        if($obj) { $this->obj = &$obj; }
        if($manager) { $this->manager = &$manager; }

        $ajax = &AppAjax::factory($view);
        return $ajax;
    }



    function getEditor($value, $cfile, $fname = 'body', $cconfig = array()) {

        require_once APP_ADMIN_DIR . 'tools/ckeditor_custom/ckeditor.php';

        $config_file = array(
          'news' => 'ckconfig_news.js',
          'article' => 'ckconfig_article.js',
          'glossary' => 'ckconfig_glossary.js',
          'custom_field' => 'ckconfig_custom_field.js',
          'export' => 'ckconfig_export.js',
        );

        $CKEditor = new CKEditor();
        $CKEditor->returnOutput = true;
        $CKEditor->basePath = APP_ADMIN_PATH . 'tools/ckeditor/';

        $config = array();
        $config['customConfig'] = APP_ADMIN_PATH . 'tools/ckeditor_custom/' . $config_file[$cfile];

        foreach($cconfig as $k => $v) {
            $config[$k] = $v;
        }

        $events = array();
        // $events['instanceReady'] = 'function (ev) {
        //     alert("Loaded: " + ev.editor.name);
        // }';

        return $CKEditor->editor($fname, $value, $config, $events);
    }


    function getViewEntryTabs($entry, $tabs, $own_record = true, $options2 = array()) {

        $tabs_generate = array();

        if ($entry) {
            $record_id = $entry['id'];
            $active = $entry['active'];
        }

        foreach ($tabs as $k => $tab) {

            $title = false;
            $link = false;
            $highlight = false;
            $options = array();

            $action = (is_array($tab)) ? $k : $tab;

            $action_values = $tab;
            if(in_array($action, $tabs, true)) {
                $action_values = $action;

            // use key for action $tabs['update'] = ...
            } elseif(isset($tabs[$action])) {

                // remove emty actions in case if we use it like
                // $tabs['update'] = false;
                if(empty($tabs[$action])) {
                    continue;
                }

                if(is_array($tabs[$action])) {
                    $action_values = $tabs[$action];
                }

            } else {
                continue;
            }


            switch ($action) {
            case 'detail':
                $link = $this->getActionLink('detail', $record_id);
                $title = $this->msg['detail_msg'];

                break;

            case 'log':
                $link = $this->getActionLink('log', $record_id);
                $title = $this->msg['log_msg'];

                break;

            case 'update':
                if($this->priv->isPriv('update')) {
                    if($this->priv->isPrivStatusAction('update', false, $active)) {
                        $link = $this->getActionLink('update', $record_id);
                        $title = $this->msg['update_msg'];
                    }

                    // self update
                    if($this->priv->isSelfPriv('update') && !$own_record) {
                        $link = false;
                    }

                    // as draft only allowed
                    if($this->priv->isPrivOptional('update', 'draft')) {
                        $link = false;
                    }

                }

                break;
            }


            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('update'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];
                    }
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['title'])) {
                    $title = $action_values['title'];
                }

                if (!empty($action_values['highlight'])) {
                    $highlight = $action_values['highlight'];
                }

                if (!empty($action_values['options'])) {
                    $options = $action_values['options'];
                }
            }

            if($link) {
                $tabs_generate[$action] = array(
                    'link' => AppController::_replaceArgSeparator($link),
                    'title' => $title,
                    'highlight' => $highlight,
                    'options' => $options
                    );
            }
        }


        $str = '';
        if($tabs_generate) {

            // more button with menu
            $more_menu = '';
            if(!empty($options2['more'])) {
                $more_menu = $this->getViewEntryTabsMoreMenu($entry, $options2['more'], $own_record);
                if($more_menu) {
                    $tabs_generate['menu'] = array(
                        'title'  => ucwords($this->msg['more_msg']) . ' <img src="images/icons/dropdown_arrow.svg" />',
                        'link' => 'javascript: return false;',
                        'options' => array('a_extra' => 'data-dropdown="#more_menu"')
                    );
                }
            }

            // back link
            if ($entry) {
                $tabs_generate += $this->getViewEntryTabsBack($record_id, $options2);
            }

            $nav = new AppNavigation;

            $equal_attrib = (!empty($options2['equal_attrib'])) ? $options2['equal_attrib'] : 'action';
            $nav->setEqualAttrib('GET', $equal_attrib);
            // $nav->setTemplate(APP_TMPL_DIR . 'sub_menu_html.html');
            // $nav->setTemplate(APP_TMPL_DIR . 'sub_menu_simple.html');
            $nav->setTemplate(APP_TMPL_DIR . 'btn_menu.html');

            foreach ($tabs_generate as $k => $v) {
                $opt = (!empty($v['options'])) ? $v['options'] : array();
                $nav->setMenuItem($v['title'], $v['link'], $opt);

                // add some more when highlight
                if(!empty($v['highlight'])) {
                    foreach($v['highlight'] as $v2) {
                        $nav->setHighlightMenuItem($v2, $k);
                    }
                }
            }

            if(!empty($entry['title'])) {
                $str_ = '<div class="entryTabTitle">[%d], %s</div>';
                $str = sprintf($str_, $record_id, $entry['title']);
            }

            $str .= $nav->generate() . "<br/><br/>";
            $str .= $more_menu;

            return $str;
        }

        return $str;
    }


    function getViewEntryTabsMoreMenu($entry, $actions, $own_record = true) {

        $record_id = $entry['id'];
        $active = $entry['active'];

        // actions
        $prev_action = false;
        $action_order = $this->getActionsOrder();
        $action_items = array();

        $menu_str = '<div id="more_menu" class="dropdown dropdown-tip dropdown-relative"><ul class="dropdown-menu">%s</ul></div>';
        $action_item_str = '<li><a href="%s" %s %s>%s</a></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="dropdown-divider"></li>';
        $img_path = 'images/icons/%s.gif';

        foreach ($action_order as $k => $action) {

            $link = false;
            $img = false;
            $msg = false;
            $title = false;
            $confirm_msg = false;
            $link_attributes = '';

            $action_values = $action;
            if($action != 'delim') {
                if(in_array($action, $actions, true)) {
                    $action_values = $action;

                // use key for action $actions['update'] = ...
                } elseif(isset($actions[$action])) {

                    // remove emty actions in case if we use it like
                    if(empty($actions[$action])) {
                        continue;
                    }

                    if(is_array($actions[$action])) {
                        $action_values = $actions[$action];
                    }

                } else {
                    continue;
                }
            }

            switch ($action) {
            case 'delete':
                if($this->priv->isPriv('delete')) {
                    if($this->priv->isPrivStatusAction('delete', false, $active)) {
                        $link = $this->getActionLink('delete', $record_id);
                        $title = $this->msg['delete_msg'];
                        $confirm_msg = $this->msg['sure_common_msg'];
                    }

                    // self delete
                    if($this->priv->isSelfPriv('delete') && !$own_record) {
                        $link = false;
                    }
                }

                break;

            case 'clone':
                if($this->priv->isPriv('insert')) {
                    $more = array('show_msg'=>'note_clone');
                    $link = $this->getActionLink('clone', $record_id, $more);
                    $title = $this->msg['duplicate_msg'];
                    $img = 'clone';
                }

                break;
            }

            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('clone', 'trash', 'delete'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];
                    }
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['title'])) {
                    $title = $action_values['title'];
                }

                if (!empty($action_values['confirm_msg'])) {
                    $confirm_msg = $action_values['confirm_msg'];
                }
            }


            if ($link) {
                $confirm = '';
                if($confirm_msg) {
                    $confirm_msg = str_replace(array('\n', '\r'), '', $confirm_msg);
                    $confirm_msg = addslashes($confirm_msg);
                    $confirm = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $link);
                }

                if (!empty($action_values['disabled']) && $action_values['disabled'] === true) {
                    $action_items[] = sprintf($disabled_item_str, $msg);

                } else {
                    $action_items[] = sprintf($action_item_str, $link, $link_attributes, $confirm, $title);
                }
            }

            // delim
            $last_key = count($action_items)-1;
            if($action == 'delim' && $action_items && $action_items[$last_key] != $divider_str) {
                $action_items[] = $divider_str;
            }
        }

        // remove first and last delims
        if (isset($action_items[$last_key])) {
           if($action_items[$last_key] == $divider_str) {
               unset($action_items[$last_key]);
           }
       }

        $menu = '';
        if (!empty($action_items)) {
            $menu = sprintf($menu_str, implode('', $action_items));
        }

        return $menu;
    }


    function getViewEntryTabsBack($record_id, $options = array()) {

        if(!empty($options['back_link'])) {
            $link = $options['back_link'];

        } else {
            $link = $this->controller->getCommonLink();
            if($referer = @$_GET['referer']) {
                if(strpos($referer, 'client') !== false) {
                    $link = $this->controller->getClientLink(array('index'));
                } else {
                    $link = WebUtil::unserialize_url($referer);
                }
            }
        }

        $tabs = array();
        $tabs['spacer'] = array('title'  => 'spacer', 'link' => 1);
        $tabs['back'] = array(
            'title'  => '&#x2190; ' . $this->msg['back_msg'],
            'link' => $link,
            'options' => $options
        );

        return $tabs;
    }


    function ajaxValidateForm($values, $options = array()) {

        $objResponse = new xajaxResponse();
        if (!empty($values['_files'])) {
            $_FILES = $values['_files'];
        }

        $objResponse->script('$("select, input, textarea").removeClass("validationError");');
        $objResponse->script('$("#growls").empty();');

        $func = (empty($options['func'])) ? 'getValidate' : $options['func'];
        $ov = $this->obj->$func($values);

        if($key = array_search('action', $ov['options'])) {
            $ov['options'][$key] = $this->controller->action;
        }

        if($key = array_search('manager', $ov['options'])) {
            $ov['options'][$key] = $this->manager;
        }

		// $is_error = false; // to debug normal submit
        $is_error = call_user_func_array($ov['func'], $ov['options']);
        //$is_error = false;

        if ($is_error) {
            $error_fields = array();
            foreach($this->obj->errors as $type => $num) {
                foreach($num as $v) {
                    $module = ($this->controller->module == 'setting') ? $this->controller->page : $this->controller->module;
                    $msg = AppMsg::getErrorMsgs($module);

                    $error_msg = ($type == 'custom') ? $v['msg'] : $msg[$v['msg']];
                    $error_msg = preg_replace("/\r\n|\r|\n/", '<br />', trim($error_msg));

                    $error_msg_arr = explode(' ', $error_msg);
                    foreach($error_msg_arr as $k2 => $v2) {
                        if (_strlen($error_msg_arr[$k2]) > 33) {
                            $error_msg_arr[$k2] = wordwrap($error_msg_arr[$k2], 33, '<br />', true);
                        }
                    }

                    $error_msg = implode($error_msg_arr, ' ');

                    // size: "large",
                    $str = '$.growl.error({title: "", message: "%s", fixed: true, id: "%s"});';
                    $objResponse->script(sprintf($str, $error_msg, 'growl_' . $v['rule']));

                    if (is_array($v['field'])) {
                        foreach ($v['field'] as $v1) {
                            $error_fields[$v1] = $v['rule'];
                        }

                    } else {
                        $error_fields[$v['field']] = $v['rule'];
                    }
                }
            }

            $objResponse->call('ErrorHighlighter.highlight', $error_fields);
            $objResponse->script('$("#loadingMessagePage").hide();');

            // saml/ldap
            if (!empty($options['callback'])) {
                if ($options['callback'] != 'skip') {
                    $script = $options['callback'] . 'ValidateErrorCallback();';
                    $objResponse->script($script);
                }
            }

        } else {
            $button_name = (empty($options['button_name'])) ? 'submit' : $options['button_name'];
            
            if (!empty($options['callback'])) {
                if ($options['callback'] != 'skip') {
                    $objResponse->call($options['callback'], $button_name);
                }

                //$objResponse->script('$("#loadingMessagePage").hide();');

            } else { // default behaviour
                $objResponse->call('showSpinner', $button_name);

                $script = sprintf('$("input[name=%s]").attr("onClick", "").click();', $button_name);
                $objResponse->script($script);
            }

        }

        return $objResponse;
    }


    // parse filter data, use mysql or sphinx
    function parseFilterSql($manager, $q, $mysql_sql, $sphinx_sql, $options = array()) {

        $arr_keys = array();
        $arr_keys = array('where', 'select', 'join', 'from', 'match', 'group');

        if(!empty($sphinx_sql['match']) && AppSphinxModel::isSphinxOnSearch($q)) {
            foreach($arr_keys as $v) {
                $arr[$v] = '';
                if(isset($sphinx_sql[$v])) {
                    $arr[$v] = implode(" \n", $sphinx_sql[$v]);
                }
            }

            $bp = PageByPage::factory('form', $manager->limit, $_GET);
            $sort = &$this->getSort();

            $smanager = new AppSphinxModel;
            $smanager->setIndexParams($options['index']);

            if(!empty($options['own'])) {
                $reg =& Registry::instance();
                $priv = $reg->getEntry('priv');

                $smanager->setOwnParams($manager, $priv);
            }

            if(!empty($options['entry_private'])) {
                $smanager->setEntryRolesParams($manager, 'write');
            }

            if(!empty($options['cat_private'])) {
                $smanager->setCategoryRolesParams($manager, $options['cat_private']);
            }

            $smanager->setSqlParams($arr['where']);
            $smanager->setSqlParamsSelect($arr['select']);
            $smanager->setSqlParamsMatch($arr['match']);
            $smanager->setSqlParamsOrder($sort->getSql());

            $group = (!empty($arr['group'])) ? $arr['group'] : null;

            $arr = array_map(create_function('$n', 'return null;'), $arr);
            $ids = $smanager->getRecordsIds($bp->limit, $bp->offset);
            // echo '<pre>', print_r($ids, 1), '</pre>';

            $id_field = (!empty($options['id_field'])) ? $options['id_field'] : 'e.id';

            if(!empty($ids)) {
                $arr['where'] = sprintf('AND %s IN(%s)', $id_field, implode(',', $ids));
                $arr['sort'] = sprintf('ORDER BY FIELD(%s, %s)', $id_field, implode(',', $ids));

            } else {
                $arr['where'] = 'AND 0';
                $arr['sort'] = 'ORDER BY id';
            }

            if (!empty($group)) { // for mysql
                $arr['group'] = $group;
            }

            $arr['count'] = $smanager->getCountRecords();
            $arr['offset'] = 0;

        } else {

            foreach($arr_keys as $v) {
                $arr[$v] = '';
                if(isset($mysql_sql[$v])) {
                    $arr[$v] = implode(" \n", $mysql_sql[$v]);
                }
            }

            // $arr['count'] = false;
            // $arr['sort'] = false;
        }

        return $arr;
    }

}
?>