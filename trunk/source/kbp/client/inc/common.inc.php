<?php
if (isset($_GET['conf'])) { return; }
elseif (!isset($conf))    { return; }


$use_ob_gzhandler = $conf['use_ob_gzhandler'];
if($use_ob_gzhandler) {
    $z = strtolower(ini_get('zlib.output_compression'));
    if($z && $z != 'off') {
        $use_ob_gzhandler = false;
    }
}

if($use_ob_gzhandler) {
    $v_ = (isset($_GET['View'])) ? $_GET['View'] : '';
    if($v_ == 'file' || $v_ == 'afile' || strpos($v_, 'pdf') !== false) {
        $use_ob_gzhandler = false;
    }
}

if($use_ob_gzhandler) { ob_start("ob_gzhandler"); }
else                  { ob_start(); }


// debug
if($conf['debug_speed']) {
    require_once 'speed/_dima_timestat.php';
}

// includes
require_once 'core/base/BaseApp.php';
require_once 'core/base/BaseView.php';
require_once 'core/base/BaseModel.php';
require_once 'core/app/AppModel.php';
require_once 'core/base/SphinxModel.php';
require_once 'core/app/AppMsg.php';

require_once 'eleontev/HTML/FormSelect.php';
require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'eleontev/URL/RequestData.php';
require_once 'eleontev/Util/GetMsg.php';
require_once 'eleontev/Assorted.inc.php';
require_once 'eleontev/Auth/AuthPriv.php';
require_once 'eleontev/Auth/AuthRemote.php';
require_once 'eleontev/Auth/AuthProvider.php';
require_once 'eleontev/PageByPage.php';
require_once 'adodb/adodb.inc.php';
require_once 'Cache/Lite/Output.php';


// if using https
if($conf['ssl_client'] && empty($conf['ssl_skip_redirect'])) {
    $port = ($conf['ssl_client'] == 1) ? 443 : $conf['ssl_client'];
    if($_SERVER['SERVER_PORT'] != $port) {
        header("Location: " . APP_CLIENT_PATH);
        exit();
    }
}

$client_dir = APP_CLIENT_DIR . 'client/inc/';

require_once $client_dir . 'KBClientLoader.php';
require_once $client_dir . 'KBClientBaseModel.php';
require_once $client_dir . 'KBClientModel.php';
require_once $client_dir . 'KBClientView.php';
require_once $client_dir . 'KBClientController.php';
require_once $client_dir . 'KBClientAction.php';
require_once $client_dir . 'KBClientPageRenderer.php';
require_once $client_dir . 'DocumentParser.php';

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingModel.php';
require_once APP_MODULE_DIR . 'tool/list/inc/ListValueModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


// db
$reg =& Registry::instance();
$reg->setEntry('tbl_pref', $conf['tbl_pref']);
$reg->setEntry('conf', $conf);
$reg->setEntry('extra', $conf['extra']);

$db = &DBUtil::connect($conf);
$reg->setEntry('db', $db);

$setting = KBClientModel::getSettings(array(1, 2, 10, 100, 140, 141, 150));

// timezone, set db timezone only if timezone updated in settings
if($setting['timezone'] !== 'system') {
    if(date_default_timezone_set($setting['timezone']) === true) {
        DBUtil::setTimezone($db, date("P"));
    }
}


// language
require_once APP_MSG_DIR . $setting['lang'] . '/config_lang.php';

define('APP_LANG', $setting['lang']);
define('XAJAX_DEFAULT_CHAR_ENCODING', $conf['lang']['meta_charset']); // for xajax


// settings
$setting['auth_check_ip'] = $conf['auth_check_ip'];
$setting['view_template'] = 'default';      // looking for template in this dir
$setting['view_style'] = 'default';         // looking for css with this name
//echo '<pre>', print_r($setting, 1), '</pre>';

// mobile view
if (isset($_GET['mobile'])) {
    if ($_GET['mobile']) {
        unset($_COOKIE['full_view_']);
        setcookie('full_view_', '', time() - 3600); // remove cookie
    } else {
        $_COOKIE['full_view_'] = 1;
        setcookie('full_view_', 1, time() + (86400*365)); // 1 year
    }
}

if(empty($_COOKIE['full_view_'])) { // forced full view
    if(!isset($_COOKIE['mobile_device_'])) {
        $mobile_device = (WebUtil::isMobileDevice2()) ? 1 : 0;
        $_COOKIE['mobile_device_'] = $mobile_device;
        setcookie('mobile_device_', $mobile_device, time() + (86400*7)); // 7 days
    }
    
    if($_COOKIE['mobile_device_']) { $setting['view_format'] = 'mobile'; }
}

// $forced_full_view = (!empty($_COOKIE['full_view_']));
// if(!$forced_full_view && ! WebUtil::isMobileDevice2()) {
    // $setting['view_format'] = 'mobile';
// }

require_once APP_ADMIN_DIR . 'common_share.inc.php';
?>