<?php
function setupTest($run_magic, $mail_cron_test = true) {
    
    require_once APP_MODULE_DIR . 'home/kbpreport/inc/KBPReportModel.php';
    $exitcode = 1;
    $exitcode_test = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
                
    // cron
    $model = new KBPReportModel;
    $model->error_die = false;
    
    // settings
    $sm = new SettingModel();
    $model->setting = $sm->getSettings('1, 2, 134, 140, 141');
    
    $a = array();    
    $setup_msg = 'Setup Test: ';
    
    $dirs = $model->getDirsToCheck();
    foreach ($dirs as $dir_key => $dir) {
        $a[$dir_key] = $model->checkDir($dir);
        if (!$a[$dir_key]) {
            $exitcode_test = 0; 
            $cron->logCritical($setup_msg . $a[$dir_key]['msg']);
        }
    }
                                   
    // xpdf
    $a['xpdf'] = $model->checkXpdf();
    if ($a['xpdf']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['xpdf']['msg']);
    }                                       
    
    // catdoc
    $a['catdoc'] = $model->checkCatdoc();
    if ($a['catdoc']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['catdoc']['msg']);
    }
    
    // antiword
    $a['antiword'] = $model->checkAntiword();
    if ($a['antiword']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['antiword']['msg']);
    }
    
    // zip extension
    $a['zip'] = $model->checkZipExtension();
    if ($a['zip']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logNotify($setup_msg . $a['zip']['msg']);
    }
    
    // spell tools
    $a['spell'] = $model->checkSpellSuggest();
    if ($a['spell']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logNotify($setup_msg . $a['spell']['msg']);
    }
                                                  
    // htmldoc
    $a['htmldoc'] = $model->checkHtmldoc();
    if ($a['htmldoc']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['htmldoc']['msg']);
    }
    
    // wkhtmltopdf
    $a['wkhtmltopdf'] = $model->checkWkHtmlToPdf();
    if ($a['wkhtmltopdf']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['wkhtmltopdf']['msg']);
    }
    
    // sphinx
    $a['sphinx'] = $model->checkSphinx();
    if ($a['sphinx']['code'] == 0) {
        $exitcode_test = 0;
        $cron->logCritical($setup_msg . $a['sphinx']['msg']);
    }

    // cron
    $a['cron']['code'] = 3;
    $a['cron']['msg'] = '';
    
    $first_cron_ts = $model->getFirstCronExecution();
    if($first_cron_ts === false) {
        $exitcode = 0;
    }
    
    if($first_cron_ts) {
        // $run_magic = array('freq', 'hourly', 'weekly', 'monthly');
        $a['cron'] = $model->checkCronSet($first_cron_ts, $run_magic);
        if ($a['cron']['code'] == 0) {
            if($mail_cron_test) {
                $exitcode_test = 0;
                $cron->logCritical($setup_msg . $a['cron']['msg']);
            }
        }
    }

    // if error in some test
    if ($exitcode_test == 0) {
        $link = APP_ADMIN_PATH . 'index.php?module=home&page=kbpreport';
        $cron->logNotify($setup_msg . 'for details please see ' . $link); 
    }
    
    $data_string = serialize($a);
    
    $ret = $model->saveReport($data_string);
    if($ret === false) {
        $exitcode = 0;
    }

    return $exitcode;
}



function cleanCacheDirectory() {
    
    require_once 'eleontev/Dir/MyDir.php';
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $d = new MyDir;
    $d->one_level = true;
    $d->full_path = true;
    $d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS');
    
    $dirs = &$d->getDirs(APP_CACHE_DIR);
    
    $pattern = "#^export_\w{32}$#";
    $remove_dirs = preg_grep($pattern, array_keys($dirs));
    
    // echo 'dirs: ', print_r($dirs, 1);
    // echo 'remove_dirs: ', print_r($remove_dirs, 1);
    // exit;
    
    $deleted_files = 0;
    $deleted_dirs = 0;
    
    if($remove_dirs) {
        foreach($remove_dirs as $v) {
            $ret = $d->removeFilesDirs($dirs[$v]);
            if($ret) {
                $deleted_dirs ++;                
            }
        }
    }
    
    $cron->logNotify('%d file(s) removed.', $deleted_files);
    $cron->logNotify('%d directory(s) removed.', $deleted_dirs);
    
    return $exitcode;
}


function cleanDraftDirectory() {
    
    require_once 'eleontev/Dir/MyDir.php';
    require_once 'inc/FileDirectoryModel.php';
    require_once APP_MODULE_DIR . 'file/entry/inc/FileEntry.php';
    
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new FileDirectoryModel;
    
    $file_dir = SettingModel::getQuickCron(1, 'file_dir');
    if ($file_dir === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $draft_dir = $file_dir . 'draft/';
    
    $d = new MyDir;
    $d->one_level = true;
    $d->full_path = true;
    $d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS');
    
    $files = $d->getFilesDirs($draft_dir);
    $drafts = $model->getDraftFiles($draft_dir, false);
    
    $dead_drafts = array_diff($files, array_keys($drafts));
    
    $deleted_files = 0;
    
    foreach($dead_drafts as $v) {
        $ret = $d->removeFilesDirs($v);
        if($ret) {
            $deleted_files ++;          
        }
    }
    
    $cron->logNotify('%d file(s) removed.', $deleted_files);
    
    return $exitcode;
}


function setupValidate() {
    KBValidateLicense::sendRequest('license_check');
    return 1;
}

?>