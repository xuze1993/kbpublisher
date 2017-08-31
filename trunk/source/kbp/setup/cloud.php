<?php

$dir = str_replace('\\', '/', dirname(__FILE__)) . '/';
$app_dir = str_replace('setup', 'admin', $dir);
define('APP_ADMIN_DIR', $app_dir);

require_once $app_dir . 'config.inc.php';

chdir($dir);

// errors
set_error_handler('errorHandler');
register_shutdown_function('fatalErrorShutdownHandler');

function errorHandler($code, $message, $file, $line) {
    if (error_reporting() && $code != 2048) { // suppressed and strict
        echo sprintf('Code: %s, Message: %s, File: %s, Line: %s', $code, $message, $file, $line);
        exit(69);
    }
}

function fatalErrorShutdownHandler() {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) { // fatal error
        errorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
}


$win = (substr(PHP_OS, 0, 3) == "WIN");
$include_separator = ($win) ? ';' : ':';
$include_path = array();
$include_path[] = $app_dir . 'lib';
$include_path[] = $app_dir . 'lib/Pear';

ini_set('include_path', implode($include_separator, $include_path));


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

$controller = new SetupController();
$controller->working_dir = $dir;
//$controller->home_path = $home_path;
$controller->mod_rewrite = false;

require_once $dir . 'inc/action/SetupAction_upgrade.php';
require_once $dir . 'inc/action/SetupAction_config.php';

$reg =& Registry::instance();
$conf['debug_db_error'] = 'cloud';

$reg->setEntry('conf', $conf);
$reg->setEntry('lang', $lang);
$reg->setEntry('dir', $dir);
$reg->setEntry('controller', $controller);


$test = (!empty($_GET['test']));

if($test) {
    $values = $_GET;
    // echo '<pre>$values:', print_r($values, 1), '</pre>';
    // echo '<pre>', print_r("===========", 1), '</pre>';
    
} else {
    //$values = json_decode($argv[1], true);
    $values = file_get_contents('php://stdin');
    $values = json_decode($values, true);
}


if (!$values) {
    exit(64);
}

$values['setup_type'] = 'upgrade';

// we have one class, version_map for version above 45
// list here for convinience 
$version_map = array(
    // to 5.5.1.4
    // '45*_to_5514'  => '45_to_5514',
    // '50*_to_5514'  => '50_to_5514',    
    // '551*_to_5514' => '5513_to_5514', // all 551_ to 5513_to_5514
    
    '45*_to_5515'  => '45_to_5514',
    '50*_to_5515'  => '50_to_5514',
    
    '5514_to_5515' => 'skip',     // no sql updates
    '551*_to_5515' => '5513_to_5514', // all 551_ to 5513_to_5514 exept 5514,
    
    '45*_to_602'  => '45_to_602',
    '50*_to_602'  => '50_to_602',
    '551*_to_602' => '551_to_602', // all 551 (including 551) to 551_to_60, all instaces no less than 551
    '60_to_602'   => '60_to_602',  
    '601_to_602'  => 'skip'        // no sql updates
);


if (isset($version_map[$values['setup_upgrade']])) {
    $values['setup_upgrade'] = $version_map[$values['setup_upgrade']];

} else {
    
    foreach($version_map as $in => $out) {
        $search = str_replace('*', '(\d*)', $in);    
        $search = "#^{$search}$#";
        preg_match($search, $values['setup_upgrade'], $match);
        if(!empty($match[0])) {
            $version_from = $match[1];
            $setup_upgrade = str_replace('[num]', $version_from, $out);
            $values['setup_upgrade'] = $setup_upgrade;
            
            // echo '<pre>in: ', print_r($in, 1), '</pre>';
            // echo '<pre>search: ', print_r($search, 1), '</pre>';
            // echo '<pre>match: ', print_r($match, 1), '</pre>';
            // echo '<pre>setup_upgrade: ', print_r($setup_upgrade, 1), '</pre>';
            
            break;
        }
    }
}


$class = 'SetupModelUpgrade_' . $values['setup_upgrade'];
if (!class_exists($class)) {
    if($test) {
        echo 'class: ', $class, "<br/>\n";
        echo 'Exit with code 65, upgrade class not found!';
    }
    
    exit(65);
}

if($test) {
    // http://localhost/kbp/kbp_dev/setup/cloud.php?test=1&setup_upgrade=60_to_601
    echo 'setup_upgrade: ', $values['setup_upgrade'], "<br/>\n";
    echo 'class: ', $class, "<br/>\n";
    exit;
}

$manager = new SetupModel;
$ret = $manager->connect($values);
if($ret !== true) {
    echo $ret;
    exit(66);
}

$values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
$manager->setTables($values['tbl_pref']);
$ret = $manager->checkPrefixOnUpgrade();
if($ret !== true) {
    exit(67);
}


$action = new SetupAction_upgrade;
$ret = $action->process($values, $manager, $values['setup_upgrade']);

if (is_array($ret)) {
    echo $ret['formatted'][0]['msg'];
    exit(68);
}

if (@$values['config']) {
    $action = new SetupAction_config;
    $action->setVars($controller, $manager);
    $config = $action->execute($controller, $manager, $values);
    echo $config;
}

exit(0);

?>