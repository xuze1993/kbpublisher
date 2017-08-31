<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005 Evgeny Leontev                                    |
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

// ASSORTED CLASSES AND FUNCTIONS INCLUDED ON EVERY REQUEST
class Registry
{
    var $_cache;

    function __construct() {
        $this->_cache = array();
    }

    function setEntry($key, &$item) {
        $this->_cache[$key] = &$item;
    }

    function &getEntry($key) {
        return $this->_cache[$key];
    }

    function isEntry($key) {
        return ($this->getEntry($key) !== null);
    }

    static function & instance() {
        static $registry;
        if (!$registry) { $registry = new Registry(); }
        return $registry;
    }
}


// to place all useful functions if not sure where to place
class ExtFunc
{

    // @example valueToArray($elem1, $elem2, $elem3, ...);
    // @return array('$elem1' => '$elem1', ...);
    static function valueToArray($elem) {
        $ar = func_get_args();
        foreach($ar as $v) {
            $new_ar[$v] = $v;
        }
        return $new_ar;
    }


    // recursive
    static function arrayToString(&$arr, $glue = ',') {
        foreach($arr as $k => $v) {
            if(is_array($v)) {
                $arr[$k] = ExtFunc::arrayToString($v, $glue);
            }
        }
        return (join($glue, $arr));
    }


    // convert multidimensional array flat array
    static function &multiArrayToOne($arr, $out = array()) {
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $out = ExtFunc::multiArrayToOne($arr[$k], $out);
            } else {
                $out[] = $arr[$k];
            }
        }

        return $out;
    }
}


class WebUtil
{

    static function getIP() {

        $ip = 'UNKNOWN';
        if    (!empty($_SERVER['HTTP_CLIENT_IP']))       { $ip = $_SERVER['HTTP_CLIENT_IP']; }
        // elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        elseif(!empty($_SERVER['REMOTE_ADDR']))          { $ip = $_SERVER['REMOTE_ADDR']; }
		
        // fix for mysql 5.7 error - string error for INET_ATON('::1') 
        $ip =($ip == '::1') ? '127.0.0.1' :  $ip;
		
        return $ip;
    }


    static function serialize_url($url) {
        return rawurlencode(serialize($url));
    }


    static function unserialize_url($url) {
        return unserialize(urldecode(stripslashes($url)));
    }


    static function getFileSize($file, $format = false){

        if(is_numeric($file)) {
            $file_size = $file+0;
        } else {
            $file_size = filesize($file);
        }

        // usefull in listing
        if($format) {
            if ($format == 'gb') {
                $file_size = number_format($file_size / 1073741824 * 100 / 100, 2, '.', ' ') ." gb";
            } elseif ($format == 'mb') {
                $file_size = number_format($file_size / 1048576 * 100 / 100, 2, '.', ' ') ." mb";
            } elseif ($format == 'kb') {
                $file_size = number_format($file_size / 1024 * 100 / 100, 2, '.', ' ') ." kb";
            } else {
                $file_size = number_format($file_size, 0, '.', ' ') . " b";
            }

        } else {

            if ($file_size >= 1073741824) {
                $file_size = round($file_size / 1073741824 * 100) / 100 ." gb";
            } elseif ($file_size >= 1048576) {
                $file_size = round($file_size / 1048576 * 100) / 100 ." mb";
            } elseif ($file_size >= 1024) {
                $file_size = round($file_size / 1024 * 100,-2) / 100 ." kb";
            } else {
                $file_size = round($file_size) . " b";
            }
        }

        return $file_size;
    }


	static function generatePassword($num_sign = 3, $num_int = 2) {
		mt_srand(time());
		$a[] = preg_replace_callback("/(.)/",
			function ($matches) {
				return chr(mt_rand(ord('m'),ord('z')));
			},str_repeat('.',$num_sign));

		$a[] = preg_replace_callback("/(.)/",
		function ($matches) {
				return chr(mt_rand(ord('A'),ord('Z')));
			}, str_repeat('.',$num_sign));

		$a[] = preg_replace_callback("/(.)/",
		function ($matches) {
			return chr(mt_rand(ord('0'),ord('9')));
		}, str_repeat('.',$num_int));

		return str_shuffle(implode('', $a));
	}


