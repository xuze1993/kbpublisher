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

require_once 'core/app/DataToValueModel.php';
require_once 'core/common/CommonEntryModel.php';
require_once 'core/common/CommonCustomFieldModel.php';
require_once APP_MODULE_DIR . 'file/category/inc/FileCategoryModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
require_once APP_MODULE_DIR . 'tool/tag/inc/TagModel.php';


class FileEntryModel extends CommonEntryModel
{
    var $tbl_pref_custom = 'file_';
    var $tables = array('table'=>'entry', 'entry', 'category', 'entry_to_category', 
                        'data', 'text', 'custom_data');
    
    var $custom_tables =  array('kb_attachment_to_entry',
                                'kb_entry',
                                'trouble_attachment_to_step',
                                'role'=>'user_role', 
                                'list_value', 
                                'data_to_value'=>'data_to_user_value',
                                'data_to_value_string'=>'data_to_user_value_string',
                                'user',
                                'entry_schedule',
                                'lock' => 'entry_lock',
                                'draft' => 'entry_draft',
                                'draft_workflow' => 'entry_draft_workflow',
                                'workflow_history' => 'entry_draft_workflow_history',
                                'entry_hits',
                                'user_subscription',
                                'trigger',
                                'entry_task'
                                );        
    
    var $id_field = 'id';
    var $setting = array();
    
    var $use_entry_private = false;
    var $role_read_rule = 'file_entry_to_role_read';
    var $role_read_id = 102;    
    var $role_write_rule = 'file_entry_to_role_write';
    var $role_write_id = 106;
    
    var $select_type = 'index';
    var $show_bulk_sort = true;
    var $update_diff = 60; // seconds, to display updated if difference more than 
    
    var $entry_type = 2; 
    var $draft_type = 8; // file's draft
    
    var $num_files_upload = 3; // number of files allowed to upload once
    
    
    function __construct($user = array(), $apply_private = 'write') {
        parent::__construct();
        $this->dv_manager = new DataToValueModel();
        $this->cat_manager = new FileCategoryModel($user);
        $this->cf_manager = new CommonCustomFieldModel($this);
        $this->tag_manager = new TagModel;
        $this->tag_manager->entry_type = $this->entry_type;
        
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();
        
        $this->role_manager = &$this->cat_manager->role_manager;
        $this->setEntryRolesSql($apply_private);
        $this->setCategoriesNotInUserRole($apply_private);
    }    
    
    
    function setFileSetting(&$values) {
        
        $this->setting = &$values;
        
        $this->setting['extract_save_dir'] = APP_CACHE_DIR;
        $this->setting['file_param_pdf'] = '';
        $this->setting['file_param_doc'] = '';
        
        // it will set tool (path where some toll installed) for example /urr/local/bin/
        // later it will be used in cmd /urr/local/bin/xpdf.exe
        // it should be set fol file extension if required 
        $this->setting['extract_tool']['pdf'] = array($values['file_extract_pdf'], $values['file_param_pdf']);
        $this->setting['extract_tool']['doc'] = array($values['file_extract_doc'], $values['file_param_doc']);


        if(strtolower($values['file_extract_doc']) == 'off') {
            if(strtolower($values['file_extract_doc2']) != 'off') {
                $this->setting['extract_tool']['doc'][0] = $values['file_extract_doc2'];
                $this->setting['extract_tool']['doc']['load_extension'] = 'doc2';
            }
        }    
        
        
        $this->setting['file_denied_extensions'] = ($this->setting['file_denied_extensions']) 
                                                ? explode(',', $this->setting['file_denied_extensions'])
                                                : array();
                                                
        $this->setting['file_allowed_extensions'] = ($this->setting['file_allowed_extensions']) 
                                                ? explode(',', $this->setting['file_allowed_extensions'])
                                                : array();
                                                
        return $this->setting;
    }
    
    
    function getRecordsSqlCategory() {
    
        $sql = "
        SELECT 
            e.*,
            e_to_cat.sort_order AS real_sort_order,
            cat.id AS category_id,
            cat.private AS category_private,
            cat.name AS category_title,
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            UNIX_TIMESTAMP(e.date_updated) AS tsu,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
    
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
    
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    // for page by page
    function getCountRecordsSqlCategory() {
        $s = ($this->entry_role_sql_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT {$s} AS 'num'
        FROM 
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE e.id = e_to_cat.entry_id
        AND cat.id = e_to_cat.category_id
        AND {$this->entry_role_sql_where}
        AND {$this->sql_params}";
    
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function getRecordsSqlIndex() {
    
        $sql = "
        SELECT 
            e.*,
            e.sort_order AS real_sort_order, 
            cat.id AS category_id,
            cat.private AS category_private,
            cat.name AS category_title,
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            UNIX_TIMESTAMP(e.date_updated) AS tsu,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->entry} e {$this->entry_sql_force_index},
            {$this->tbl->category} cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
    
        WHERE 1
            AND e.category_id = cat.id
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
    
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    // for page by page
    function getCountRecordsSqlIndex() {
        $s = ($this->entry_role_sql_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT {$s} AS 'num'
        FROM 
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE e.category_id = cat.id
        AND {$this->entry_role_sql_where}
        AND {$this->sql_params}";
    
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }    


    function getRecordsSql() {    
        return ($this->select_type == 'index') ? $this->getRecordsSqlIndex() : $this->getRecordsSqlCategory();
    }


    function getCountRecordsSql() {
        return ($this->select_type == 'index') ? $this->getCountRecordsSqlIndex() : $this->getCountRecordsSqlCategory();
    }    
    
    
    function getSortRecords($category_id, $limit = false, $offset = 0) { 
        $sql = "SELECT e.id, e.filename AS 't', e_to_cat.sort_order AS 's'
        FROM 
            {$this->tbl->entry} e,
            {$this->tbl->entry_to_category} e_to_cat
        WHERE e_to_cat.category_id = '%d'
        AND e_to_cat.entry_id = e.id
        ORDER BY e_to_cat.sort_order";
        
        $sql = sprintf($sql, $category_id);
        
        if ($limit) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));           
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));        
        }
        
