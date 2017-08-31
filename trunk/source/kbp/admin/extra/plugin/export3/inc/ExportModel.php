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

require_once 'ExportColumnHelper.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
// require_once APP_MODULE_DIR . 'knowledgebase/category/inc/KBCategoryModel.php'; 


class ExportModel extends AppModel
{

    var $tables = array('table' => 'export', 'export_data', 'user', 'category' => 'kb_category', 'rating');
                       
    var $content_types = array('application/zip' => 'zip');
                           
    var $export_types = array(6 => 'excel', 7 => 'csv', 8 => 'xml');
    
    
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
            'user_id' => $export_option['user_id']);
        
     
        $view2 = new KBClientView();
        $view2->addMsg('common_msg.ini');
        $view2->addMsg('common_msg.ini', 'knowledgebase');
        
        $client_manager = &KBClientLoader::getManager($setting, $controller2, 'article', $user_option);
        if (!$user_option['user_id']) {
            $client_manager->is_registered = false;
            $client_manager->setCategories();    
        }
        
        $manager2 = new KBEntryModel($user_option, 'read');
        
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'http_host' => $_SERVER['HTTP_HOST'],
            'category_id' => $export_option['category_id'],
            'columns' => $export_option['columns'],
            'include_images' => @$export_option['include_images']);
        
        $export = new KBExport;
            
        $export->setComponents($manager2, $client_manager, $controller2, $view2);
        $export->setConfig($config);
        
        
        if ($export_option['category_id']) {
            $categories_ids = array($export_option['category_id']);
            $categories = $manager2->getCategoryRecordsUser();
            
            $children = $manager2->cat_manager->getChildCategories($categories, $export_option['category_id']);
            $categories_ids = array_merge($categories_ids, $children);
            
            if (!empty($export_option['published_only'])) {
                foreach (array_keys($categories_ids) as $k) {
                    $category_id = $categories_ids[$k];
                    if ($categories[$category_id]['active'] == 0) {
                        unset($categories_ids[$k]);
                    }
                }
                
                if (empty($categories_ids)) {
                    return false;
                }
            }
            

            
            $manager2->setSqlParams(sprintf('AND e_to_cat.category_id IN (%s)', implode(',', $categories_ids)));
        }
        
        if (!empty($export_option['published_only'])) {
            $publish_status_ids = $manager2->getEntryStatusPublished('article_status');
            $manager2->setSqlParams(sprintf('AND e.active IN (%s)', implode(',', $publish_status_ids)));
        }
        
        $additional_columns = 'a.first_name AS author_first_name,
                               a.last_name AS author_last_name,
                               u.first_name AS updater_first_name,
                               u.last_name AS updater_last_name';
        $manager2->setSqlParamsSelect($additional_columns);
        $manager2->setSqlParamsJoin("LEFT JOIN {$this->tbl->user} a ON e.author_id = a.id");
        $manager2->setSqlParamsJoin("LEFT JOIN {$this->tbl->user} u ON e.updater_id = u.id");
        
        $sql = $manager2->getRecordsSqlCategory();
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $data = $export->getExportData($result);
        
        if(!$data) {
            return false;
        }
        
        foreach ($this->export_types as $type_id => $type) {
    
            if (isset($export_option['do'][$type])) {
                if ($type == 'csv') { // overwriting for csv
                    $csv_params = array('fields_terminated', 'optionally_enclosed', 'lines_terminated', 'header_row');
                    foreach ($csv_params as $param) {
                        $config[$param] = $export_option['csv'][$param];    
                    }                    
                }
                
                $export->setConfig($config);
                $zip = ($type == 'xml') ? $export->getXmlFile($data, $obj->get('title')) : $export->getCsvFile($data, $obj->get('title'));
                
                $export_result = $export->getLog();
                
                $this->saveExportData($obj->get('id'), $type_id, $zip, $export_result, $export->archive_type); 
            }
        }
        
        return true;
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
        $filename = $data['filename'];
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