    static function sendFile($params, $filename = NULL, $attachment = true) {

        require_once 'HTTP/Download.php';
        PEAR::setErrorHandling(PEAR_ERROR_PRINT);

        session_write_close();
        ini_set('zlib.output_compression', 'Off');

        $http_download = ($attachment) ? HTTP_DOWNLOAD_ATTACHMENT : HTTP_DOWNLOAD_INLINE;
        $h = new HTTP_Download($params);
        $h->setContentDisposition($http_download, $filename);

        // if use inline it works ok but if user want/choose "save as"
        // the name for the file looks strange id number for Safary and  "O5dG__E9.html.part" for FF
        // the same ting with HTTP_DOWNLOAD_INLINE but with this user can save just by click
        // $h->setContentDisposition(HTTP_DOWNLOAD_INLINE, $data['filename']);

        return $h->send();
    }


    // detect if it's a mobile device
    static function isMobileDevice() {
        $ret = false;

        if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$useragent = $_SERVER['HTTP_USER_AGENT'];
            $pattern1 = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino|android|ipad|playbook|silk/i';
            $pattern2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

            if (preg_match($pattern1, $useragent) || preg_match($pattern2, substr($useragent, 0, 4))) {
                $ret = true;
            }
		}

        return $ret;
    }


    static function isMobileDevice2() {
        require_once 'Mobile-Detect/Mobile_Detect.php';
        $detect = new Mobile_Detect;
        
        $is_mobile = $detect->isMobile();
        $is_tablet = $detect->isTablet();
        
        return ($is_mobile || $is_tablet);
    }
    
    
    // function getMaxFileSize($size = 'system') {
        // return ($size == 'system') ? ini_get('upload_max_filesize') : $size;
    // }
    
    
    // return size in bytes for upload_max_filesize 
    // and others identical
    static function getIniSize($key) {
        $value = ini_get($key);
        if(!is_numeric($value)) {
            $value = self::returnBytes($value);
        }
        
        return $value;
    }


    // return size in bytes, $value = '50M' or '21k';
    static function returnBytes($val) {
    
        $val = trim($val);
        $val_int = substr($val, 0, strlen($val) - 1);
        if (!$val_int) {
            return 0;
        }
    
        $last = strtolower($val[strlen($val)-1]);
    
        switch($last) {
            case 'g':
                $val_int *= 1024;
            case 'm':
                $val_int *= 1024;
            case 'k':
                $val_int *= 1024;
        }

        return $val_int;
    }
}


class DBUtil
{

    static function error($sql = null, $real_error = false, $db = false) {

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        if(!$db) {
            $db = &$reg->getEntry('db');
        }

        $format = false;
        if($conf['debug_db_error'] || $real_error) {
            $format = ($conf['debug_db_error'] == 2 || $real_error) ? 'full' : 'short';
        }

        if($conf['debug_db_error'] === 'api') {
            $err = array(
                'num' => $db->ErrorNo(),
                'msg' => $db->ErrorMsg(),
                'sql' => $sql
                );
            register_shutdown_function(array('KBApiError','shutdownDbError'), $err);
            return;
        
        }  elseif($conf['debug_db_error'] === 'cron') {
            return DBUtil::getErrorShortString($db->ErrorMsg(), $db->ErrorNo());
        
        }  elseif($conf['debug_db_error'] === 'cloud') {
            return DBUtil::getErrorShortString($db->ErrorMsg(), $db->ErrorNo());
        }
        
        return DBUtil::getError($db->ErrorMsg(), $db->ErrorNo(), $sql, $format);
    }