        return $result->GetAssoc();    
    }    
    
    
    function getFileText($file_id) {
        $sql = "SELECT filetext FROM {$this->tbl->table} WHERE id = %d";
        $sql = sprintf($sql, $file_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('filetext');        
    }
    
    
    // ACTIONS // ---------------------
    
    function updateFileText($filetext, $file_id) {
        $sql = "UPDATE {$this->tbl->table} SET 
        filetext = '{$filetext}'
        WHERE id = '{$file_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function countDownload($file_id) {
        $sql = "UPDATE {$this->tbl->table} SET 
        downloads = downloads+1, 
        date_updated = date_updated
        WHERE id = '{$file_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // PRIV // ------------------------------    
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {
        
        $priv->setCustomAction('text', 'update');
        $priv->setCustomAction('file', 'select');
        $priv->setCustomAction('category', 'select');
        $priv->setCustomAction('ref_notice', 'delete');
        $priv->setCustomAction('ref_remove', 'delete');
        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('tags', 'select');
        $priv->setCustomAction('fopen', 'select');
        $priv->setCustomAction('approval_log', 'select');
        $priv->setCustomAction('draft_remove', 'select');
        $priv->setCustomAction('move_to_draft', 'delete');
                
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new FileEntryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }        
        
        // as draft only allowed
        $actions = array('insert', 'update', 'clone');
        if(in_array($action, $actions)) {
            $action_to_check = ($action == 'clone') ? 'insert' : $action;
            if($this->priv->isPrivOptional($action, 'draft')) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        // check for roles
        $actions = array(
            'clone', 'status', 'update', 'delete', 'move_to_draft'
        );
        
        if(in_array($action, $actions) && $record_id) {
            
            // entry is private and user no role
            if(!$this->isEntryInUserRole($record_id)) {
                echo $priv->errorMsg();
                exit;
            }
            
            // if some of categories is private and user no role        
            $categories = $this->getCategoryById($record_id);
            $has_private = $this->isCategoryNotInUserRole($categories);
            if($has_private) {
                echo $priv->errorMsg();
                exit;                    
            }            
        }        
        
        
        // check for roles on insert
        if($action == 'insert') {
            $categories = array();
            if(!empty($_POST['category'])) {
                $categories = $_POST['category'];
            }
            
            $has_private = $this->isCategoryNotInUserRole($categories);
            if($has_private) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE id = '{$record_id}' AND author_id = {$priv->user_id}";    
        $priv->setOwnSql($sql);
        
        $sql = "SELECT active AS status FROM {$this->tbl->table}  WHERE id = '{$record_id}'";            
        $priv->setEntryStatusSql($sql);
        
        $priv->check($action);
        
        // set sql to select own records
        if($popup) { $priv->setOwnParam(1); } 
        else       { $priv->setOwnParam($this->getOwnParams($priv)); }
        
        $this->setSqlParams('AND ' . $priv->getOwnParam());
    }
    
    function getOwnParams($priv) {
        return sprintf("author_id=%d", $priv->user_id);
    }
        
    
    // ATTACHMENTS
    
    //get all articles that have this file as attachment
    function &getEntryToAttachment($file_id, $types = '1,2,3', $in_bulk = false) {
        
        $sql = "
        SELECT entry_id, attachment_id
        FROM 
            {$this->tbl->kb_attachment_to_entry} r
        WHERE 1 
            AND r.attachment_id IN ($file_id)
            AND r.attachment_type IN ($types)";
            
        //echo '<pre>', print_r($sql, 1), '</pre>';
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        if($in_bulk) {
            while($row = $result->FetchRow()) {
                $data[$row['attachment_id']][] = $row['entry_id'];
            }
        } else {
            while($row = $result->FetchRow()) {
                $data[] = $row['entry_id'];
            }            
        }
        
        return $data;
    }
    

    // get all files attached for this article
    function getAttachmentToEntry($entry_id, $types = '1,2,3') {
        $sql = "SELECT attachment_id AS aid, attachment_id FROM {$this->tbl->kb_attachment_to_entry} r
        WHERE r.entry_id IN ($entry_id) AND r.attachment_type IN ($types)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // get all files attached for this trouble entry step
    function getAttachmentToTroubleEntry($entry_id, $types = '1,2,3') {
        $sql = "SELECT attachment_id AS aid, attachment_id FROM {$this->tbl->trouble_attachment_to_step} r
        WHERE r.entry_id IN ($entry_id) AND r.attachment_type IN ($types)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getReferencedArticlesNum($ids) {
        $sql = "SELECT attachment_id, COUNT(*) FROM {$this->tbl->kb_attachment_to_entry} 
        WHERE attachment_id IN ($ids) GROUP BY attachment_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // DELETE RELATED // --------------------- 
    
    /*function isEntryInUse($entry_id) {
            $sql = "SELECT COUNT(*) as num FROM {$this->tbl->kb_attachment_to_entry} 
            WHERE attachment_id = '{$entry_id}'";
            $result = $this->db->Execute($sql) or die(db_error($sql));
            return $result->Fields('num');
        }*/    
    
    
    // delete from attachment_to_entry records where this file attached
    function deleteEntryToAttachment($record_id, $type = '1,2,3') {
        $sql = "DELETE FROM {$this->tbl->kb_attachment_to_entry} 
        WHERE attachment_id IN ({$record_id}) AND attachment_type IN({$type})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteEntries($record_id) {
        $sql = "DELETE FROM {$this->tbl->entry} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    function deleteEntryToCategory($record_id, $all = true) {
        $param = ($all === true) ? 1 : "is_main = '{$all}'";
        $sql = "DELETE FROM {$this->tbl->entry_to_category} WHERE entry_id IN ({$record_id}) AND {$param}";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function delete($record_id, $from_disk = false, $update_sort = true) {
        
        // convert to string 1,2,3... to use in IN()
        $record_id = $this->idToString($record_id);
        
        $this->deleteFileData($record_id, $from_disk);
        $this->deleteEntries($record_id);
        
        if($update_sort) {
            $this->updateSortOrderOnDelete($record_id);
        }        
        
        $this->deleteEntryToCategory($record_id);
        $this->deleteSchedule($record_id);
        $this->deleteSubscription($record_id);        
        $this->deleteRoleToEntry($record_id);
        
        // normally just attached (not online) references could stay in tables 
        // so we remove it
        $this->deleteEntryToAttachment($record_id);
        
        // delete empty hit records 
        $this->deleteHitRecord($record_id);
        
        // delete tags
        $this->tag_manager->deleteTagToEntry($record_id);
        
        // delete custom fields
        $this->cf_manager->delete($record_id);
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);
    }
    
    
    // concrete
    
    function getEntryStatusPublishedConcrete() {
        return $this->getEntryStatusPublished('file_status');
    }    
}
?>