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

require_once APP_ADMIN_DIR . 'cron/inc/CronModel.php';
require_once APP_MODULE_DIR . 'setting/admin_setting/SettingValidator.php';
require_once APP_MODULE_DIR . 'setting/public_setting/SettingValidatorPublic.php';
require_once APP_MODULE_DIR . 'setting/sphinx_setting/SettingValidatorSphinx.php';


class KBPReportModel extends AppModel
{

    var $tables = array('table' => 'stuff_data', 'log_cron');
    var $setting = array();
        
    var $code = array('error', 'passed', 'disabled', 'skipped');
    
    var $data_key = 'setup_report';
        
    
    function runTest() {
        
        $a = array();
        
        $dirs = $this->getDirsToCheck();
        foreach ($dirs as $dir_key => $dir) {
            $a[$dir_key] = $this->checkDir($dir);
        }

        // xpdf
        $a['xpdf'] = $this->checkXpdf();
        
        // catdoc
        $a['catdoc'] = $this->checkCatdoc();
        
        // antiword
        $a['antiword'] = $this->checkAntiword();
        
        // zip extension
        $a['zip'] = $this->checkZipExtension();
        
        // spell tools
        $a['spell'] = $this->checkSpellSuggest();
        
        // htmldoc
        $a['htmldoc'] = $this->checkHtmldoc();
        
        // wkhtmltopdf
        $a['wkhtmltopdf'] = $this->checkWkHtmlToPdf();
        
        // sphinx
        $a['sphinx'] = $this->checkSphinx();
        
        // cron
        $a['cron']['code'] = 3;
        $a['cron']['msg'] = 'No one schedule task log found, 
        after setting scheduled task wait at least 5 min for first execution';
        $ts = $this->getFirstCronExecution();
        if($ts) {
            $a['cron'] = $this->checkCronSet($ts);
        }
        
        $data_string = serialize($a);
        $this->saveReport($data_string);
    }
    
    
    function getDirsToCheck() {
        $dirs = array(
            'file_dir' => $this->setting['file_dir'],
            'cache_dir' => APP_CACHE_DIR,
            'html_editor_upload_dir' => $this->setting['html_editor_upload_dir']);
                      
        return $dirs;
    }
    
    
    function checkDir($dir) {
        $writeable = is_writable($dir);

        $code = ($writeable) ? 1 : 0;
        $msg = ($writeable) ? '' : sprintf('Directory %s is not writeable or does not exist', $dir);
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkXpdf() {
        $path = $this->setting['file_extract_pdf'];
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateXPDF($path);
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'XPDF - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkCatdoc() {
        $path = $this->setting['file_extract_doc']; 
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateDoc($path, 'doc');  
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'Catdoc - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkAntiword() {
        $path = $this->setting['file_extract_doc2']; 
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateDoc($path, 'doc2');  
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'Antiword - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkZipExtension() {
        $is_ext = extension_loaded('zip');
        
        $code = ($is_ext) ? 1 : 0;
        $msg = ($is_ext) ? '' : 'Zip extension is not loaded';
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkSpellSuggest() {
        
        $setting = $this->setting['search_spell_suggest'];
        
        $msg = '';
        $code = 1;
        
        if($setting) {
            
            switch ($setting) {
                case 'enchant':
                	$ret = SettingValidatorPublic::validateEnchant($this->setting);
                    if(is_array($ret)) {
                        $code = 0;
                        $msg = 'Enchant - ' . $ret['code_message']; 
                    }
                    break;
                
                case 'pspell':
                	$ret = SettingValidatorPublic::validatePspell($this->setting);
                    if(is_array($ret)) {
                        $code = 0;
                        $msg = 'Pspell - ' . $ret['code_message']; 
                    }
                    break;
                    
                case 'bing':
                    $url = $this->setting['search_spell_bing_spell_check_url'];
                    $key = $this->setting['search_spell_bing_spell_check_key'];
                	$ret = SettingValidatorPublic::validateBing($url, $key);
                    
                    if (!$ret) {
                        $code = 0;
                        $msg = "Bing - Connection could not be established";
                    } 
                    break;
            }
            

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkHtmldoc() {
        $path = $this->setting['plugin_htmldoc_path'];  
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExport.php';
            require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExportHtmldoc.php';

            $export = KBExport::factory('pdf');
            $ret = $export->validate($path, 'license_key'); // second arg to check only key 

            if(is_array($ret)) {
                $code = 0;
                $msg = 'Htmldoc - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkWkHtmlToPdf() {
        $path = $this->setting['plugin_wkhtmltopdf_path'];  
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2.php';
            require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2_pdf.php';
            
            $export = new KBExport2_pdf;
            $ret = $export->validate($path, 'license_key');  // second arg to check only key 
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'WKHTMLTOPDF - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkSphinx() {
        $setting = $this->setting['sphinx_enabled'];
        
        $msg = '';
        $code = 1;
        
        if($setting == 1) {
            $ret = SettingValidatorSphinx::validateConnection($this->setting);
            
            if($ret !== true) {
                $code = 0;
                $msg = $ret['body']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    

    function getMagicValues($timestamp, $run_magic = array()) {
        
        $diff_ts = time() - $timestamp;
        $diff_minutes = $diff_ts/60;
        $magic_minutes = array(
            'freq'       => 5+11,             // 16 minutes - 3 executions
            'hourly'     => 60+70,            // 2 hour and 10 minutes - 2 executions
            'daily'      => 24*60+120,        // 1 day and 2 hour  
            'weekly'     => 7*24*60+24*60,    // 1 week and 1 day
            'monthly'    => 31*24*60+48*60    // 1 months (31 days) and 2 days
        );
        
        $cron = new CronModel();
        unset($cron->magic_to_number['_test_']);
        
        $magic = array();
        foreach($cron->magic_to_number as $k => $v) {
            if($run_magic && !in_array($k, $run_magic)) {
                continue;
            }
            
            if($diff_minutes <= $magic_minutes[$k]) {
                continue;
            }
            
            $magic[$k]['num'] = $v;
            $magic[$k]['word'] = $k;
            $magic[$k]['minutes'] = $magic_minutes[$k];
        }
        
        // echo '<pre>First Cron: ', print_r(date('Y-m-d', $timestamp), 1), '</pre>';
        // echo '<pre>Now: ', print_r(date('Y-m-d'), 1), '</pre>';
        // echo '<pre>diff_minutes: ', print_r($diff_minutes, 1), '</pre>';
        // echo '<pre>', print_r($magic_minutes, 1), '</pre>';
        // echo '<pre>', print_r($magic, 1), '</pre>';
        // exit;
                
        return $magic;
    }


    function checkCronSet($timestamp, $run_magic = array()) {

        $str = 'scheduled tasks are not configured properly or latest tasks execution skipped by some reason';
        $str2 = 'DB error determing if scheduled tasks configuired:';
        
        $code = $msg = $log = array();
        $magic = $this->getMagicValues($timestamp, $run_magic);
                        
        foreach($magic as $v) {
            $result = $this->isCronExecuted($v['minutes'], $v['num']);
            
            if($result === false) {
                $str = $m2 . $this->db->ErrorMsg();
                $code[$v['num']] = 0;
                $msg[$v['num']] = $str;
            } else {
                $code[$v['num']] = 1;
                if(!$result) {
                    $code[$v['num']] = 0;
                    $msg[$v['num']] = $v['word'];
                }
            }            
        }
        
        $ret = array();
        $ret['code'] = (in_array(0, $code)) ? 0 : 1;
        $ret['msg'] = (in_array(0, $code)) ? sprintf('%s %s', implode(', ', $msg), $str) : '';
        
        return $ret;
    }
    

    function isCronExecuted($minutes, $magic) {
        $sql = "SELECT date_finished FROM {$this->tbl->log_cron} 
        WHERE magic = %d AND date_finished > SUBDATE(NOW(), INTERVAL %d MINUTE)";
        $sql = sprintf($sql, $magic, $minutes);
        $result = $this->db->SelectLimit($sql, 1);
        
        if(!$result) {            
            return $this->db_error2($sql);
        }
        
        return $result->Fields('date_finished');
    }


    function getFirstCronExecution() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_started)) AS 'min' FROM {$this->tbl->log_cron}";
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }        

        return $result->Fields('min');
    }
    
    
    function getReport() {
        $sql = "SELECT * FROM {$this->tbl->table} WHERE data_key = '%s'";
        $sql = sprintf($sql, $this->data_key);
        $result = $this->db->Execute($sql);        
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        // ! ATTENTION FetchRow RETURNS FALSE IF NO RECORDS FOUND 
        return ($ret = $result->FetchRow()) ? $ret : array();
    }


    function isReport() {
        $sql = "SELECT id FROM {$this->tbl->table} WHERE data_key = '%s'";
        $sql = sprintf($sql, $this->data_key);
        $result = $this->db->Execute($sql);        
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result->Fields('id');
    }    
    
    
    function saveReport($data_string) {
        $is_report = $this->isReport();
        if($is_report === false) {
            return false;
        }        
        
        if($is_report) {
            $ret = $this->updateReport($data_string);
        } else {
            $ret = $this->addReport($data_string);
        }
        
        return $ret;
    }
    
    
    function updateReport($data_string) {
        $sql = "UPDATE {$this->tbl->table}
            SET data_string = '%s', date_posted = NOW()
            WHERE data_key = '%s'";
        $sql = sprintf($sql, $data_string, $this->data_key);
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
    
    function addReport($data_string) {
        $sql = "INSERT {$this->tbl->table} VALUES (NULL, '%s', NOW(), '%s')";
        $sql = sprintf($sql, $this->data_key, $data_string);
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
}
?>