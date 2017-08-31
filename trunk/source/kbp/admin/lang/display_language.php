<?php
require_once '../config.inc.php';
require_once '../config_more.inc.php';
require_once 'eleontev/Dir/MyDir.php'; 
require_once 'CompareLang.php';


$lang_compare = 'en';
$skip_files = array();
$skip_dirs = array('trouble'); // for version 4.5


$c = new CompareLang($lang_compare);
$c->setFiles($skip_files, $skip_dirs);
echo $c->getLanguageHTML();
?>