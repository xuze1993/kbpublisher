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

//if (isset($_GET['conf'])) { return; }
//elseif (!isset($conf))    { return; }

$use_ob_gzhandler = $conf['use_ob_gzhandler'];
if($use_ob_gzhandler) {
    $z = strtolower(ini_get('zlib.output_compression'));
    if($z && $z != 'off') {
        $use_ob_gzhandler = false;
    }
    
    // if(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    //     $use_ob_gzhandler = false;
    // }
}

// file action should be always for download file
// ob_gzhandler will be disabled in this case 
if($use_ob_gzhandler) {
    if(@$_GET['action'] == 'file') {
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

require_once 'core/base/Controller.php';
require_once 'core/app/AppController.php';

require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';

require_once 'core/base/BaseModel.php';
require_once 'core/app/AppModel.php';

require_once 'core/base/SphinxModel.php';
require_once 'core/app/AppSphinxModel.php';

require_once 'core/base/BaseView.php';
require_once 'core/app/AppView.php';

require_once 'core/app/AppAction.php';

require_once 'adodb/adodb.inc.php';
require_once 'eleontev/PageByPage.php';
require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'eleontev/HTML/FormSelect.php';
require_once 'eleontev/URL/RequestData.php';
require_once 'eleontev/SQL/SortOrder.php';
require_once 'eleontev/Assorted.inc.php';
require_once 'eleontev/Auth/AuthPriv.php';
require_once 'eleontev/Auth/AuthRemote.php';
require_once 'eleontev/Auth/AuthProvider.php';
require_once 'eleontev/Navigation.php';

require_once 'core/app/AppMsg.php';
require_once 'core/app/AppNavigation.php';
require_once 'core/app/PageRenderer.php';

require_once 'core/common/PrivateEntry.php';


// if using https
if($conf['ssl_admin'] && empty($conf['ssl_skip_redirect'])) { 
    $port = ($conf['ssl_admin'] == 1) ? 443 : $conf['ssl_admin'];
    if($_SERVER['SERVER_PORT'] != $port) {
        header("Location: " . APP_ADMIN_PATH);
        exit();            
    }
}

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingModel.php';
require_once APP_MODULE_DIR . 'tool/list/inc/ListValueModel.php';


// db
$reg =& Registry::instance();
$reg->setEntry('tbl_pref', $conf['tbl_pref']);
$reg->setEntry('conf', $conf);
$reg->setEntry('extra', $conf['extra']);

$db = &DBUtil::connect($conf);
$reg->setEntry('db', $db);

// setting
$setting = SettingModel::getQuick(1);

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

require_once 'core/app/AppAjax.php';
require_once APP_ADMIN_DIR . 'common_share.inc.php';
?>