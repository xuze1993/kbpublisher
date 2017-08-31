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

    var $tables = array('table' => 'export', 'export_data', 'user', 'category' => 'kb_category');

    var $file_extensions = array(4 => 'zip', 5 => 'zip', 6 => 'pdf');                        
    var $content_types = array('application/zip' => 'zip', 'application/pdf' => 'pdf');
                           
    var $export_types = array(4 => 'html', 5 => 'htmlsep', 6 => 'pdf');
    
    
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
    
    
    function generate($obj, $export_option, $setting) {

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
        
        $cats = KBExport2::getData($manager2, $export_option['category_id']);

        if(!$cats) {
            return 'no_cats';
        }
        
        // set descriptions
        $descs = $this->getDescriptions($cats);
        foreach ($descs as $cat_id => $desc) {
            $manager2->categories[$cat_id]['description'] = $desc;
        }        

        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'http_host' => $_SERVER['HTTP_HOST'],
            'tool_path' => $setting['plugin_wkhtmltopdf_path'],
            'category_id' => $export_option['category_id'],
            'title' => $obj->get('title'),
            'settings' => array(
                'fontsize' => $setting['htmldoc_fontsize'],
                'font' => $setting['htmldoc_bodyfont'],
				'dpi' => $setting['plugin_wkhtmltopdf_dpi'],
				'margin_top' => $setting['plugin_wkhtmltopdf_margin_top'],
				'margin_bottom' => $setting['plugin_wkhtmltopdf_margin_bottom']
            )
        );
        
        foreach ($this->export_types as $number => $type) {
    
            if (isset($export_option['do'][$type])) {
    
                $export = KBExport2::factory($type);
                $export->setComponents($manager2, $controller2, $view2);

                $config['temp_dir'] = KBExport2::getTempDir(APP_CACHE_DIR, $type);
                $config['print_entry_info'] = isset($export_option['print_info']);
                
                if (!empty($export_option[$type])) {  
                    $config['settings'] = $export_option[$type] + $config['settings'];
                    //$config['settings'] += $export_option[$type];
                }
                
                $export->setConfig($config);             
                $export->createTempDirs();

                $export->setArchiveType($this->file_extensions[$number]);
                
                $data = $export->export($cats);
                $export_result = $export->getLog();
                
                $export->removeTempDir();
                
                $this->saveExportData($obj->get('id'), $number, $data, $export_result, $export->archive_type); 
            }
        }
    }
    
    
    function getDescriptions($cats) {
        $ids = implode(',', array_keys($cats));
        $sql = "SELECT id, description FROM {$this->tbl->category} WHERE id IN({$ids})";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function _updateExportData($id, $type, $data, $result_msg, $content_type) {
        $sql = "UPDATE {$this->tbl->export_data}
                SET date_created = NOW(), export_data = '%s', export_result = '%s', content_type = '%s'
                WHERE export_id = %d AND export_type = %d";
        $sql = sprintf($sql, $data, $result_msg, $content_type, $id, $type);
        $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Affected_Rows();
    }
    
    
    function _addExportData($id, $type, $data, $result_msg, $content_type) {
        $sql = "INSERT {$this->tbl->export_data} VALUES (%d, %d, NOW(), '%s', '%s', '%s')";
        $sql = sprintf($sql, $id, $type, $data, $result_msg, $content_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function saveExportData($id, $type, $data, $result_msg, $archive_type) {
        $content_type = array_search($archive_type, $this->content_types);
        $data = addslashes($data);
        $result_msg = addslashes($result_msg);
        
        $result = $this->_updateExportData($id, $type, $data, $result_msg, $content_type);
        if(!$result) {
            $this->_addExportData($id, $type, $data, $result_msg, $content_type);
        }
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