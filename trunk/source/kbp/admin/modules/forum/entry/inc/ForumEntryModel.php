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
require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModelBulk.php';
require_once APP_MODULE_DIR . 'forum/category/inc/ForumCategoryModel.php';
require_once APP_MODULE_DIR . 'tool/list/inc/ListValueModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
require_once APP_MODULE_DIR . 'tool/tag/inc/TagModel.php';


class ForumEntryModel extends CommonEntryModel
{
	var $tbl_pref_custom = 'forum_';
	var $tables = array('table'=>'entry', 'entry', 'category', 'entry_to_category', 
	                    'message', 'message_attachment',
                        'featured');
	
	var $custom_tables =  array('file_entry', 
								'role'=>'user_role',
								'list_value', 
								'data_to_value'=>'data_to_user_value',
								'data_to_value_string'=>'data_to_user_value_string',
								'user',
                				'entry_schedule',
								'entry_hits'
								);   
	

	var $use_entry_private = false;
    var $role_read_rule = 'forum_entry_to_role_read';
    var $role_read_id = 111;

    var $role_write_rule = 'forum_entry_to_role_write';
    var $role_write_id = 112;

    var $select_type = 'index';
    var $show_bulk_sort = true;
    var $update_diff = 60; // seconds, to display updated if difference more than

    var $entry_type = 4; // means forum entry (topic)
		
	
	function __construct($user = array(), $apply_private = 'write') {
		parent::__construct();
		$this->dv_manager = new DataToValueModel();
		$this->cat_manager = new ForumCategoryModel($user);
        
        $this->tag_manager = new TagModel;
        $this->tag_manager->entry_type = $this->entry_type;
		
		$this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
		$this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
		$this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();
        
        
        $this->role_manager = &$this->cat_manager->role_manager;
        $this->setEntryRolesSql($apply_private);
        $this->setCategoriesNotInUserRole($apply_private);
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
            f.id as is_sticky,
			{$this->sql_params_select}
			
		FROM 
			({$this->tbl->entry} e,
			{$this->tbl->category} cat,
			{$this->tbl->entry_to_category} e_to_cat
			{$this->sql_params_from})
        
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        LEFT JOIN {$this->tbl->featured} f
        ON e.id = f.entry_id
            AND (f.date_to > NOW() OR f.date_to IS NULL)
		
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
            
        LEFT JOIN {$this->tbl->featured} f
        ON e.id = f.entry_id
            AND (f.date_to > NOW() OR f.date_to IS NULL)
			
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
			cat.id AS category_id,
			cat.private AS category_private,
			cat.name AS category_title,
			UNIX_TIMESTAMP(e.date_posted) AS ts,
			UNIX_TIMESTAMP(e.date_updated) AS tsu,
            f.id as is_sticky,
			{$this->sql_params_select}
			
		FROM 
			({$this->tbl->entry} e {$this->entry_sql_force_index},
			{$this->tbl->category} cat
			{$this->sql_params_from})
        
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        LEFT JOIN {$this->tbl->featured} f
        ON e.id = f.entry_id
            AND (f.date_to > NOW() OR f.date_to IS NULL)
		
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
            
        LEFT JOIN {$this->tbl->featured} f
        ON e.id = f.entry_id
            AND (f.date_to > NOW() OR f.date_to IS NULL)
            
		WHERE e.category_id = cat.id
		AND {$this->entry_role_sql_where}
		AND {$this->sql_params}";
		
		//echo "<pre>"; print_r($sql); echo "</pre>";
		return $sql;
	}	

	
	function getRecordsSql() {	
		return ($this->select_type == 'index') ? $this->getRecordsSqlIndex() 
		                                       : $this->getRecordsSqlCategory();
	}
	
	
	function getCountRecordsSql() {
		return ($this->select_type == 'index') ? $this->getCountRecordsSqlIndex() 
		                                       : $this->getCountRecordsSqlCategory();
	}
	
    
    // MESSAGE ATTACHMENTS
    
    function getMessageListAttachment($id) {
        $sql = "SELECT
            a.id,
            a.message_id,
            a.filename,
            a.mime_type
        FROM 
            ({$this->tbl->message_attachment} a,
            {$this->tbl->message} m)
        WHERE
            m.entry_id = {$id}
            AND a.message_id = m.id";

        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());
        $data = $result->GetArray();
        
        $data2 = array();
        
        foreach ($data as $attach) {
            $data2[$attach['message_id']][] = array('filename' => $attach['filename'], 
                                                    'mime_type' => $attach['mime_type'], 
                                                    'id' => $attach['id']);
        }

