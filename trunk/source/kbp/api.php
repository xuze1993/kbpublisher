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

$app_dir = str_replace('\\', '/', getcwd()) . '/admin/';   // trying to guess admin directory
//$app_dir = '/path_to/kb/admin/';                         // set it manually

$ssl_port = 443;

/* DO NOT MODIFY */
//if(!is_file($app_dir . 'config.inc.php')) {
//  echo 'Wrong path! Set correct value for $app_dir at the top of index.php <br /><br />';
//}

require_once $app_dir . 'config.inc.php';
require_once $app_dir . 'config_more.inc.php';

// define conf
$conf['ssl_client'] = 0;
$conf['auth_check_ip'] = 0;
$conf['debug_db_error'] = 'api';


require_once APP_CLIENT_DIR . 'client/inc/common.inc.php';
require_once 'eleontev/Validator.php';
require_once 'eleontev/Array2XML.php';

require_once APP_CLIENT_DIR . 'client/api/KBApiLoader.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiResponce.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiController.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiModel.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiError.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiValidator.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiCommon.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiUtil.php';
require_once APP_CLIENT_DIR . 'client/api/KBApiEntryModel.php';


// set api format, need it before any KBApiError call
$conf['api_format'] = 'json';
if(isset($_GET['format'])) {
    if(!in_array($_GET['format'], array('xml', 'json'))) {
        KBApiError::error(25, KBApiError::parseMsg('invalid', 'format'));
    }
    $conf['api_format'] = $_GET['format'];
}

// set api version, need it before any KBApiError call
$conf['api_version'] = 1;
if(isset($_GET['version'])) {
    if(!in_array($_GET['version'], array(1))) {
        KBApiError::error(25, KBApiError::parseMsg('invalid', 'version'));
    }
    $conf['api_version'] = $_GET['version'];
}


// api access 
if(empty($setting['api_access'])) {
    KBApiError::error(28);
}

// only https allowed
$setting['api_secure_port'] = 443;
if($setting['api_secure']) { 
    if($_SERVER['SERVER_PORT'] != $setting['api_secure_port']) {
        KBApiError::error(21);
    }    
}

$required = array('accessKey', 'timestamp', 'signature');
KBApiValidator::validateGetArguments($required);


// retrive private key, session id, etc
$public_key = KBApiController::getRequestVar('accessKey');
$public_key = addslashes($public_key);
$user_ip = WebUtil::getIP();

$am = new KBApiModel();
$info = $am->getApiInfoByPublicKey($public_key);

if(empty($info['user_id'])) {
    KBApiError::error(3);
}


// with session in db
// serilaized session saved in db and we should control to delete/empty it. 
if($info['access'] == 1) { // $info['access'] = 2; //as public user, not logged
    $hash = md5($info['private_key'] . $info['user_id'] . 'wq2TR4&5');
    $_SESSION = array();
    if(!empty($info['session'])) {
        $_SESSION = unserialize($info['session']);
        $_SESSION['auth_']['thua'] = $hash;
    }
    
    if(!AuthPriv::isAuthStatic($conf['auth_check_ip'])) {
        AuthPriv::logout(false);
        $auth = new AuthPriv;
        $auth->setCheckIp($conf['auth_check_ip']);
        $auth->doAuthByValue(array('id' => $info['user_id']));
    
        // fix for api, as it does not have session_id, need some uniq value
        $auth->authToSessionApi($info['user_id'], $public_key, $hash);
    
        $session = serialize($_SESSION);
        $am->saveSession($session, $info['user_id'], $user_ip);
    }
}
// echo '<pre>', print_r(@$_SESSION,1), '<pre>';

$cc = new KBClientController();
$cc->setDirVars($setting);
$cc->arg_separator = '&';
$reg->setEntry('controller', $cc);

$view = new KBClientView();
$reg->setEntry('view', $view);

$controller = new KBApiController();
$controller->setUrlVars();
$controller->setDirVars($setting);

// map search 
if($controller->call == 'search' && !empty($_GET['in'])) {
	$in = $_GET['in'];
    if(strpos($in, 'article') !== false) {
		$controller->call = 'articles';
		$controller->method = 'search';

    } elseif(strpos($in, 'file') !== false) {
		$controller->call = 'files';
		$controller->method = 'search';

    } elseif(strpos($in, 'news') !== false) {
		$controller->call = 'news';
		$controller->method = 'search';
	}
}

KBApiValidator::validateCall($controller->call, $controller->call_map);
KBApiValidator::validateSignature($info['private_key'], $controller);

$manager = &KBApiLoader::getManager($setting, $cc, $controller->call);
$api     = &KBApiLoader::getApi($controller, $manager);

// echo '<pre>', print_r(get_class ($manager),1), '<pre>';
// echo '<pre>', print_r(get_class ($api),1), '<pre>';

KBApiValidator::validateRequest($controller->request_method, $api->allowed_requests);


// responce
$responce = KBApiResponce::factory($api->format);
echo $responce->process($api, $controller, $manager);


ob_end_flush();
?>