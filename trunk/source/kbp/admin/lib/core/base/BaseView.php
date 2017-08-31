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

class BaseView extends BaseApp
{

    var $encoding;


    function _getFormatedDate($timestamp, $format = '%d %b, %Y') {
        $timestamp = (is_numeric($timestamp)) ? $timestamp : strtotime($timestamp);

        if(!empty($this->date_convert)) {
            return iconv($this->date_convert, "UTF-8", strftime($format, $timestamp));
        }

        return strftime($format, $timestamp);
    }


    function getDateConvertFrom($lang) {
        $ret = false;
        $table = array('cp1251');

        if(isset($lang['iso_charset']) && in_array($lang['iso_charset'], $table)) {
            if(strtolower($lang['meta_charset']) == 'utf-8') {
                if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $ret = $lang['iso_charset'];
                }
            }
        }

        return $ret;
    }


    function isExtra($module) {
        return $this->extra[$module];
    }


    function stripVars($values, $skip_keys = array(), $server_check = 'display') {
    // function stripVars(&$values, $skip_keys = array(), $server_check = 'display') {
        return RequestDataUtil::stripVars($values, $skip_keys, $server_check);
    }


    function getTimeInterval($timestamp, $format = false) {
        if(empty($this->msg['interval'])) {
            require_once 'eleontev/Util/TimeUtil.php';
            $this->msg['interval'] = AppMsg::getMsg('datetime_msg.ini', false, 'time_interval');
        }

        return  $this->_getTimeInterval($timestamp, $this->msg['interval'], $this->msg['interval']['ago'], $format);
    }


    function _getTimeInterval($timestamp, $msg, $ago_msg = false, $format = false) {

        if($format === true || $format === 'time') {
            $format = $this->conf['lang']['time_format'];
        }

        $timestamp = (is_numeric($timestamp)) ? $timestamp : strtotime($timestamp);
        $interval = TimeUtil::getInterval($timestamp, $format);

        if($interval['t'] == 'today' || $interval['t'] == 'yesterday') {
            $iret = sprintf($msg[$interval['t']], $interval['i']);
            $ago_msg = false;

        } elseif(is_array($interval['i'])) {
            foreach($interval['i'] as $t => $num) {
                $i = ($num > 1) ? 2 : 1;
                $imsg[] = $num . ' ' . $msg[$t . '_' . $i];
            }

            $iret = implode(' ', $imsg);

        } else {
            $i = ($interval['i'] > 1) ? 2 : 1;
            $inum = $interval['i'];
            $imsg = $msg[$interval['t'] . '_' . $i];
            $iret = $inum . ' ' . $imsg;
            
            if ($inum == 0 && $imsg == 'second') {
                $ago_msg = false;
                $iret = $msg['just_now_msg'];
            }
        }

        return ($ago_msg) ? sprintf($ago_msg, $iret) : $iret;
    }


    static function getSubstring($str, $num_sign, $sufix = '...') {
        if(_strlen($str) > $num_sign) {
            $str = _substr($str, 0, $num_sign);
            $str = explode(' ', $str);
            unset($str[count($str)-1]);
            $str = implode(' ', $str) . $sufix;
        }

        return $str;
    }


    static function getSubstringStrip($str, $num_sign, $sufix = '...') {
        $str = htmlspecialchars_decode($str);
        $str = BaseView::getSubstring($str, $num_sign, $sufix);
        return htmlspecialchars($str);
    }


    static function getSubstringJsEscape($str, $num_sign, $sufix = '...') {
        $str = htmlspecialchars_decode($str);
        $str = BaseView::getSubstring($str, $num_sign, $sufix);
        $str = htmlspecialchars($str);
        return BaseView::jsEscapeString($str);
    }


    static function getSubstringSign($str, $num_sign, $sufix = '...') {
        if(_strlen($str) > $num_sign) {
            $str = _substr($str, 0, $num_sign) . $sufix;
        }

        return $str;
    }


    static function getSubstringSignStrip($str, $num_sign, $sufix = '...') {
        $str = htmlspecialchars_decode($str);
        $str = BaseView::getSubstringSign($str, $num_sign, $sufix);
        return htmlspecialchars($str);
    }


    static function isSubstring($str, $num_sign) {
        if(_strlen($str) > $num_sign) {
            return true;
        }

        return false;
    }


    static function jsEscapeString($str) {
        return RequestDataUtil::jsEscapeString($str);
    }


    // addslashes for required msg, for js
    function escapeMsg($msgs) {
        foreach(array_keys($msgs) as $k) {
            if(isset($this->msg[$msgs[$k]])) {
                $this->msg[$msgs[$k]] = addslashes($this->msg[$msgs[$k]]);
            }
        }
    }


    // ERROR // ------------------------

    // $type = array('key', 'custom', 'formatted');
    function setError($msg_key, $type = 'key') {
        $this->errors[$type][]['msg'] = $msg_key;
    }


    function setErrors($errors) {
        $this->errors = $errors;
    }


    function getErrors($module = false) {
        require_once 'core/app/AppMsg.php';
        return AppMsg::errorBox($this->errors, $module = false);
    }


    // FORM // -----------------------------

    function getChecked($var) {
        return ($var) ? 'checked' : '';
    }


    // use it when wronng form submission
    function setFormData(&$arr) {
        $this->form_data = &$arr;
    }


    // to popularte in form after wronng form submission
    function &getFormData($key = false) {
        $this->form_data['required_sign'] = '<span class="requiredSign">*</span>';

        if($key) {
            $data = (isset($this->form_data[$key])) ? $this->form_data[$key] : false;
        } else {
            $data = &$this->form_data;
        }

        return $data;
    }


    function setDatepickerVars($timestamps) {
        $row = array();

        if (!is_array($timestamps)) {
            $timestamps = array($timestamps);
        }

        $date_parts = array('Y', 'm', 'd');
        for ($i = 0; $i < count($timestamps); $i ++) {
            $timestamp = $timestamps[$i];
            $date_obj_params = array();

            foreach ($date_parts as $part) {
                $date_obj_param = date($part, $timestamp);
                if ($part == 'm') {
                    $date_obj_param --;
                }
                $date_obj_params[] = $date_obj_param;
            }
            $row['date_formatted_' . ($i + 1)] = implode(',', $date_obj_params);
        }

        $date_format = TimeUtil::getDateFormat();
        $row['date_format'] = $date_format;
        $row['date_format_formatted'] = str_replace('yy', 'yyyy', $date_format);
        $row['week_start'] = $this->week_start;
        $row['current_date'] = date('m/d/Y');

        return $row;
    }


    // private

    static function getPrivateTypeMsg($private, $msg) {
        $_msg = array(1 => 'private2_readwrite_msg',
                      2 => 'private2_write_msg',
                      3 => 'private2_read_msg');

        return ($private) ? $msg[$_msg[$private]] : '';
    }
}
?>