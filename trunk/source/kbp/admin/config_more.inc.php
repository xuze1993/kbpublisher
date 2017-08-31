<?php
/* IN MOST CASES THERE IS NO NEED TO EDIT ANYTHING BELOW THIS LINE */
/* --------------------------------------------------------------- */
                            
/* PATHS */

// admin path
$http                     = ($conf['ssl_admin']) ? 'https://' : 'http://';
$conf['admin_dir']        = $_SERVER['DOCUMENT_ROOT'] . $conf['admin_home_dir'];
$conf['admin_path']       = $http . $_SERVER['HTTP_HOST'] . $conf['admin_home_dir'];

// client path
$http                     = ($conf['ssl_client']) ? 'https://' : 'http://';
$conf['client_dir']       = $_SERVER['DOCUMENT_ROOT'] . $conf['client_home_dir'];
$conf['client_path']      = $http . $_SERVER['HTTP_HOST'] . $conf['client_home_dir'];

// other 
$conf['api_path']         = $_SERVER['HTTP_HOST'] . $conf['client_home_dir'] . 'api.php';
$conf['demo_mode']        = 0;


$win = (substr(PHP_OS, 0, 3) == "WIN");
$include_separator = ($win) ? ';' : ':';
$include_path = array();


if(!empty($conf['config_local'])) {
    include $conf['config_local'];
}


// in some cases we have double slash
$conf['admin_dir'] = str_replace('//', '/', $conf['admin_dir']);
$conf['client_dir'] = str_replace('//', '/', $conf['client_dir']);
$conf['cache_dir'] = str_replace('\\', '/', $conf['cache_dir']);

/* DON'T MODIFY */
$conf['product_name']    = 'KBPublisher';
$conf['product_www']     = 'http://www.kbpublisher.com/';
$conf['product_version'] = '6.0.2';
$conf['product_desc']    = 'Knowledge base software';


define('APP_ADMIN_DIR',     $conf['admin_dir']);
define('APP_ADMIN_PATH',    $conf['admin_path']);

define('APP_CLIENT_DIR',    $conf['client_dir']);
define('APP_CLIENT_PATH',   $conf['client_path']);
define('APP_SITE_ADDRESS',  $conf['site_address']);

define('APP_LIB_DIR',       $conf['admin_dir'] . 'lib/');
define('APP_MODULE_DIR',    $conf['admin_dir'] . 'modules/');
define('APP_TMPL_DIR',      $conf['admin_dir'] . 'template/');
define('APP_EMAI_TMPL_DIR', $conf['admin_dir'] . 'template_email/');
define('APP_CACHE_DIR',     $conf['cache_dir']);
define('APP_MAIL_POOL_DIR', $conf['cache_dir']); // subdirs will be created
define('APP_MSG_DIR',       $conf['admin_dir'] . 'lang/');
define('APP_EXTRA_MODULE_DIR', $conf['admin_dir'] . 'extra/');

define('APP_DEMO_MODE',     $conf['demo_mode']);
define('APP_BLOCKED_FILE',  $conf['cache_dir'] . 'app_blocked'); // if file_exists then app will be blocked!


$include_path[] = APP_LIB_DIR;
$include_path[] = APP_LIB_DIR . 'Pear';
//$include_path[] = ini_get('include_path');

//set_include_path(implode($include_separator, $include_path));
ini_set('include_path', implode($include_separator, $include_path));
ini_set('display_errors', 1);
ini_set('arg_separator.output', '&amp;');
@ini_set('pcre.backtrack_limit', 10000000);      // default in PHP 5.2.x is 100000 (it's too small)

// memory limit
if(!empty($conf['memory_limit'])) {
    $ml = ($conf['memory_limit'] == 1) ? 32 : preg_replace("#[^\d]#", '', $conf['memory_limit']);
    ini_set('memory_limit', $ml . 'M');
}

// timezone, in 5.5 timezone added to Settings but this still valid
// will change timezone date 
if(!empty($conf['timezone'])) {
    // date_default_timezone_set($conf['timezone']);
    @ini_set('date.timezone', $conf['timezone']);
}


if(!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}

if(!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', 16384);
}


if($conf['debug_info'] === 2) {

    error_reporting(E_ALL); //2047
    // error_reporting(E_ALL ^ E_NOTICE); // 6135

} elseif($conf['debug_info']) {
    
    if(version_compare(phpversion(), '5.4', '<' )) {
        error_reporting(E_ALL ^ E_DEPRECATED);
    } else {
        error_reporting(E_ALL ^ (E_DEPRECATED | E_STRICT));
    }
    
} else {
    
    if(version_compare(phpversion(), '5.4', '<' )) {
        error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
    } else {
        error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT));
    }
}


if($conf['debug_info']) {
    if (!extension_loaded($conf['db_driver'])) {
        exit('<b>ERROR:</b> '.$conf['db_driver'].' extension not found!<br/>
                 In order to have these functions available, you must compile PHP with MySQL support.<br/>' . "\n");
    }
    
    // if(!isset($_SERVER['DOCUMENT_ROOT'])) {
    //     exit('You should manually specify $_SERVER["DOCUMENT_ROOT"] in admin/config.inc.php<br>' . "\n");
    // }
    // 
    // if(!isset($_SERVER['HTTP_HOST'])) {
    //     exit('You should manually specify $_SERVER["HTTP_HOST"] in admin/config.inc.php<br>' . "\n");
    // }
    
    $de = ini_get('display_errors');
    if(!$de || $de == strtolower('off')) {
        echo '"display_errors" setting is off, if you have blank page please enable it in php.ini<br/>' . "\n";
    }
}

//if (file_exists(APP_BLOCKED_FILE)) {
//    exit('Application is temporarily unavailable. <a href="" onclick="history.go(0);">Retry please</a>.');
//}

?>