    static function getErrorBody($error_msg, $error_num, $sql, $format = 'short') {

        $html = false;
        if($format == 'full') {
            $html =  '<div style="font-size: 12px;">';
            $html .= '<b>SQL ERROR:</b> ' . $error_msg . '<br />';
            $html .= '<b>CODE:</b> ' . $error_num;
            $html .= ($sql) ? '<br /><b>SQL: </b><pre>' . print_r($sql, 1) . '</pre>' : '';
            $html .= '</div>';
        } elseif($format == 'short') {
            $html = sprintf("%s: %s", $error_num, $error_msg);
        }

        return $html;
    }


    static function getError($error_msg, $error_num, $sql, $format = 'short') {

        require_once 'eleontev/HTML/BoxMsg.php';
        $msgs = AppMsg::getMsgs('error_msg.ini', false, 'db_error', 1);

        if($format) {
            $msgs['body'] = DBUtil::getErrorBody($error_msg, $error_num, $sql, $format);
        }

        return BoxMsg::factory('error', $msgs);
    }

    
    function getErrorShortString($error_msg, $error_num) {
        return sprintf("%s: %s", $error_num, $error_msg);
    }


    static function &connect($conf, $error_die = true) {
        
        $db = ADONewConnection($conf['db_driver']);
        @$ret = $db->Connect($conf['db_host'], $conf['db_user'], $conf['db_pass'], $conf['db_base'], true);
        
        if(!$ret) {
            if($error_die) {
                 die (DBUtil::error(false, true, $db));
            } else {
                $ret = false;
                return $ret;
            }   
        }
        
        $db->SetFetchMode(ADODB_FETCH_ASSOC);
        $db->ADODB_COUNTRECS = false;
        $db->debug = (!empty($conf['debug_db_sql']) && !isset($_GET['ajax'])) ? 1 : 0;

        // set connection names, could be required for some situations
        if(!empty($conf['db_names'])) {
            $sql = sprintf("SET NAMES '%s'", $conf['db_names']);
            $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        }

        // sql_mode, remove ONLY_FULL_GROUP_BY, no call in sphinx
        if(empty($conf['sphinx'])) {
            $sql = "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))";
            $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        } else {
            // $sql = "SET sql_mode=''";
            // $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        }

        //echo "<pre>"; print_r($db); echo "</pre>";
        //$db->fnExecute = 'CountExecs';
        //$db->fnCacheExecute = 'CountCachedExecs';

        return $db;
    }


    function beginTrans($db) {
        $db->_Execute($sql = 'SET AUTOCOMMIT=0') or die(db_error($sql));
        $db->_Execute($sql = 'START TRANSACTION') or die(db_error($sql));
    }


    function commitTrans($db) {
        $db->_Execute($sql = 'COMMIT') or die(db_error($sql));
        $db->_Execute($sql = 'SET AUTOCOMMIT=1') or die(db_error($sql));
    }


    static function setTimezone($db, $timezone) {
        $sql = sprintf("SET time_zone = '%s'", $timezone);
        $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
    }
}


// SOME FUNCTIONS
function db_error($sql = null, $real_error = false) {
    return DBUtil::error($sql, $real_error);
}

function &db_connect($conf) {
    return DBUtil::connect($conf);
}

/*
function db_die($sql = null, $real_error = false) {
    
    // if(!$conf) {
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
    // }
    
    $error = DBUtil::error($sql, $real_error);
    
    if($conf['debug_db_error'] === 'cron') {
        trigger_error($error);
        return false;
    } else {
        die($error);
    }
}*/



// to make siglton from any class
// $reg =& Singleton('ClassName');
function & Singleton($class) {
    static $instances;

    if (!is_array($instances)) {
         $instances = array();
    }

    if (!isset($instances[$class])) {
        $instances[$class] = new $class;
    }

    return $instances[$class];
}


function getDebugInfo() {
    require_once 'eleontev/HTML/BoxMsg.php';

    $arr = array('GET'=>$_GET, 'POST'=>$_POST, 'SESSION'=>$_SESSION, 'COOKIE'=>$_COOKIE, 'FILES'=>$_FILES);
    $str = '<span style="font-size: 11px;"><pre>%s</pre></span>';

    $html[] = '<table align="center" style="width: 70%;"><tr><td><br>';

    foreach($arr as $k => $v) {
        //if(!$v) { continue; }
        $msgs = array();
        $msgs['title'] = $k;
        $msgs['body']  = ($v) ? sprintf($str, htmlspecialchars(print_r($v, 1))) : false;
        // $msgs['body']  = ($v) ? sprintf($str, print_r($v, 1)) : false;

        // $html[] = BoxMsg::quickGet($msgs);

        $html[] = BoxMsg::factory('hint', $msgs);

    }

    $html[] = '</td></tr></table>';
    //echo "<pre>"; print_r(apache_request_headers()); echo "</pre>";

    return implode('', $html);
}


// build hidden fields from array of params
function http_build_hidden($formdata, $encode = false, $numeric_prefix = null) {

    // If $formdata is an object, convert it to an array
    if (is_object($formdata)) {
        $formdata = get_object_vars($formdata);
    }

    // Check we have an array to work with
    if (!is_array($formdata)) {
        trigger_error('http_build_hidden() Parameter 1 expected to be Array or Object. Incorrect value given.',
                       E_USER_WARNING);
        return false;
    }

    // If the array is empty, return null
    if (empty($formdata)) {
        return;
    }

    // Start building the query
    $tmp = array ();
    foreach ($formdata as $key => $val) {
        if (is_integer($key) && $numeric_prefix != null) {
            $key = $numeric_prefix . $key;
        }

        if (is_scalar($val)) {
            $str = '<input type="hidden" name="%s" value="%s" />';
            if($encode) {
                array_push($tmp, sprintf($str, urlencode(urldecode($key)), urlencode(urldecode($val))));
            } else {
                array_push($tmp, sprintf($str, $key, $val));
            }

            continue;
        }

        // If the value is an array, recursively parse it
        if (is_array($val)) {
            array_push($tmp, __http_build_hidden($val, $key, $encode));
            continue;
        }
    }

    return str_replace(array('%5B','%5D'), array('[',']'), implode("\n", $tmp));
}


