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

require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
// require_once APP_MODULE_DIR . 'knowledgebase/category/inc/KBCategoryModel.php';


class ExportModel extends AppModel
{

    var $tables = array('table' => 'export', 'export_data', 'user' => 'user', 'stuff_entry');

    var $file_extensions = array(1 => 'pdf', 2 => 'zip', 3 => 'zip');
    var $content_types = array('application/pdf' => 'pdf', 'application/zip' => 'zip');

    var $export_types = array(1 => 'pdf', 'html', 'htmlsep');


    function __construct() {
        parent::__construct();
        $emanager = new KBEntryModel();

        $this->emanager = $emanager;
        $this->cat_manager =& $emanager->cat_manager;
        $this->role_manager =& $emanager->role_manager;
    }


    function getUserByIds($ids) {
        $sql = "SELECT id, username FROM {$this->tbl->user} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getCategoryRange() {
        return $this->cat_manager->getSelectRangeFolow();
    }


    function getGenerateForUserSelectRange($msg) {
        $range = array(
            $msg['admin_user_msg'],
            $msg['not_logged_user_msg'],
            $msg['logged_user_msg']
            );

        return $range;
    }


    function getRoleRange() {
        return $this->role_manager->getSelectRangeFolow();
    }


    function generate($export_id, $export_option, $setting) {

        require_once APP_CLIENT_DIR . 'client/inc/KBClientView.php';
        require_once APP_CLIENT_DIR . 'client/inc/KBClientLoader.php';
        require_once APP_CLIENT_DIR . 'client/inc/KBClientController.php';
        require_once APP_CLIENT_DIR . 'client/inc/DocumentParser.php';

        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');

        $setting['auth_check_ip'] = $conf['auth_check_ip'];
        $setting['auth_captcha'] = false;
        $setting['view_style'] = 'default';
        $setting['private_policy'] = 1;

        $controller2 = new KBClientController();
        $controller2->setDirVars($setting);
        $controller2->setModRewrite(false);

        $reg->setEntry('controller', $controller2);

        $user_option = array(
            'role_id' => $export_option['role_ids'],
            'priv_id'=> $export_option['priv_id'],
            'user_id' => $export_option['user_id']
            );

        $view2 = new KBClientView();

        $manager2 = &KBClientLoader::getManager($setting, $controller2, 'article', $user_option);
        /*if (!$user_option['user_id']) {
            $manager2->is_registered = false;
            $manager2->setCategories();
        }*/

        $cats = KBExport::getData($manager2, $export_option['category_id']);

        if(!$cats) {
            return 'no_cats';
        }

        $config = array(
            'document_root'    => $_SERVER['DOCUMENT_ROOT'],
            'tool_path'        => $setting['plugin_htmldoc_path'],
            'http_host'        => $_SERVER['HTTP_HOST'],
            'ssl'              => ($conf['ssl_client'])
            );

        // document options
        $settings = array(
            'book'        => true,
            'fontsize'    => $setting['htmldoc_fontsize'],
            'bodyfont'    => $setting['htmldoc_bodyfont']
            // 'fontspacing' => 1.2
            );

        // title page
        if ($export_option['title'] || $export_option['titleimage']) {
            $do_title = true;
            $doc_title = $export_option['title'];
            $doc_titleimage = false;
            if($export_option['titleimage']) {
                $doc_titleimage = $this->getStuffImageById($export_option['titleimage']);
            }

        } else {
            $do_title = false;
            $settings['no-title'] = true;
        }

        $export_data = $this->getExportData($export_id);

        foreach ($this->export_types as $number => $type) {

            if (isset($export_option['do'][$type])) {

                $config['temp_dir'] = KBExportHtmldoc::getTempDir(APP_CACHE_DIR, $type);

                $export = KBExport::factory($type);
                $export->setConfig($config);
                $export->createTempDir();

                // create directory for htmldoc output
                if ($type == 'html' || $type == 'htmlsep') {
                    $export->tmp_subdir = $export->config['temp_dir'] . 'output/';
                    $export->createDir($export->tmp_subdir);
                    $export->createDir($export->config['temp_dir'] . 'images/');

                    $export->setSetting('path', $export->config['temp_dir'] . 'images');
                    $export->setSetting('d', $export->tmp_subdir);
                }

                $ex_settings = $settings;

                // title file
                if ($do_title) {
                    $export->setTitle($doc_title, $doc_titleimage);
                }

                // header
                if ($type == 'pdf') {

                    if ($export_option['pdf']['orientation']) {
                        $orientation = $export_option['pdf']['orientation'];
                        $ex_settings[$orientation] = true;
                    }

                    $header = '...';

                    if ($export_option['pdf']['logoimage']) {
                        // $img = APP_CLIENT_PATH . 'file.php?type=image&id=' . $export_option['pdf']['logoimage'];
                        $fmore = array('id'=>$export_option['pdf']['logoimage']);
                        $img = AppController::getAjaxLinkToFile('image', $fmore);
                        $ex_settings['logoimage'] = $img;
                        $header[0] = 'l';
                    }

                    if (!empty($export_option['title']) && isset($export_option['header'])) {
                        $ht_index = (isset($ex_settings['logoimage'])) ? 2 : 0;
                        $header[$ht_index] = 't';
                    }

                    $ex_settings['header'] = $header;
                    $ex_settings['footer'] = 'h.1';

                    if ($export_option['pdf']['password']) {
                        $ex_settings['password'] = $export_option['pdf']['password'];
                    }

                    if (isset($export_option['pdf']['duplex'])) {
                        $ex_settings['duplex'] = true;
                    }
                }

                $export->setSettings($ex_settings);
                $export->setArchiveType($this->file_extensions[$number]);
                $data = $export->export($cats, $manager2, $controller2, $view2);
                $export->removeTempDir();

                $log = $export->getLog();
                $data[2] .= "\n" . $log;

                $action = (isset($export_data[$number])) ? 'update' : 'insert';
                $this->saveExportData($export_id, $number, $data, $export->archive_type, $action);
            }
        }
    }


