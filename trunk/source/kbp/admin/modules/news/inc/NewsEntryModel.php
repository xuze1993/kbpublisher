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
require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
require_once APP_MODULE_DIR . 'tool/tag/inc/TagModel.php';


class NewsEntryModel extends CommonEntryModel
{
    var $tbl_pref_custom = '';
    var $tables = array('table'=>'news', 'entry'=>'news', 'news', 'custom_data' => 'news_custom_data');
    var $custom_tables =  array('file_entry', 
                                'role'=>'user_role', 
                                'list_value', 
                                'data_to_value'=>'data_to_user_value',
                                'data_to_value_string'=>'data_to_user_value_string',
                                'autosave' => 'entry_autosave',
                                'user',
                                'entry_hits',
                                'entry_schedule',
                                'user_subscription');
    

    var $use_entry_private = false;
    var $role_read_rule = 'news_entry_to_role_read';
    var $role_write_rule = 'news_entry_to_role_write';
    var $role_read_id = 107;
    var $role_write_id = 108;
    var $entry_type = 3;
    
    
    function __construct($user = array(), $apply_private = 'write') {
        parent::__construct();
        $this->dv_manager = new DataToValueModel();
        $this->cf_manager = new CommonCustomFieldModel($this);
        $this->tag_manager = new TagModel;
        $this->tag_manager->entry_type = $this->entry_type;
		
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();
        
        require_once APP_MODULE_DIR . 'user/role/inc/RoleModel.php';
        $this->role_manager = new RoleModel();
        $this->setEntryRolesSql($apply_private);
    }
        
    
    function getById($record_id) {

        $sql = "SELECT e.*    
        FROM {$this->tbl->table} e 
        WHERE e.id = %d";
        
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getRecordsSql() {
        
        $sql = "
        SELECT 
            e.*,
            {$this->sql_params_select}
            
        FROM 
            ({$this->tbl->table} e
            {$this->sql_params_from})
            {$this->entry_role_sql_from}
            {$this->sql_params_join}
            
        WHERE 1
            AND {$this->sql_params}
            AND {$this->entry_role_sql_where}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }    
    
    
    function getCountRecordsSql() {
        $s = ($this->entry_role_sql_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT {$s} AS 'num'
        FROM 
            ({$this->tbl->table} e
            {$this->sql_params_from})
            {$this->entry_role_sql_from}
            {$this->sql_params_join}

        WHERE 1
            AND {$this->sql_params}
            AND {$this->entry_role_sql_where}";
    
        return $sql;
    }        
    
    
    // PRIV // ------------------------------    
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {
        
        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('preview', 'select');
        $priv->setCustomAction('autosave', 'select');
        $priv->setCustomAction('tags', 'select');

        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new NewsEntryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
        
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        
        // check for roles
        $actions = array(
            // 'preview', 'detail', //  has_private for write only now so need to validate 
            'clone', 'status', 'update', 'delete'
            );
        
        if(in_array($action, $actions) && $record_id) {
            
            // entry is private and user no role
            if(!$this->isEntryInUserRole($record_id)) {
                echo $priv->errorMsg();
                exit;
            }            
        }        
        
    
        $priv->check($action);
    }
    
    
    function save($obj) {

        $action = (!$obj->get('id')) ? 'insert' : 'update';                                  

        // insert
        if($action == 'insert') {

            $id = $this->add($obj);
            $this->saveSchedule($obj->getSchedule(), $id);

            if($obj->get('private')) {
                $this->saveRoleToEntryObj($obj, $id);
            }
            
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id);
            $this->cf_manager->save($obj->getCustom(), $id);


        // update
        } else {

            $id = $obj->get('id');

            $this->update($obj);
            $this->deleteSchedule($id);
            $this->saveSchedule($obj->getSchedule(), $id);

            $this->deleteRoleToEntry($id);
            $this->saveRoleToEntryObj($obj, $id);
            
            $this->tag_manager->deleteTagToEntry($id); 
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id); 
			
            $this->cf_manager->delete($id);
            $this->cf_manager->save($obj->getCustom(), $id);
        }
    
        return $id;
    }
    
    
    // DELETE RELATED // --------------------- 
    
    function deleteEntries($record_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function delete($record_id, $update_sort = true) {
        
        // convert to string 1,2,3... to use in IN()
        $record_id = $this->idToString($record_id);
        
        $this->deleteEntries($record_id);
        $this->deleteSchedule($record_id);
        $this->deleteRoleToEntry($record_id);
        
        // delete tags
        $this->tag_manager->deleteTagToEntry($record_id); 
		
        // delete custom fields
        $this->cf_manager->delete($record_id);
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);
    }    
}
?>