 // Helper function
function __http_build_hidden ($array, $name, $encode) {
    
    $is_list = (array_values($array) === $array);

    $tmp = array ();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            array_push($tmp, __http_build_hidden($value, sprintf('%s[%s]', $name, $key), $encode));

        } elseif (is_scalar($value)) {
            $key = ($is_list) ? '' : $key;
            $str = '<input type="hidden" name="%s[%s]" value="%s" />';
            if($encode) {
                array_push($tmp, sprintf($str,
                    urlencode(urldecode($name)),
                    urlencode(urldecode($key)),
                    urlencode(urldecode($value))));
            } else {
                array_push($tmp, sprintf($str, $name, $key, $value));
            }


        } elseif (is_object($value)) {
            array_push($tmp, __http_build_hidden(get_object_vars($value), sprintf('%s[%s]', $name, $key), $encode));
        }
    }

    return implode("\n", $tmp);
}


if (!function_exists('array_diff_key')) {

    function array_diff_key($arr_reduced, $arr_needed) {
        $r = array();
        foreach($arr_needed as $v) {
            $r[$v] = $arr_reduced[$v];
        }

        return $r;
    }
}


if (!function_exists('array_intersect_key')) {

    function array_intersect_key() {

        $args = func_get_args();
        $array_count = count($args);
        if ($array_count < 2) {
            user_error('Wrong parameter count for array_intersect_key()', E_USER_WARNING);
            return;
        }

        // check arrays
        for ($i = $array_count; $i--;) {
            if (!is_array($args[$i])) {
                user_error('array_intersect_key() Argument #' . ($i + 1) . ' is not an array', E_USER_WARNING);
                return;
            }
        }

        // intersect keys
        $arg_keys = array_map('array_keys', $args);
        $result_keys = call_user_func_array('array_intersect', $arg_keys);

        // build return array
        $result = array();
        foreach($result_keys as $key) {
            $result[$key] = $args[0][$key];
        }

        return $result;
    }
}

?>