    function saveExportData($id, $type, $data, $archive, $action) {

        $output = addslashes($data[0]);
        $result = addslashes($data[2]);

        $content_type = array_search($archive, $this->content_types);

        if ($action == 'update') {
            $sql = "UPDATE {$this->tbl->export_data}
                SET date_created = NOW(), export_data = '%s', export_result = '%s', content_type = '%s'
                WHERE export_id = %d AND export_type = %d";
            $sql = sprintf($sql, $output, $result, $content_type, $id, $type);
        
        } else {
            
            $sql = "INSERT {$this->tbl->export_data}
                SET export_id = %d, export_type = '%s', date_created = NOW(), 
                    export_data = '%s', export_result = '%s', content_type = '%s'";
            $sql = sprintf($sql, $id, $type, $output, $result, $content_type);
        }

        $result = &$this->db->Execute($sql) or die(db_error($sql));
    }


    function upload() {

        require_once 'eleontev/Dir/Uploader.php';

        $upload = new Uploader;
        $upload->store_in_db = false;

        $upload->setAllowedExtension('jpg', 'png', 'gif');
        $upload->setUploadedDir(APP_CACHE_DIR);

        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        } else{
            $f['filename'] = APP_CACHE_DIR . $f['good']['image']['name'];
        }

        return $f;
    }


    function getExportData($id) {
        $sql = "SELECT export_type, date_created, export_result FROM {$this->tbl->export_data}
            WHERE export_id = '{$id}'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getFileData($id, $type) {
        $sql = "SELECT export_data, date_created, content_type FROM {$this->tbl->export_data}
            WHERE export_id = '{$id}'
            AND export_type = '{$type}'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    function getStuffImageById($id) {
        $sql = "SELECT id, filename, title
        FROM {$this->tbl->stuff_entry}
        WHERE id = '$id'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    function getExportTypesById($id) {
        $sql = "SELECT export_type as id
        FROM {$this->tbl->export_data}
        WHERE export_id = '$id'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }


    function getLastGenerated($ids) {
        $sql = "SELECT export_id, MAX(date_created) as date_generated
        FROM {$this->tbl->export_data}
        WHERE export_id IN ({$ids})
        GROUP BY export_id";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function sendFileDownload($data, $type) {
        $params['data'] = $data['export_data'];
        $params['gzip'] = false;
        $params['contenttype'] = $data['content_type'];

        // $date_created = str_replace(' ', '_', $data['date_created']);
        // $filename = 'kbp_export_%s.%s';
        // $filename = sprintf($filename, $date_created, $this->content_types[$data['content_type']]);
        $filename = sprintf('%s.%s', $data['filename'], $this->content_types[$data['content_type']]);

        return WebUtil::sendFile($params, $filename);
    }


    function delete($id) {
        parent::delete($id);
        $this->deleteExportData($id);
    }


    function deleteExportData($export_id) {
        $sql = "DELETE FROM {$this->tbl->export_data} WHERE export_id = '{$export_id}'";
        $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>