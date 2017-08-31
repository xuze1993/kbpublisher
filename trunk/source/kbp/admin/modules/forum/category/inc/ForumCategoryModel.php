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
require_once 'eleontev/Util/TreeHelper.php';
require_once 'eleontev/SQL/TableSortOrder.php';
require_once 'core/common/CommonCategoryModel.php';
require_once 'core/common/CommonCustomFieldModel.php';
require_once APP_MODULE_DIR . 'forum/category/inc/ForumCategoryModelBulk.php';


class ForumCategoryModel extends CommonCategoryModel
{

    var $tbl_pref_custom = 'forum_';
    var $tables = array('table'=>'category', 'category', 'entry', 'entry_to_category');
    var $custom_tables = array('data_to_user_value', 'user', 
                               'role'=>'user_role', 'user_subscription', 'message' => 'forum_message');
    
	var $role_read_rule = 'forum_category_to_role_read';
    var $role_read_id = 11;
    
    var $role_write_rule = 'forum_category_to_role_write';
    var $role_write_id = 12;
    
    var $admin_user_rule = 'forum_category_to_user_admin';
    var $admin_user_id = 13;
    
    var $show_bulk_sort = true;
    var $entry_type = 14; // means forum category, topic type = 4 
    
    
    function __construct($user = array()) {
        parent::__construct();
        $this->dv_manager = new DataToValueModel();
        
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();
        
        require_once APP_MODULE_DIR . 'user/role/inc/RoleModel.php';
        $this->role_manager = new RoleModel();            
    }
    
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {

        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('category', 'select');
        $priv->setCustomAction('clone_tree', 'insert');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new ForumCategoryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
        
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        // check for actions and not correct user if private category
        // insert if add as "Add new child category"
        $actions = array(
            'insert', 'update', 'delete'
        );
        
        if(in_array($action, $actions) && $record_id) {
            $private = $this->getCategoriesNotInUserRole('write');
            if(in_array($record_id, $private)) {
                echo $priv->errorMsg();
                exit;
            }
        }        
        
        $priv->check($action);
    }
    
    
    function getMessagesNum($ids) {
        $sql = "SELECT ec.category_id, SUM(e.posts) 
        FROM ({$this->tbl->entry_to_category} ec,
            {$this->tbl->entry} e)
        
        WHERE ec.category_id IN($ids)
        AND ec.entry_id = e.id
        GROUP BY ec.category_id";
        
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
}
?>