        return $data2;
    }
    
    
    function getMessageAttachment($id) {
        $sql = "SELECT
            a.id,
            a.message_id,
            a.filename,
            a.mime_type
        FROM 
            {$this->tbl->message_attachment} a
        WHERE
            a.message_id = {$id}";

        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());

        return $result->GetArray();
    }
    
    
    function saveAttachment($message_id, $upload) {
        require_once 'eleontev/SQL/MultiInsert.php';
        
        $data = array();
        
        foreach ($upload as $file) {
            $content = Uploader::getFileContent($file['tmp_name']);
            $content = base64_encode($content);
        
            $mime_type = $file['type'];
            $name = $file['name'];
            
            $data[] = array($name, $mime_type, $content);
        }
        
        $ins = new MultiInsert;
        $ins->setFields(array('filename', 'mime_type', 'filecontent'), 'message_id');
        $ins->setValues($data, $message_id);
        
        $sql = $ins->getSql($this->tbl->message_attachment, 'INSERT');
        $result = &$this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        $id = $this->db->Insert_ID();
        return $id;
    }
    
    
    function reassignAttachment($message_id, $attachment_ids) {
        $attachment_ids = implode(',', $attachment_ids);
        $sql = "UPDATE {$this->tbl->message_attachment}
            SET message_id = {$message_id}
            WHERE id IN ({$attachment_ids})";
                    
        $result =& $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
	
    
	// ACTIONS // ---------------------
	
	function saveEntryToCategory($cat, $cat_mirror, $record_id, $sort_order, $add_categories = false) {
		
		require_once 'eleontev/SQL/MultiInsert.php';
		
		$data = array();
		$record_id = (is_array($record_id)) ? $record_id : array($record_id);
		$cat = (is_array($cat)) ? $cat : array($cat);
		
		$i = ($add_categories) ? 2 : 1; // all not main
		foreach($cat as $cat_id) {
			$sort = false;
			foreach($record_id as $entry_id) {
				$sort = 1; //(!$sort) ? $sort_order[$cat_id] : ++$sort;
				$is_main = ($i == 1) ? 1 : 0;
				$data[] = array($cat_id, $is_main, $entry_id, $sort);
			}
			
			$i++;
		}		
		
		$sql = MultiInsert::get("INSERT IGNORE {$this->tbl->entry_to_category} (category_id, is_main, entry_id, sort_order) 
		                         VALUES ?", $data);
		
		//echo "<pre>"; print_r($data); echo "</pre>";
		//echo "<pre>"; print_r($sql); echo "</pre>";
		//exit;
		
		return $this->db->Execute($sql) or die(db_error($sql));
	}

	
	function save($obj) {

		// sorting manipulations
		$sort_values = false;
		
		$action = (!$obj->get('id')) ? 'insert' : 'update';							  
		
		// insert
		if($action == 'insert') {
			                
			$id = $this->add($obj);
            
			$this->saveEntryToCategory($obj->getCategory(), array(), $id, $sort_values);
      		$this->saveSchedule($obj->getSchedule(), $id);
            
            if ($obj->getSticky()) {
                if ($obj->getStickyDate()) {
                    $sticky_date = date('Ymd', strtotime($obj->getStickyDate()));
                    $this->featureTopic($id, $sticky_date);
                    
                } else {
                    $this->featureTopic($id);
                }
            }
			
			/*if($obj->get('private')) {
				$rule_id = $this->getPrivateRule($obj->get('private'));				
				$this->saveRoleReadToEntry($obj->getRoleRead(), $id, $rule_id);	
			}*/
			
			$this->addHitRecord($id);	
		
		// update
		} else {
			
			$id = $obj->get('id');		
			
			$this->update($obj);
			
			//if($update_category) {
				$this->deleteEntryToCategory($id);
				$this->saveEntryToCategory($obj->getCategory(), array(), $id, $sort_values);
			//}

			$this->deleteSchedule($id);
			$this->saveSchedule($obj->getSchedule(), $id);
            
            $this->unfeatureTopic($id);
            if ($obj->getSticky()) {
                if ($obj->getStickyDate()) {
                    $sticky_date = date('Ymd', strtotime($obj->getStickyDate()));
                    $this->featureTopic($id, $sticky_date);
                    
                } else {
                    $this->featureTopic($id);
                }
            }
            
            $this->tag_manager->deleteTagToEntry($id); 
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id); 
							
			/*$this->deleteRoleReadToEntry($id);
			if($obj->get('private')) {
				$rule_id = $this->getPrivateRule($obj->get('private'));
				$this->saveRoleReadToEntry($obj->getRoleRead(), $id, $rule_id);
			}*/
		}
		
		return $id;
	}

	
	// DELETE RELATED // --------------------- 
	
	function deleteEntries($record_id) {
		$sql = "DELETE FROM {$this->tbl->entry} WHERE id IN ({$record_id})";
		return $this->db->_query($sql) or die(db_error($sql));
	}
	
	function deleteEntryToCategory($record_id, $all = true) {
		$param = ($all === true) ? 1 : "is_main = '{$all}'";
		$sql = "DELETE FROM {$this->tbl->entry_to_category} WHERE entry_id IN ({$record_id}) AND {$param}";
		return $this->db->_query($sql) or die(db_error($sql));
	}		
	
	function deleteMessages($record_id) {
		$sql = "DELETE FROM {$this->tbl->message} WHERE entry_id IN ({$record_id})";
		return $this->db->_query($sql) or die(db_error($sql));
	}	
	    
    function deleteAttachmentToMessage($record_id) {
        $sql = "DELETE a.*
        FROM ({$this->tbl->message_attachment} a,
            {$this->tbl->message} m)
        WHERE m.id = a.message_id
            AND m.entry_id IN ({$record_id})";
        
        return $this->db->_query($sql) or die(db_error($sql));
    }
	
	
	function delete($record_id, $update_sort = true) {
		
		// convert to string 1,2,3... to use in IN()
		$record_id = $this->idToString($record_id);
		
		$this->deleteEntries($record_id);
		
		$this->deleteEntryToCategory($record_id);
        $this->deleteAttachmentToMessage($record_id);
		$this->deleteMessages($record_id);
        
        $this->tag_manager->deleteTagToEntry($record_id);
		
		// delete empty hit records 
		$this->deleteHitRecord($record_id);		
	}
	
	
	
	// PRIV // ------------------------------	
	
	// if check priv is different for model so reassign 
	function checkPriv(&$priv, $action, $record_id, $popup = false, $bulk_action = false) {

		$priv->setCustomAction('bulk', 'update');
		$priv->setCustomAction('detail', 'select');
        $priv->setCustomAction('list', 'select');
		$priv->setCustomAction('category', 'select'); // for popup categories
		$priv->setCustomAction('role', 'select'); // for popup roles
		
		
		// bulk will be first checked for update access
		// later we probably need to change it
		// for now it works ok as we do not allow bulk without full update access
		if($action == 'bulk') {
			$bulk_manager = new ForumEntryModelBulk();
			$allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
			if(!in_array($bulk_action, $allowed_actions)) {
				echo $priv->errorMsg();
				exit;
			}
		
		} else {
			

		}
		
		// check for roles
		$actions = array('update', 'delete');
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
	
	
	// concrete
	
	function getEntryStatusPublishedConcrete() {
		return $this->getEntryStatusPublished('forum_status');
	}
    

	// MESSAGES // --------------------------
    
    function getMessages($id) {
        $sql = "SELECT 
            m.*,
            u.first_name,
            u.last_name     
        FROM {$this->tbl->message} m
        LEFT JOIN {$this->tbl->user} u ON m.user_id = u.id 
        WHERE
            m.entry_id = '{$id}'
        ORDER BY m.date_posted ASC";
                                    
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();            
    }
    
    
    function getMessage($id) {
        $sql = "SELECT 
            m.*,
            u.first_name,
            u.last_name     
        FROM {$this->tbl->message} m
        LEFT JOIN {$this->tbl->user} u ON m.user_id = u.id 
        WHERE
            m.id = '{$id}'";
                                    
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();            
    }
    
       
    function getUserById($user_id) {
        $sql = "SELECT * FROM {$this->tbl->user} WHERE id = '{$user_id}'";
        $result = &$this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getCountEntryMessages() {
        $sql = "SELECT e.id, COUNT(m.id)
            FROM {$this->tbl->entry} e
            
            LEFT JOIN {$this->tbl->message} m
            ON e.id = m.entry_id
            
            GROUP BY e.id";

        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function featureTopic($entry_id, $date_to = NULL) {
        $sql = "INSERT {$this->tbl->featured} VALUES (NULL, %d, 0, %s, 1, 1)";
        $sql = sprintf($sql, $entry_id, ModifySql::_getQuoted($date_to));
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function unfeatureTopic($entry_id) {
        $sql = "DELETE FROM {$this->tbl->featured} WHERE entry_id = '%s' AND message_id = 0";
        $sql = sprintf($sql, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    function getStickyDate($entry_id, $message_id = 0) {
        $sql = "SELECT date_to
            FROM {$this->tbl->featured}
            WHERE entry_id = '{$entry_id}'
            AND message_id = '{$message_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('date_to');
    }
    
    
    // STICKY
    
    function increaseFeaturedEntrySortOrder($category_id) {
        $sql = "UPDATE {$this->tbl->featured} f
                INNER JOIN {$this->tbl->table} e
                ON e.id = f.entry_id
                    AND e.category_id = {$category_id}
                SET f.sort_order = f.sort_order + 1";
        return $this->db->Execute($sql) or die(db_error($sql));
    }

    
    function saveFeaturedEntry($entry_id) {
        $sql = "INSERT {$this->tbl->featured} (entry_id) VALUES ('{$entry_id}')";
        return $this->db->Execute($sql) or die(db_error($sql));    
    }
    
    
    function deleteFeaturedEntry($entry_id) {
        $sql = "DELETE FROM {$this->tbl->featured}
            WHERE entry_id = {$entry_id}
                AND message_id = 0";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
}
?>