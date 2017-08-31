<?php
ob_start();

require_once '../config.inc.php';
require_once '../config_more.inc.php';
require_once 'eleontev/Dir/MyDir.php';
require_once 'eleontev/URL/RequestData.php';
require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'eleontev/Util/FileUtil.php';
require_once 'eleontev/Util/GetMsg.php';
require_once 'CompareLang.php';

$langs = array(
    'en', 'ru', 'de', 'pl', 'jp', 'pt_BR', 'es', 'it', 'nl', 'fr', 'zh_CN'
);

$lang_original = 'en';

// get all available langs
$lang_compare = 'ru';

if(isset($_COOKIE['lang_'])) {
    if(!in_array($_COOKIE['lang_'], $_COOKIE['lang_'])) {
        exit('Wrong langauge to compare');
    }
    
    $lang_compare = $_COOKIE['lang_'];
}

$skip_files = array(
    'en/public/user_msg.ini'
);

$skip_dirs = array(
    'trouble', 'forum'
);


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);


$c = new CompareLang($lang_compare, $lang_original);
$c->setFiles($skip_files, $skip_dirs);


// write changes
if (!empty($rp->vars)) {

    if (isset($rp->config_lang_file)) {

        $sfile = $c->compared_lang . '/config_lang.php';
        $user_data = trim(stripslashes($rp->config_lang_file));
        $ret = FileUtil::write($sfile, $user_data);

    } elseif (isset($rp->sphinx_lang_file)) {

        $sfile = $c->compared_lang . '/sphinx.conf';
        $user_data = trim($rp->sphinx_lang_file);
        $ret = FileUtil::write($sfile, $user_data);

    } else {

        $txt_file = false;
        $user_data = array();
        $fname = basename($rp->vars['ini_filename']);

        if (in_array($rp->vars['ini_filename'], $c->multi_ini_files)) {
            $user_data = $rp->vars;

        } elseif(CompareLang::getFileExtension($fname) == 'txt') {
            $txt_file = true;
            $user_data = $rp->vars['content'];

        } else {
            foreach($rp->vars as $name => $value){
                $data = explode(':', $name);
                settype($data[0], 'string');
                if (isset($data[1])) {
                    settype($data[1], 'string');
                    $user_data[$data[0]][$data[1]] = $value;
                } else {
                    $user_data[$data[0]] = $value;
                }
            }

            $user_data = $c->stripVars($user_data);
        }


        $sfile = $c->compared_lang . '/' . $rp->vars['ini_filename'];

        if(file_exists($sfile) && !is_writable($sfile)) {
            echo 'Files is not writable: ' . $sfile;
            exit;
        }

        // create if new dir
        $dir = $c->compared_lang . '/' . dirname($rp->vars['ini_filename']);
        if (!is_dir($dir)) {
            $oldumask = umask(0);
            $r = mkdir($dir, 0777);
            umask($oldumask);

            if (!$r) {
                echo "Can't create directory " . $dir;
                exit;
            }
        }

        if($txt_file) {
            $ret  = $c->writeTxtFile($rp->vars['ini_filename'], $user_data);
        } else {
            $ret  = $c->writeIniFile($rp->vars['ini_filename'], $user_data);
        }
    }


    if(!$ret) {
        echo 'Unable write to file: ' . $sfile;
        exit;
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;


} elseif(isset($rq->q)) {
    echo $c->getSearchResult($rq->q);
    exit;
}



$mode = (isset($rq->mode)) ? $rq->mode : 'all';

switch ($mode) {

    case 'allfields':
        echo $c->getLanguageHTML(true);
        break;

    case 'config':
        echo $c->getConfigHTML();
        break;

    case 'sphinx':
        echo $c->getSphinxHTML();
        break;


    case 'translate':
        $file = $c->files_list[$rq->f];
        $original_file = APP_MSG_DIR . $c->original_lang . '/' . $file;
        $compared_file = APP_MSG_DIR . $c->compared_lang . '/' . $file;

        $compared_data = array();
        if (in_array($file, $c->multi_ini_files)) {
            $original_data = GetMsgHelper::parseMultiIni($original_file);
            if (file_exists($compared_file)) {
                $compared_data = GetMsgHelper::parseMultiIni($compared_file);
            }

        } elseif(CompareLang::getFileExtension($original_file) == 'txt') {
            $original_data['content'] = file_get_contents($original_file);
            if (file_exists($compared_file)) {
                $compared_data['content'] = file_get_contents($compared_file);
            }

        } else {

            $original_data = GetMsgHelper::parseIni($original_file);
            if (file_exists($compared_file)) {
                $compared_data = GetMsgHelper::parseIni($compared_file);
            }

            if ($file == 'public/user_msg.ini') {

                $original_file1 = parse_ini_file($original_lang . '/user_msg.ini', 1);
                $original_file2 = parse_ini_file($original_lang . '/public/user_msg.ini', 1);
                $original_data = array_merge($original_file1, $original_file2);

                $compared_file1 = parse_ini_file($lang_compare . '/user_msg.ini', 1);
                $compared_file2 = parse_ini_file($lang_compare . '/public/user_msg.ini', 1);
                $compared_data = array_merge($compared_file1, $compared_file2);
             }
        }

        $from = (isset($rq->from)) ? 1 : 0;
        echo $c->getHtmlIni($original_data, $compared_data, $file, $from);
        break;


    case 'public':
    case 'all':

        $c->getMissedFiles();
        $c->getMissedFields();
        $c->getEmptyFields();
        $c->getEqualValues();

        $public_only = ($mode == 'public');
        echo $c->getHTML($public_only);
        break;
}

ob_end_flush();
?>