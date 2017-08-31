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

$dir = str_replace('\\', '/', getcwd()) . '/';  // trying to guess setup directory
//$dir = '/path_to/kb/setup/';                    // set it manually

@$host = $_SERVER['HTTP_HOST'];  // trying to guess http host
// $host = 'domain.com';            // set it manually, example: domain.com



/* DO NOT MODIFY */
ob_start();

session_name('kb_setup');
session_start();

//unset($_SESSION['setup_']);

$dir = str_replace('\\', '/', $dir);
$app_dir = str_replace('setup/', 'admin/', $dir);
define('APP_ADMIN_DIR', $app_dir);

require_once($app_dir . 'config.inc.php');

if(!empty($conf['config_local'])) {
    include $conf['config_local'];
}


$disabled = false;
if(empty($conf['allow_setup'])) {
    $disabled = true;
    if(isset($_SESSION['setup_']['setup_type'])) {
        $step = ($_SESSION['setup_']['setup_type'] == 'install') ? array(9,10) : array(8,9);
        if(in_array($_GET['step'], $step)) {
            $disabled = false;
        }
    }
}

if($disabled) {
    die('Disabled');
}

@set_time_limit(120);

$install_dir = str_replace('setup/', '', $dir);
define('APP_INSTALL_DIR', $install_dir);

$lang = (!empty($_SESSION['setup_']['lang'])) ? $_SESSION['setup_']['lang'] : 'en';


$win = (substr(PHP_OS, 0, 3) == "WIN");
$include_separator = ($win) ? ';' : ':';
$include_path = array();
$include_path[] = $app_dir . 'lib';
$include_path[] = $app_dir . 'lib/Pear';

ini_set('include_path', implode($include_separator, $include_path));


if(!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}

if(!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', 16384);
}

if(version_compare(phpversion(), '5.4', '<' )) {
    error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
} else {
    error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT));
}


require_once 'core/base/BaseApp.php';
require_once 'core/base/BaseView.php';
require_once 'core/base/BaseModel.php';
require_once 'core/app/AppMsg.php';
require_once 'core/app/PageRenderer.php';
require_once 'adodb/adodb.inc.php';
require_once 'eleontev/Assorted.inc.php';
require_once 'eleontev/HTML/FormSelect.php';
require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'eleontev/URL/RequestData.php';
require_once 'eleontev/Util/FileUtil.php';
require_once 'eleontev/Util/HashPassword.php';

require_once $dir . 'inc/ParseSqlFile.php';
require_once $dir . 'inc/CheckConfiguration.php';
require_once $dir . 'inc/SetConfiguration.php';
require_once $dir . 'inc/SetupLoader.php';
require_once $dir . 'inc/SetupView.php';
require_once $dir . 'inc/SetupAction.php';
require_once $dir . 'inc/SetupModel.php';
require_once $dir . 'inc/SetupModelUpgrade.php';
require_once $dir . 'inc/SetupController.php';
require_once $dir . 'inc/SetupPageRenderer.php';


require_once $app_dir . 'lang/' . $lang . '/config_lang.php';
//define('XAJAX_DEFAULT_CHAR_ENCODING', $conf['lang']['meta_charset']); // for xajax
define('APP_LANG',             $lang);
define('APP_MSG_DIR',         $app_dir . 'lang/');

$reg =& Registry::instance();
$reg->setEntry('conf', $conf);
$reg->setEntry('lang', $lang);
$reg->setEntry('dir', $dir);



// IIS fixes // -------------------- 

$http_host_msg = '';
$home_path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])) . '/';

if(strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {

    // IIS generating full path
    if(!empty($host)) {
        $home_path = $host . $home_path;
        $home_path = str_replace('//', '/', $home_path); 

        $http = ($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $home_path = $http . $home_path;
    }
    
    // IIS note to set $_SERVER['HTTP_HOST']
    if(empty($host)) {
        $vars = array('file' => APP_ADMIN_DIR . 'index.php');
        $http_host_msg = AppMsg::getMsg('error_msg.ini', 'setup', 'iis_httphost_notice');
        $http_host_msg = &BoxMsg::factory('error', $http_host_msg, $vars);    
    }    
}


$controller = new SetupController();
$controller->working_dir = $dir;
$controller->home_path = $home_path;
$controller->mod_rewrite = false;

$reg->setEntry('controller', $controller);


$loader = new SetupLoader();
$manager = &$loader->getManager($controller);
$view   = &$loader->getView($controller, $manager);


$page = new SetupPageRenderer();
$page->setObjects($view, $controller, $manager);
$page->template_dir = $dir . 'template/';
$page->template = 'page.html';

$page->assign($conf['lang']['meta_charset'], 'meta_charset');
$page->assign($http_host_msg, 'top_msg');
$page->display();


if($conf['debug_info']) {
    echo getDebugInfo();
}

ob_end_flush();
?>