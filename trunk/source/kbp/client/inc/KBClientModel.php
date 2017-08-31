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


class KBClientModel extends KBClientBaseModel
{
    
    var $tbl_pref_custom = 'kb_';
    var $tables = array('entry', 'category', 'entry_to_category', 'comment', 'rating', 'rating_feedback',
                        'glossary', 'attachment_to_entry', 'related_to_entry', 'custom_data', 'entry_history');
                        
    var $custom_tables = array('user',
                               'feedback',
                               'feedback_custom_data',
                               'list_value',
                               'file_entry',
                               'file_category',
                               'file_entry_to_category',
                               'forum_message',
                               'data_to_value'=>'data_to_user_value',
                               'data_to_value_string'=>'data_to_user_value_string',
                               'article_template',
                               'news',
                               'lock' => 'entry_lock',
                               'entry_hits',
                               'entry_featured',
                               'user_subscription',
                               'user_company',
                               'user_ban',
                               'user_temp',
                               'log_search',
                               'custom_field',
                               'custom_field_to_category',
                               'custom_field_range_value',
                               'tag',
                               'tag_to_entry');
    
    var $is_registered = false;
    var $user_id;
    var $user_priv_id;
    var $search_params;

    var $categories = array();
    var $categories_parent = array();
    var $tree_helper_array = array();
    var $is_private_category = false;    
    var $role_skip_categories = array();
    var $all_skip_categories = array();
    var $is_closed_entry = false;
    
    // for select article
    var $sql_params = 1;
    var $sql_params_order = '';
    var $sql_params_select = 1;
    var $sql_params_ar = array();
    
    // sessions name
    var $session_vote_name = 'kb_vote_';
    var $session_view_name = 'kb_view_';
    var $session_captha_name = 'kb_captcha_';
    
    // rules id in data to user rule
    var $role_entry_read_id = 101;
    var $role_entry_write_id = 105;    
    var $role_category_read_id = 1;
    var $role_category_write_id = 5;    
    
    var $entry_role_sql_group;
    var $entry_role_sql_from;
    var $entry_role_sql_where = '1';
    var $user_child_role_ids = array();
    
    var $entry_list_id = 1; // id in list statuses
    var $entry_type = 1; // entry type in entry_hits, entry_schedule      
    var $entry_type_cat = 11; // entry type for category
    
    var $dv_manager;
    var $role_manager;
    
    
    // ATTACHMENT // ----------------------
    
    function getAttachmentList($entry_id) {
        
        $published_status = $this->getEntryPublishedStatus(2);
        
        $sql = "SELECT
            e.id,
            e.title,
            e.filesize,
            e.filename
        FROM 
            {$this->tbl->file_entry} e,
            {$this->tbl->attachment_to_entry} ae
        WHERE 1
            AND ae.entry_id = %d
            AND ae.attachment_id = e.id
            AND ae.attachment_type IN(1,3)
            AND e.active IN ({$published_status})";
        
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();
    }
    
    
    // is file to donwnload realy attached to article
    // return file data
    function getAttachment($entry_id, $file_id) {
    
        $published_status = $this->getEntryPublishedStatus(2);
    
        $sql = "SELECT 
            e.id,
            e.filename,
            e.directory,
            e.filesize,
            e.filetype,
            e.directory
        FROM 
            {$this->tbl->file_entry} e,
            {$this->tbl->attachment_to_entry} ae
        WHERE 1
            AND ae.entry_id = %d
            AND ae.attachment_id = %d
            AND ae.attachment_id = e.id
            AND e.active IN ({$published_status})";
        
        $sql = sprintf($sql, $entry_id, $file_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();    
    }    


    function addDownload($file_id) {
        $this->addView($file_id, 2);
     }
    
    
    // SETTINGS // ------------------------        
    
    
    // USER // ------------------------
    
    function getUserInfo($user_id) {
        $sql = "SELECT u.*, c.title AS 'company'
        FROM {$this->tbl->user} u
        LEFT JOIN {$this->tbl->user_company} c ON c.id = u.company_id 
        WHERE u.id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->FetchRow();
    }
    
    
    function setUserStatus($user_id, $status) {
        $sql = "UPDATE {$this->tbl->user} SET active = '$status' WHERE id = '$user_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function setUserGrantor($user_id, $val) {
        $sql = "UPDATE {$this->tbl->user} SET grantor_id = '$val' WHERE id = '$user_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function isUser($confirm_str, $user_id = false) {
        $user_param = ($user_id !== false) ? "AND id = '{$user_id}'" : '';
        $sql = "SELECT id, active FROM {$this->tbl->user} 
        WHERE MD5(email) = '{$confirm_str}' {$user_param}";
        $result = $this->db->SelectLimit($sql, 1) or die(db_error($sql));
        return $result->FetchRow();
    }
        
    
/*
    function getUserBanById($user_id) {
        $datetime = date('Y-m-d H:i:s');
        
        $sql = "SELECT ban_type, user_reason
            FROM {$this->tbl->user_ban}
            WHERE user_id = {$user_id}
            AND IF (date_end, '{$datetime}' BETWEEN date_start AND date_start + INTERVAL date_end MINUTE, 1)
            GROUP BY ban_type";

        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
*/
    
    
/*
    function getUserBanByRule($email, $ip) {
        $datetime = date('Y-m-d H:i:s'); 
        
        $sql = "SELECT ban_type, user_reason
            FROM {$this->tbl->user_ban}
            WHERE 1
                AND (ban_rule = 1 AND ban_string LIKE '{$email}') 
                OR (ban_rule = 2 AND ban_string LIKE '{$ip}')
            AND IF (date_end, '{$datetime}' BETWEEN date_start AND date_start + INTERVAL date_end MINUTE, 1)
            GROUP BY ban_type";
            
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
*/
        
    
    // CATEGORIES // ------------------------
    
    // when select entry_id to find its category to help with session category_id
    function getCategoryIdsByEntryId($entry_id) {
        
/*
        // not active also here 
        $sql = "SELECT category_id AS c_id,  category_id
        FROM {$this->tbl->entry_to_category} e_to_cat
        WHERE e_to_cat.entry_id = '%d'";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        echo $this->getExplainQuery($this->db, $result->sql);
*/
        
        // do not return not active categories
        $sql = "SELECT category_id, is_main
        FROM 
            {$this->tbl->category} cat, 
            {$this->tbl->entry_to_category} e_to_cat
        WHERE cat.id = e_to_cat.category_id
        AND cat.active = 1
        AND e_to_cat.entry_id = '%d'";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $ret = $result->GetAssoc();
        arsort($ret, SORT_NUMERIC);
        
        return array_keys($ret);
    }
    
    
    function isCategoryExistsAndActive($category_id) {
        $sql = "SELECT id FROM {$this->tbl->category} WHERE active = 1";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
        
    
    function getCategoryRssData() {
        
        $private = implode(',', $this->private_rule['read']);
        $sort = $this->getSetting('category_sort_order');
        $rss_setting = $this->getSetting('rss_generate');
    
        $params = '1';
        if($rss_setting == 'top') {
            $params = "parent_id = 0";
        }
    
        $sql = "SELECT id, name AS title, description FROM {$this->tbl->category}
        WHERE active = 1
        AND private NOT IN({$private})
        AND {$params}
        ORDER BY {$sort}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();    
    }
    
    
    function getCategoryList($parent_id = 0) {
        
        $private_sql = $this->getPrivateSql();
        $role_skip_sql = $this->getCategoryRolesSql();
        $sort = $this->getSetting('category_sort_order');
        
        $sql = "SELECT 
            c.id AS category_id,
            c.*
        FROM 
            {$this->tbl->category} c
        WHERE 1
            AND c.parent_id = '{$parent_id}' 
            AND c.active = 1
            AND {$private_sql}
            AND {$role_skip_sql}
        ORDER BY {$sort}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->getAssoc();
    }
    
    
    function countEntriesPerCategory($category_ids) {
    
        $private_sql = $this->getPrivateSql('count');
    
        $sql = "SELECT 
            e_to_cat.category_id,
            COUNT(*)    
        FROM 
            ({$this->tbl->entry} e, 
            #{$this->tbl->entry_to_category} e_to_cat FORCE INDEX (category_id))
            {$this->tbl->entry_to_category} e_to_cat)
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e_to_cat.category_id IN($category_ids)
            AND e_to_cat.entry_id = e.id
            AND e.active IN ({$this->entry_published_status})
            AND {$this->entry_role_sql_where}
            AND {$private_sql}
        GROUP BY e_to_cat.category_id";
        
        // {$this->entry_role_sql_group}
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();
    }
    
    
    function countEntriesPerCategoryChilds($category_ids) {
    
        $private_sql = $this->getPrivateSql('count');
    
        $sql = "SELECT 
            COUNT(*) AS 'cnt'    
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->entry_to_category} e_to_cat)
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e_to_cat.category_id IN($category_ids)
            AND e.id = e_to_cat.entry_id
            AND e.active IN ({$this->entry_published_status})
            AND {$this->entry_role_sql_where}
            AND {$private_sql}
        {$this->entry_role_sql_group}";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->Fields('cnt');
    }    
    
    
    function getCategoriesSql($sort, $type_param = 'all') {
        $type_param = ($type_param == 'all') ? 1 : "category_type IN($type_param)";
        $sql = "SELECT id, parent_id, name, description, sort_order, private, category_type AS type
        FROM {$this->tbl->category} c FORCE INDEX ( sort_order )
        WHERE c.active = 1 AND {$type_param}
        ORDER BY {$sort}";
        
        return $sql;    
    }
    
    
    function &getCategories($sort = false, $type_param = 'all') {
        
        // here we list all, even not active 
        $this->role_skip_categories = &$this->getCategoriesNotInUserRole();
        //echo '<pre>', print_r($this->role_skip_categories, 1), '</pre>';
        
        $private_sql = $this->getPrivateSql();
        $sort = ($sort) ? $sort : $this->setting['category_sort_order'];
        
        $sql = $this->getCategoriesSql($sort, $type_param);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $categories = array();
        $removed_role_skip_categories = array();
        while($row = $result->FetchRow()) {
            $categories[$row['id']] = $row;
                
            // read/write, write private
            if(in_array($row['private'],  $this->private_rule['read'])) {
                $this->is_private_category = true;
                        
                // remove category from categories as it not in user role
                if($private_sql != 1 || isset($this->role_skip_categories[$row['id']])) {
                    unset($categories[$row['id']]);
                    
                    // all unset categories
                    $this->all_skip_categories[$row['id']] = $row['id'];
                    
                    // save these categories
                    $removed_role_skip_categories[$row['id']] = $row['id'];
                }
            }
        }
        

        // need to remove not active from $this->role_skip_categories, not to double it in query
        $this->role_skip_categories = array_intersect($this->role_skip_categories, 
                                         array_merge(array_keys($categories), $removed_role_skip_categories));
            
        //echo '<pre>', print_r($this->role_skip_categories, 1), '</pre>';
        //echo "<pre>"; print_r($categories); echo "</pre>";
        return $categories;
    }
    
        
    function setCategories($sort = false, $type_param = 'all') { 
        $this->categories = &$this->getCategories($sort, $type_param);
    }
    
    
    function &getCategorySelectRange($arr, $parent_id = 0, $pref = '-- ') {
        
        if($arr === false) {
            $arr = &$this->categories;
        }
        
        if(!$arr) {
            $data = array();
            return $data; 
        }
        
        $tree_helper = &$this->getTreeHelperArray($arr, $parent_id);
        foreach($tree_helper as $id => $level) {
    
            $p = ($level == 0) ? '' : str_repeat($pref, $level);
            $data[$id] = $p . $arr[$id]['name'];
        }
        
        return $data;
    }
    
    
    // to generate form select range $arr from getSelectRecords
    function &getCategorySelectRangeFolow($arr = false, $parent_id = 0, $pref = ' -> ') {
        
        if($arr === false) {
            $arr = &$this->categories;
        }
        
        if(!$arr) {
            $data = array();
            return $data; 
        }
        
        $tree_helper = &$this->getTreeHelperArray($arr, $parent_id);
        foreach($tree_helper as $id => $level) {
        
            if($level == 0) {
                $data[$id] = $arr[$id]['name'];
                $prev[$level] = $arr[$id]['name'];
            
            } else {
                $data[$id] = $prev[$level-1] . $pref . $arr[$id]['name'];
                $prev[$level] = $data[$id];
            }
        }
        
        return $data;
    }        
    
    
    function &getTreeHelperArray($arr, $parent_id = 0) {
        static $calls = 1;
        if(!$arr) { 
            $a = array(); 
            return $a; 
        }
        
        // here we try to save some time and return 
        // previosly defined tree_helper_array if any 
        if($this->tree_helper_array && $parent_id == 0) {
            return $this->tree_helper_array;
        }
        
        //echo "<pre>Parent ID: "; print_r($parent_id); echo "</pre>";
        //echo "<pre>Call #: "; print_r($calls++); echo "</pre>";
        //echo "<pre>"; print_r('------------'); echo "</pre>";
        
        $tree = new TreeHelper();
        foreach(array_keys($arr) as $k) {
            $tree->setTreeItem($arr[$k]['id'], $arr[$k]['parent_id']);
        }
        
        if($parent_id == 0) {
            $this->tree_helper_array = &$tree->getTreeHelper($parent_id);
            return $this->tree_helper_array;
        } else {
            $helper_array = &$tree->getTreeHelper($parent_id);
            return $helper_array;            
        }
    }        
    
    
    function &getTreeArray($arr) {
        $tree = new TreeHelper();
        foreach(array_keys($arr) as $k) {
            $tree->setTreeItem($arr[$k]['id'], $arr[$k]['parent_id']);
        }
        
        return $tree->getTreeArray();
    }
    
    
    function getPrivateSql($category = true) {
        $sql = '1';
        if($this->getSetting('private_policy') == 1 && !$this->is_registered) {
            $private = implode(',', $this->private_rule['read']); // 1,3
        
            if($category === 'count') {
                $sql = "e.private NOT IN({$private})";
            } elseif($category === 'news') {
                $sql = "e.private NOT IN({$private})";
            } else {
                $sql = ($category) ? "c.private NOT IN({$private})" 
                                   : "cat.private NOT IN({$private}) AND e.private NOT IN({$private})";
            }
        }
        
        //echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }
    
    
    function getCategoryType($category_id) {
        $cat_type = array(1 => 'default', 2 => 'faq', 3 => 'book', 4 => 'faq2');
        $type = $this->categories[$category_id]['type'];
        return (isset($cat_type[$type])) ? $cat_type[$type] : 'default';
    }
    
    
    // PRIV // ------------------------------
    
    // sql to know is user have priv to (update) this article
    function getEntryPrivSql($entry_id, $user_id, $user_priv_id) {
        $sql = "SELECT 1 FROM {$this->tbl->entry}  WHERE id = '{$entry_id}' AND author_id = '{$user_id}'";
        return $sql;
    }    
    
    
    // CATEGORY ROLES // ---------------------------
    
    // get private categories ids
    function getCategoryPrivateIds($action = 'write') {    
        $p = implode(',', $this->private_rule[$action]);
        $sql = "SELECT id as id1, id FROM {$this->tbl->category} c WHERE private IN({$p})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
        
    
    function isUserPrivIgnorePrivate() {
        return in_array($this->user_priv_id, $this->no_private_priv);
    }


    function isPrivateCategory($category_id) {
    
        $ret = false;
        if($this->is_private_category) {
            $private = $this->private_rule['read'];
        
            // no category in categories = private
            if(!isset($this->categories[$category_id]) && 
                isset($this->all_skip_categories[$category_id])) {
                
                $ret = 'hidden';
        
            // private without role
            } elseif(isset($this->categories[$category_id]) && 
                     in_array($this->categories[$category_id]['private'], $private)) {
                        
                $ret = 'display';
            }
        }

        return $ret;
    }

    
    
    function getCategoryRolesSql($category = true) {
        $role_skip_sql = 1;
        if($category === 'e_to_cat.category_id') {
            $field = 'e_to_cat.category_id';
        } else {
            $field = ($category) ? 'c.id' : 'cat.id';
        }
        
        if($this->role_skip_categories) {
            $role_skip_sql = sprintf('%s NOT IN(%s)', $field, implode(',', $this->role_skip_categories));
        }
        
        return $role_skip_sql;
    }    
    
    
    function &getCategoriesNotInUserRole($action = 'read', $entries_ids = false) {
        
        $data = array();        
        if($this->isUserPrivIgnorePrivate()) { 
            return $data;
        }
         
        // hide private
        // if not display private entries at all we do not need any roles ...
        // filter by cat.private = 0 and hide all private 
        if($this->getSetting('private_policy') == 1 && !$this->is_registered) {
            return $data;
        }

        // display with lock sign
        // new private policy, no need roles if not reqistered
        // we return all and then generate lock sign in view
        if($this->getSetting('private_policy') == 2 && !$this->is_registered) {
            return $data;
        }
        
        
        $private_write_cats = $this->getCategoryPrivateIds($action);
        if(!$private_write_cats) {
            return $data;
        }        
        
        if($entries_ids) {
            $entries_ids = is_array($entries_ids) ? $entries_ids : explode(',', $entries_ids);
            $entries_ids = array_intersect($entries_ids, $private_write_cats);
        } else {
            $entries_ids = &$private_write_cats;    
        }
                
        
        $user_role_ids = array();
        if($this->user_role_id) {
            $user_role_ids = &$this->getUserRolesIdsChild();
        }
        
        
        $m = &$this->getDataToValueModel();
        $rule = ($action == 'read') ? $this->role_category_read_id : $this->role_category_write_id;
        $data_to_role = $m->getDataIds($rule, $entries_ids);

        foreach($data_to_role as $cat_id => $roles) {
            $result = array_intersect($user_role_ids, $roles);
            if(!$result) {
                $data[$cat_id] = $cat_id;
            }
        }
        
        // echo "<pre>Category to role: "; print_r($data_to_role); echo "</pre>";
        // echo "<pre>User role ids: "; print_r($user_role_ids); echo "</pre>";        
        // echo "<pre>Categories not in user role: "; print_r($data); echo "</pre>";
        
        return $data;
    }    
    
    
    // temp: change for many roles
    function setEntryRolesSql() {
        
        // no need private sql
        if($this->isUserPrivIgnorePrivate()) {
            return;
        }
            
        // if not display private entries at all
        if($this->getSetting('private_policy') == 1 && !$this->is_registered) {
            return;
        }
        
        // new private policy, no need roles if not reqistered
        if($this->getSetting('private_policy') == 2 && !$this->is_registered) {
            return;
        }
        
        $data = &$this->getEntryRolesSql($this->role_entry_read_id);
        $this->entry_role_sql_where = $data['entry_role_sql_where'];
        $this->entry_role_sql_from = $data['entry_role_sql_from'];
        $this->entry_role_sql_group = $data['entry_role_sql_group'];
    }
        

    // temp: change for many roles
    // read&write AND ??? or this select will work with read/write only by $role_entry_read_id
    function &getEntryRolesSql($role_entry_read_id) {
        
        $data['entry_role_sql_group'] = '';
        
        if($this->user_role_id) {
            $user_role_ids = $this->getUserRolesIdsChild();
            if(count($user_role_ids) > 1) {
                //$data['entry_role_sql_group'] = 'GROUP BY e.id';
            }            
            
            $pattern = '(^|,)(' . implode('|', $user_role_ids) . ')(,|$)';
            $data['entry_role_sql_where'] = "IF(du.user_value, du.user_value REGEXP '{$pattern}', 1)";            
                
        } else {
            $data['entry_role_sql_where'] = "du.data_value IS NULL"; // not show if any roles assigned
        }

        // instead of $role_entry_read_id (select diff roles) we can play with e.private
        // and always use the same $role_entry_read_id
        // AND e.private = 1 - read/write 
        // AND e.private = 2 - write 
        // AND e.private = 3 - read 

        $private = implode(',', $this->private_rule['read']);
        $data['entry_role_sql_from'] = "
        LEFT JOIN {$this->tbl->data_to_value_string} du 
        ON e.id = du.data_value
        AND du.rule_id = '{$role_entry_read_id}'
        AND e.private IN ({$private})";
        
        return $data;
    }
    
    
    // check if any private entries or concrete entry is private
    function isPrivateEntry($entry_id = false, $private_apply = 'read') {
        
        $params = ($entry_id) ? sprintf('id = %d', $entry_id) : '1';
        $private = implode(',', $this->private_rule[$private_apply]);

        $sql = "SELECT id FROM {$this->tbl->entry} WHERE private IN({$private}) AND {$params}";
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    // PRIVATE&ROLES // -----------------------
    
    function &getDataToValueModel() {
        if(!$this->dv_manager) {
            require_once 'core/app/DataToValueModel.php';
            $this->dv_manager = new DataToValueModel();
        }
        
        return $this->dv_manager;
    }
    
    
    function &getRoleModel() {
        if(!$this->role_manager) {
            require_once APP_MODULE_DIR . 'user/role/inc/RoleModel.php';
            $this->role_manager = new RoleModel();
        }
        
        return $this->role_manager;
    }    
    
    
    // $rule = category, entry
    function getPrivateInfo($entry_id, $rule) {
        
        $dv_manager = &$this->getDataToValueModel();
        
        if($rule == 'entry') {
            $rule_id = array($this->role_entry_read_id, $this->role_entry_write_id);
        } else {
            $rule_id = array($this->role_category_read_id, $this->role_category_write_id);
        }
        
        $map = array(
            'read' => array($this->role_entry_read_id, $this->role_category_read_id),
            'write' => array($this->role_entry_write_id, $this->role_category_write_id)
            );
        
        $ret = $dv_manager->getDataWithRuleById($entry_id, $rule_id, '*', false);
        
        $data = array();
        foreach($rule_id as $rid) {
            $key = (in_array($rid, $map['read'])) ? 'read' : 'write';
            $data[$key] = (isset($ret[$rid])) ? $ret[$rid] : array();
        }
        
        // echo '<pre>', print_r($data, 1), '</pre>';
        return $data;
    }
    
    
    // $rule = category, entry, $private_rule = read/write
    function getPrivateRolesIds($entry_id, $rule, $private_rule) {

        $dv_manager = &$this->getDataToValueModel();

        $map = array();       
        $map['entry']['read']     = $this->role_entry_read_id;
        $map['entry']['write']    = $this->role_entry_write_id;
        $map['category']['read']  = $this->role_category_read_id;
        $map['category']['write'] = $this->role_category_write_id;
        
        $rule_id = $map[$rule][$private_rule];

        $select = 'dv.user_value, dv.user_value AS id1';
        return $dv_manager->getDataById($entry_id, $rule_id, $select, false);
    }
    
        
    function &getUserRolesIdsChildByUserId($user_role_id) {
        
        if(!is_array($user_role_id)) {
            $user_role_id = array($user_role_id);
        }
        
        $role_manager = &$this->getRoleModel();
        $user_role_ids_temp = $this->role_manager->getChildRoles(false, $user_role_id);
        $user_role_ids = array();
        
        foreach($user_role_ids_temp as $role_id => $role_ids) {
            $user_role_ids[] = $role_id;
            $user_role_ids = array_merge($user_role_ids, $role_ids);
        }
                
        array_unique($user_role_ids);
        //echo '<pre>', print_r($user_role_ids, 1), '</pre>';        
        
        return $user_role_ids;
    }
    
    
    function &getUserRolesIdsChild() {

        if(!$this->user_child_role_ids && $this->user_role_id) {
            $this->user_child_role_ids = &$this->getUserRolesIdsChildByUserId($this->user_role_id);        
        }
        
        // assign value to $this->user_child_role_ids not to call it twice
        if(!$this->user_child_role_ids) {
            $this->user_child_role_ids = array('123456789123456789');
        }
        
        return $this->user_child_role_ids;
    }
    

    // ARTICLES // ------------------------    
    
    function getArticesByIds($ids) {
        $sql = "SELECT * FROM {$this->tbl->entry} WHERE id IN(%s)";
        $sql = sprintf($sql, $ids);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // it is for sake of speed on index page
    function getEntriesSqlIndex($force_index = false) {
        
        $sql = "SELECT 
            e.*,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.active AS category_active,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,
            cat.category_type,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            r.votes AS votes,
            (r.rate/r.votes) AS rating
        
        FROM 
            -- ({$this->tbl->entry} e {$force_index}, 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat
            {$this->sql_params_from})
        
        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e.category_id = cat.id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }    
    
    
    function getEntriesSqlCategory() {

        $sql = "SELECT 
            e.*,
            e_to_cat.sort_order AS real_sort_order,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.active AS category_active,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,
            cat.category_type,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            r.votes AS votes,
            (r.rate/r.votes) AS rating        
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat)
        
        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e_to_cat.entry_id = e.id 
            AND e_to_cat.category_id = cat.id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getEntryCountSql($what = 'category') {
        
        $s = ($this->entry_role_sql_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        if($what == 'category') {
            
            $sql = "SELECT {$s} AS 'num'
            FROM 
                ({$this->tbl->entry} e, 
                {$this->tbl->category} cat,
                {$this->tbl->entry_to_category} e_to_cat)
            {$this->entry_role_sql_from}
            
            WHERE 1
                AND e_to_cat.entry_id = e.id 
                AND e_to_cat.category_id = cat.id
                AND e.active IN ({$this->entry_published_status})
                AND cat.active = 1            
                AND {$this->entry_role_sql_where}
                AND {$this->sql_params}";

        } else {
            
            $sql = "SELECT {$s} AS 'num'
            FROM 
                ({$this->tbl->entry} e, 
                {$this->tbl->category} cat)
            {$this->entry_role_sql_from}
            
            WHERE 1
                AND e.category_id = cat.id
                AND e.active IN ({$this->entry_published_status})
                AND cat.active = 1
                AND {$this->entry_role_sql_where}
                AND {$this->sql_params}";                
        }
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getEntryCount($what = 'category') {
        $sql = $this->getEntryCountSql($what = 'category');
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);    
        return $result->Fields('num');        
    }
    
    
    function getRatingForEntry($entry_ids) {
        $sql = "SELECT
            r.entry_id, 
            r.votes AS votes,
            (r.rate/r.votes) AS rating
        FROM {$this->tbl->rating} r
        WHERE r.entry_id IN({$entry_ids})
        GROUP BY r.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // get all entries for category, 
    // used if category type book, in left menu, in others in category
    function getCategoryEntries($category_id, $entry_id, $limit = -1, $offset = 0) {
        $result = &$this->getCategoryEntriesResult($category_id, $entry_id, $limit, $offset);
        return $result->GetArray();
    }
    
    
    function &getCategoryEntriesResult($category_id, $entry_id, $limit = -1, $offset = 0, $entry_type = true) {

        $private_sql = $this->getPrivateSql('count');
        $entry_type_sql = ($entry_type) ? 'e.entry_type' : 1;

        $sql = "SELECT  
            e.id,
            e.id AS entry_id, 
            e.title, 
            e.url_title,
            e.private,
            {$entry_type_sql}
        FROM 
            ({$this->tbl->entry} e,
            {$this->tbl->entry_to_category} e_to_cat)
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e_to_cat.entry_id = e.id
            AND e_to_cat.category_id = %d
            AND e.id != %d
            AND e.active IN ({$this->entry_published_status})            
            AND {$this->entry_role_sql_where}
            AND {$private_sql}
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";                    
                        
        $sql = sprintf($sql, $category_id, $entry_id);
        
        if($limit !== -1) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));            
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));
        }
        
        // echo $this->getExplainQuery($this->db, $result->sql);    
        return $result;
    }    
    
    
    function &_getEnrtryRelatedIdsResult($entry_id) {         
        $sql = "SELECT entry_id, related_type, related_entry_id 
        FROM {$this->tbl->related_to_entry} r
        WHERE r.entry_id IN (%s) AND r.related_type IN(1,2,3)";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));         
        return $result;
    }
    
    
    // get related assigned for entry, including 2 - inline only
    function &getEnrtryRelatedIds($entry_id) {
        
        $data['inline'] = array();
        $data['attached'] = array();
        
        $result = &$this->_getEnrtryRelatedIdsResult($entry_id);
        while($row = $result->FetchRow()) {
            if(in_array($row['related_type'], array(2,3))) {
                $data['inline'][$row['related_entry_id']] = $row['related_entry_id'];
            }
            
            if(in_array($row['related_type'], array(1,3))) {
                $data['attached'][$row['related_entry_id']] = $row['related_entry_id'];
            }    
        }
        
        return $data;
    }
    
    
    // get related cross reference
    function getEnrtryRelatedCrossReferenceIds($entry_id) {
        $sql = "SELECT entry_id, entry_id AS r FROM {$this->tbl->related_to_entry} r
        WHERE r.related_entry_id IN (%s) AND r.related_type IN(1,3) AND r.related_ref = 1";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    // get all related for entry
    function &getEntryRelated($entry_id, $inline_only = false, $related_id = false) {
        
        $data['inline'] = array();
        $data['attached'] = array();
        
        if ($related_id) {
            $related = array(
                'inline' => array(),
                'attached' => array($related_id => $related_id)
            );
            
        } else {
            $related = &$this->getEnrtryRelatedIds($entry_id);
        
            if(!$inline_only) {
                $related2 = $this->getEnrtryRelatedCrossReferenceIds($entry_id);
                $related['attached'] = array_merge($related['attached'], $related2);            
            }
        }
        
        $related_ids = implode(',', array_merge($related['inline'], $related['attached']));
        //echo '<pre>', print_r($related, 1), '</pre>';
        
        if(!$related_ids) {
            return $data;
        }
                          
        
        $private_sql = $this->getPrivateSql(false);
        $private_sql .= ' AND ' . $this->getCategoryRolesSql(false);
        
        $sql = "SELECT  
            e.id as entry_id,
            e.title, 
            e.url_title,
            e.private,
            e.entry_type,
            cat.id AS category_id,
            cat.private AS category_private            
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat)
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e.id IN($related_ids)
            AND e.category_id = cat.id    
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1    
            AND {$this->entry_role_sql_where}
            AND {$private_sql}
        {$this->entry_role_sql_group}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        while($row = $result->FetchRow()) {
            if(in_array($row['entry_id'], $related['inline'])) {
                $data['inline'][$row['entry_id']] = $row;
            }
            
            if(in_array($row['entry_id'], $related['attached'])) {
                $data['attached'][$row['entry_id']] = $row;
            }    
        }
        
        //echo '<pre>', print_r($data, 1), '</pre>';
        return $data;
    }
    
    
    function &getEntryRelatedInline($entry_id) {
        $data = &$this->getEntryRelated($entry_id, true);
        return $data['inline'];
    }
        
    
    function getEntryTitles($ids) {
        $sql = "SELECT id, title, url_title FROM {$this->tbl->entry} WHERE id IN ($ids)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getEntryTitle($id) {
        $article = $this->getEntryTitles($id);
        return $article[$id]['title'];
    }


    function getEntryData($id) {
        $sql = "SELECT * FROM {$this->tbl->entry} WHERE id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // used in index getEntryListPublished
    function getEntryCategories($entry_id, $category_id) {
        
        $private_sql = $this->getPrivateSql();
        $private_sql .= ' AND ' . $this->getCategoryRolesSql();    
        
        $sql = "SELECT  
            c.id as category_id,
            c.name as title, 
            c.private            
        
        FROM 
            ({$this->tbl->category} c,
            {$this->tbl->entry_to_category} e_to_cat)
        
        WHERE 1
            AND e_to_cat.entry_id = '{$entry_id}'
            AND e_to_cat.category_id != '{$category_id}'
            AND e_to_cat.category_id = c.id
            AND c.active = 1
            AND {$private_sql}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetArray();
    }
    
    
    function getEntryList($limit, $offset, $sql = 'index', $force_index = false) {
        $sql = ($sql == 'index') ? $this->getEntriesSqlIndex($force_index) 
                                 : $this->getEntriesSqlCategory($force_index);

        if($limit != -1) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));
        }
                                                                
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }
    
    
    function getEntryById($entry_id, $category_id) {
        
        $this->setSqlParamsOrder('');
        $this->setSqlParams(sprintf("AND e.id = %d AND cat.id = %d", $entry_id, $category_id));
        $this->setSqlParams('AND ' . $this->getPrivateSql(false));
        $this->setSqlParams('AND ' . $this->getCategoryRolesSql(false));
                   
        $sql = $this->getEntriesSqlCategory('all');
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->FetchRow();
    }

    
    // is entry exists in db
    function isEntryExists($entry_id) {
        $sql = "SELECT id, active FROM {$this->tbl->entry} WHERE id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }    
    
    
    // is entry exists in db and active
    function isEntryExistsAndActive($entry_id, $category_id) {
        $sql = "SELECT  e.id
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat)
        
        WHERE e.id = '{$entry_id}'
            AND cat.id = '{$category_id}'
            AND e_to_cat.entry_id = e.id
            AND e_to_cat.category_id = cat.id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1";

        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function updateBody($entry_id, $body, $comment) {
        $body_index = RequestDataUtil::getIndexText($body);
        
        $sql = "UPDATE {$this->tbl->entry}
            SET body  = '%s',
                body_index  = '%s',
                history_comment = '%s',
                updater_id = '%d'
        WHERE id = %d";
        
        $sql = sprintf($sql, $body, $body_index, $comment, $this->user_id, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function addMetaKeyword($entry_id, $meta_keyword, $delimiter) {
        $sql = "UPDATE {$this->tbl->entry}
            SET meta_keywords = IF(
                LENGTH(meta_keywords) > 0,
                CONCAT(meta_keywords, '{$delimiter}', '{$meta_keyword}'),
                '{$meta_keyword}'
            ),
                date_updated = date_updated
            WHERE id = '$entry_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function updateMetaKeywords($entry_id, $meta_keywords) {
        $sql = "UPDATE {$this->tbl->entry}
            SET meta_keywords = '{$meta_keywords}',
                date_updated = date_updated
            WHERE id = '$entry_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // TAGS // ----------------------------------    
        
    function getTagByEntryId($id) {
        $sql = "SELECT e.id, e.title
        FROM
            ({$this->tbl->tag} e,
             {$this->tbl->tag_to_entry} t_to_e)
        
        WHERE e.id = t_to_e.tag_id
              AND e.active = 1
              AND t_to_e.entry_id IN ({$id})
              AND t_to_e.entry_type = '{$this->entry_type}'";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getTagList($limit, $offset) {
        $sql = "SELECT e.*, COUNT(*) AS entry_num
        FROM
            ({$this->tbl->tag} e,
             {$this->tbl->tag_to_entry} t_to_e)
        WHERE e.id = t_to_e.tag_id
            AND e.active = 1
            AND {$this->sql_params}
        GROUP BY e.id
        {$this->sql_params_order}";
        
        if($limit != -1) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));
        }
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetAssoc();
    }    
    
    
    function getTagCount() {
        $sql = "SELECT COUNT(DISTINCT e.id) AS num
        FROM
            ({$this->tbl->tag} e,
             {$this->tbl->tag_to_entry} t_to_e)
        WHERE e.id = t_to_e.tag_id
            AND e.active = 1
            AND {$this->sql_params}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->Fields('num');
    }    
    
    
    // GLOSSARY // ------------------------------    
    
    function getGlossarySql() {
        $sql = "SELECT * FROM {$this->tbl->glossary} 
        WHERE active = 1 AND {$this->sql_params} {$this->sql_params_order}";
        return $sql;
    }
    
    function getGlossary($limit, $offset) {
        $sql = $this->getGlossarySql();
        if($limit != -1) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));
        }
        
        //echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();        
    }
    
    // when article viewing
    function getGlossaryItems() {
        $sql = "SELECT g.id, g.phrase FROM {$this->tbl->glossary} g WHERE active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    // when article viewing
    function getGlossaryDefinitions($ids = false) {
        $params = ($ids) ? "AND id IN ($ids)" : "";
        
        $sql = "SELECT g.phrase, g.definition AS d, g.display_once AS o
        FROM {$this->tbl->glossary} g
        WHERE active = 1 {$params}
        ORDER BY g.phrase";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
    
    
    function isGlossary() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->glossary} WHERE active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function &getGlossaryLettersResult() {
        $sql = "SELECT phrase FROM {$this->tbl->glossary} WHERE active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;        
    }
    
    
    // COMMENTS // ------------------------------    
    
    function getCommentsNumForEntry($entry_ids) {
        $sql = "SELECT
            c.entry_id, 
            SUM(c.active) AS comment_num 
        FROM {$this->tbl->comment} c 
        FORCE INDEX (entry_id)
        WHERE c.entry_id IN({$entry_ids})
        GROUP BY c.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();
    }    
    
    
    function getCommentListSql($entry_id) {
        $sql = "
        SELECT 
            c.*, 
            UNIX_TIMESTAMP(date_posted) AS ts,
            c.name AS comment_name,
            u.first_name,
            u.last_name,
            u.middle_name,
            u.username,
            u.email,
            u.phone
        FROM 
            {$this->tbl->comment} c
        LEFT JOIN {$this->tbl->user} u ON u.id = c.user_id
        WHERE 1 
            AND c.entry_id = %d
            AND c.active = 1
        ORDER BY date_posted";
        return sprintf($sql, $entry_id);
    }
    
    
    function getCommentListCount($entry_id) {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->comment} c WHERE c.entry_id = %d AND c.active = 1";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->_execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->Fields('num');    
    }
    
    
    function getCommentList($entry_id, $limit, $offset) {
        $sql = $this->getCommentListSql($entry_id);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetArray();
    }
    
    
    // just to know if any comment exists
    function isComments($entry_id) {
        $sql = $this->getCommentListCountSql($entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getCommentPosition($entry_id, $comment_id) {
        $sql = "SELECT COUNT(*) AS position
            FROM {$this->tbl->comment} m
            WHERE id <= %s
                AND entry_id = %s
                AND active = 1
            ORDER BY id";
        $sql = sprintf($sql, $comment_id, $entry_id);

        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('position');
	}
    
    
    // TEMPLATE // -----------------------------
    
    function getTemplate($id) {
        $params = (is_numeric($id)) ? "id = '{$id}'" : "tmpl_key = '{$id}'";
        $sql = "SELECT body FROM {$this->tbl->article_template} WHERE {$params} AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('body');
    }
    
    
    // CUSTOM FIELD // -----------------------------

    function &getCustomDataByEntryId($id, $by_ids = false) {
 
        $sql = "SELECT cd.*, c.*
        FROM 
            ({$this->tbl->custom_field} c,
            {$this->tbl->custom_data} cd)
        
        WHERE cd.entry_id IN ({$id})
        AND c.id = cd.field_id
        AND c.display IN (1,2,3) 
        AND c.active = 1
        
        ORDER BY sort_order";
        
        $result = $this->db->Execute($sql) or die(db_error($sql)); 

        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']][$row['field_id']] = $row;
        }

        if(!$by_ids && isset($data[$id])) {
            $data = $data[$id];
        }

        return $data;
    }

    
    // HISTORY // ------------------------------
    
    function getRevisionNum($entry_id) {
        $sql = "SELECT MAX(revision_num) as 'maxr' FROM {$this->tbl->entry_history} 
        WHERE entry_id = '{$entry_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('maxr')+1;
    }


    // ACTIONS // ------------------------------
    
    function addContactMessage($data) {
    
        $user_id = ($this->user_id) ? $this->user_id : 'NULL';
        $email = (!empty($data['email'])) ? $data['email'] : '';
        $name = (!empty($data['name'])) ? $data['name'] : '';
    
        $sql = "INSERT {$this->tbl->feedback} SET
        user_id = $user_id,
        title = '$data[title]',
        question = '$data[message]',
        attachment = '$data[attachment]',
        email = '$email',
        name = '$name',
        subject_id = '$data[subject_id]',
        date_posted = NOW()";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }        

    
    function deleteContactMessage($message_id) {
        $sql = "DELETE FROM {$this->tbl->feedback} WHERE id = %d";
        $sql = sprintf($sql, $message_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    

    function addVote($entry_id, $rate) {
        
        $action = ($this->isEntryVoted($entry_id)) ? 'UPDATE' : 'INSERT';
        
        if($action == 'UPDATE') {
            $sql = "UPDATE {$this->tbl->rating} SET
            votes    = votes+1, 
            rate     = rate+%d
            WHERE entry_id = %d";
            $sql = sprintf($sql, $rate, $entry_id);
        
        } else {
            $sql = "INSERT {$this->tbl->rating} SET
            entry_id = %d, 
            votes    = 1, 
            rate     = %d";
            $sql = sprintf($sql, $entry_id, $rate);
        }
        
        $this->db->Execute($sql) or die(db_error($sql));
    }

    
    function addVoteFeedback($entry_id, $comment, $rate_value) {
        
        $user_id = ($this->user_id) ? $this->user_id : 'NULL';
        
        $sql = "INSERT {$this->tbl->rating_feedback} SET
        entry_id = %d, 
        user_id  = $user_id,
        comment  = '%s',
        rating = %d";
        $sql = sprintf($sql, $entry_id, $comment, $rate_value);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }
    
    
    function _addHit($entry_id, $entry_type) {
        $sql = "UPDATE {$this->tbl->entry_hits} SET hits = hits+1 WHERE entry_id = %d AND entry_type = %d";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Affected_Rows();
    }
    
    
    function _addHitRecord($entry_id, $entry_type) {
        $sql = "INSERT IGNORE {$this->tbl->entry_hits} SET hits = 1, entry_id = %d, entry_type = %d";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function addView($entry_id, $entry_type = false) {
        
        // skip se bots
        if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) {
            return;
        }
        
        // require_once 'eleontev/Util/FileUtil.php';
        // $str = sprintf("%s %s %s\n", date("Y-m-d H:i:s"), WebUtil::getIP(), $_SERVER['HTTP_USER_AGENT']);
        // FileUtil::write('/home/kbpub/logs/bot_visits.txt', $str, false);
        
        // Affected_Rows( ), Returns the number of rows affected by a update or delete statement. 
        // Returns false if function not supported. Not supported by interbase/firebird currently        
        $entry_type = ($entry_type === false) ? $this->entry_type : $entry_type;
        $result = $this->_addHit($entry_id, $entry_type);
        if(!$result) {
            $this->_addHitRecord($entry_id, $entry_type);
        }
    }
        
    
    function updateArticle($entry_id, $user_id, $data) {
        
        require_once APP_MODULE_DIR . 'tool/tag/inc/TagModel.php';
        $tag_manager = new TagModel();
        
        // meta keywords, $data['tag'] is array of Z id
        $meta_keywords = '';
        if(!empty($data['tag'])) {
            $meta_keywords = $tag_manager->getKeywordsStringByIds(implode(',', $data['tag']));
            $meta_keywords = RequestDataUtil::addslashes($meta_keywords);
        }
        
        $body_index = RequestDataUtil::getIndexText($data['body']);
        $history_comment = (isset($data['history_comment'])) ? $data['history_comment'] : '';
        
        $sql = "UPDATE {$this->tbl->entry} SET 
        title = '%s', 
        body  = '%s',
        body_index  = '%s',
        meta_keywords = '%s',
        meta_description = '%s',
        history_comment = '%s',
        updater_id = '%d'
        WHERE id = %d";
        
        $sql = sprintf($sql, $data['title'], $data['body'], $body_index, $meta_keywords, 
                             $data['meta_description'], $history_comment, $user_id, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
        
        $tag_manager->deleteTagToEntry($entry_id, $this->entry_type);
        if(!empty($data['tag'])) {
            $tag_manager->saveTagToEntry($data['tag'], $entry_id, $this->entry_type);                
        }
    }
    
    
    // if article was already voted 
    function isEntryVoted($entry_id) {
        $sql = "SELECT entry_id FROM {$this->tbl->rating} WHERE    entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('entry_id');
    }
    
    
    // check if user already vore
    function isUserVoted($entry_id) {
        $ret = false;
        if(isset($_SESSION[$this->session_vote_name])) {
            if(in_array($entry_id, $_SESSION[$this->session_vote_name])) {
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    
    function setUserVoted($entry_id) {
        $_SESSION[$this->session_vote_name][] = $entry_id;
    }
    
    
    function setUserViewed($entry_id) {
        $_SESSION[$this->session_view_name][] = $entry_id;
    }
    
    
    // check if user already view
    function isUserViewed($entry_id) {
        $ret = false;
        if(isset($_SESSION[$this->session_view_name])) {
            if(in_array($entry_id, $_SESSION[$this->session_view_name])) {
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    
    function isFlood($sec = 30) {
        $ret = false;
        
        if(!empty($_SESSION['flood_'])) {
            
            $time_to_check = $_SESSION['flood_']+$sec;
            if($time_to_check > time()) {
                $ret = $time_to_check - time();
            } else {
                $_SESSION['flood_'] = time();
            }
            
        } else {
            $_SESSION['flood_'] = time();
        }
        
        return $ret;
    }


    function isEntryActionAllowedByUser($entry_id, $category_id, $entry_private, $cat_private, $entry_status, $action) {
        
        $is_priv = false;
        if(!$this->is_registered || !$this->user_priv_id) {
            return $is_priv;
        }
        
        if($this->isUserPrivIgnorePrivate()) {
            return true;
        }
        
        $auth = new AuthPriv;
        $auth->use_exit_screen = false;
        $auth->setOwnSql($this->getEntryPrivSql($entry_id, $auth->getUserId(), $auth->getPrivId()));
        
        if($auth->check($action, 'kb_entry', $entry_status)) {
            $is_priv = true;
        }
        
        // category private write, at least one category
        if($is_priv) {
            $is_priv = $this->isEntryCategoriesInUserRoles($entry_id, 'write');
            // $is_priv = $this->isEntryInUserRoles($category_id, $cat_private, 'category', 'write');
        }
        
        // entry private write
        if($is_priv) {
            $is_priv = $this->isEntryInUserRoles($entry_id, $entry_private, 'entry', 'write');                        
        }    
        
        return $is_priv;
    }
    
    
    function isEntryUpdatableByUser($entry_id, $category_id, $entry_private, $cat_private, $entry_status) {    
        
        $action = 'update';
        $is_priv = $this->isEntryActionAllowedByUser($entry_id, $category_id, $entry_private, $cat_private, $entry_status, $action);
        
        
        // if user has limitation to set status with some statuses only 
        // we do not show "Quick Update", not sure why...
        if($is_priv) {
            $auth = new AuthPriv;
            if($auth->isPrivStatusActionAny('status', 'kb_entry')) {
                $is_priv = 'no_quick';
            }
            
            // as draft only
            if($auth->isPrivOptional($action, 'draft', 'kb_entry')) {
                $is_priv = 'as_draft';
            }
        }
        
        return $is_priv;
    }
    
    
    function isEntryDeleteableByUser($entry_id, $category_id, $entry_private, $cat_private, $entry_status) {
        
        $action = 'delete';
        $is_priv = $this->isEntryActionAllowedByUser($entry_id, $category_id, $entry_private, $cat_private, $entry_status, $action);
        
        // if user has limitation to delete with some statuses only 
        if($is_priv) {
            $auth = new AuthPriv;
            $auth->use_exit_screen = false;
            if($auth->isPrivStatusActionAny('delete', 'kb_entry')) {
                $is_priv = false;
            }
        }
        
        return $is_priv;
    }
    
    
    function isCategoryUpdatableByUser($category_id) {
        
        $is_priv = false;
        if(!$this->is_registered || !$this->user_priv_id) {
            return $is_priv;
        }
        
        if($this->isUserPrivIgnorePrivate()) {
            return true;
        }
        
        $auth = new AuthPriv;
        $auth->use_exit_screen = false;
        
        if($auth->check('update', 'kb_category')) {
            $is_priv = true;
        }
        
        // category private write
        if($is_priv) {
            $private = $this->categories[$category_id]['private'];
            $is_priv = $this->isEntryInUserRoles($category_id, $private, 'category', 'write');   
        }   
        
        return $is_priv;
    }
    
    
    // $entry_type = category, entry
    function isEntryInUserRoles($entry_id, $entry_private, $entry_type, $private_rule) {
        
        $is_priv = true;
        if($this->isUserPrivIgnorePrivate()) {
            return $is_priv;
        }
        
        $private_rules = $this->private_rule[$private_rule];
        if(in_array($entry_private, $private_rules)) {
            
            $roles = $this->getPrivateRolesIds($entry_id, $entry_type, $private_rule);
            if($roles) {
                $user_roles = &$this->getUserRolesIdsChild();
                $result = array_intersect($roles, $user_roles);
                if(!$result) {
                    $is_priv = false;
                }
            }
        }
        
        return $is_priv;        
    }
    
    
    function isEntryCategoriesInUserRoles($entry_id, $private_rule) {
        
        $is_priv = true;
        if($this->isUserPrivIgnorePrivate()) {
            return $is_priv;
        }

        $sql = "SELECT category_id, category_id as cid 
        FROM {$this->tbl->entry_to_category} e_to_cat WHERE e_to_cat.entry_id = '%d'";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $cats = $result->GetAssoc();

        $skip_cats = $this->getCategoriesNotInUserRole($private_rule, $cats);
        $is_priv = (array_intersect($cats, $skip_cats)) ? false : true;
        
        // echo '<pre>', print_r($cats, 1), '</pre>';
        // echo '<pre>', print_r($skip_cats, 1), '</pre>';
        // var_dump($is_priv);
        
        return $is_priv;
    }
    
    
    // when adding article, topic
    function _isEntryAddingAllowedByUser($priv_area, $category_id = false) {

        $is_priv = true;
        if(!$this->is_registered || !$this->user_priv_id) {
            return false;
        }

        if($this->isUserPrivIgnorePrivate()) {
            return true;
        }

        // priv
        $allowed = AuthPriv::getPrivAllowed($priv_area);
        
        // if draft only
        if($priv_area == 'kb_entry' || $priv_area == 'kb_file') {
            if(in_array('insert', $allowed)) {
                $allowed_ = array($priv_area => $allowed);
                if(AuthPriv::isPrivOptionalStatic('insert', 'draft', $priv_area, $allowed_)) {
                    $is_priv = false;
                }
            }
        }
        
        // category private write
        // it will rewrite and may set it to false - means not allowed write to category
        if($is_priv && $category_id) {
            $private = $this->categories[$category_id]['private'];
            $is_priv = $this->isEntryInUserRoles($category_id, $private, 'category', 'write');
        }

        return $is_priv;
    }
    
    
    function isEntryAddingAllowedByUser($category_id = false) {
        return $this->_isEntryAddingAllowedByUser('kb_entry', $category_id);
    }
    
    
    
/*
    function isEntryLocked($record_id, $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT 1 FROM {$this->tbl->lock}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'
        AND user_id != '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }    
*/
    
    
    function isEntrySubscribedByUser($record_id,  $entry_type = 1, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT 1 FROM {$this->tbl->user_subscription}
        WHERE entry_id IN ({$record_id}) 
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }    
    
    
    function getEntrySubscribedByIds($record_ids,  $entry_type = 1, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT entry_id, entry_id AS 'id' FROM {$this->tbl->user_subscription}
        WHERE entry_id IN ({$record_ids}) 
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }

    
    // true if allowed
    function isSubscribtionAllowed($type = 'entry') {
        
        if(!$this->is_registered) {
            return false;
        } elseif(AuthPriv::isRemote()) { // not allowed for remote
            return false;
        }
        
        $ret = $this->getSetting('allow_subscribe_' . $type);            
        if($ret == 3 && !AuthPriv::getPrivId()) { // for users with priv only
            $ret = false;
        }
        
        return $ret;
    }
    
    
    function getFeaturedInCategory($limit, $offset, $category_id) {
        
        $sql = "SELECT 
            e.*,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.active AS category_active,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,
            cat.category_type, 
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            r.votes AS votes,
            (r.rate/r.votes) AS rating
        
        FROM  
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat,
            {$this->tbl->entry_featured} ef)
        
        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND ef.entry_id = e.id 
            AND ef.category_id = {$category_id}
            AND ef.category_id = cat.id
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})
        ORDER BY ef.sort_order";
        
        if($limit != -1) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));
        }
                                                                
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }

    
    // it could diff in other views
    function setCustomSettings($controller) { }
    

/*
    function isFlood($sec = 30) {
        require_once 'eleontev/Util/Antiflood.php';
        
        $af = new Antiflood(APP_CACHE_DIR);
        $af->af_prefix = "flood_";      
        $af->af_rules = array($sec => 1); // no more 1 message per 30 seconds
        return $af->getTimeout();            
    }
*/
    
    
    function getSortOrder($setting_sort = false) {
        $setting_sort = ($setting_sort) ? $setting_sort : $this->getSetting('entry_sort_order');
        $sort = array('name'         => 'e.title',
                      'sort_order'   => 'e_to_cat.sort_order',
                      'added_desc'   => 'e.date_posted DESC',
                      'added_asc'    => 'e.date_posted ASC',
                      'updated_desc' => 'e.date_updated DESC',
                      'updated_asc'  => 'e.date_updated ASC',
                      'hits_desc'    => 'e.hits DESC',
                      'hits_asc'     => 'e.hits ASC');
        
        return $sort[$setting_sort];    
    }    
    
    
    // MAIL // ---------------------------------
    
    function sendContactNotification($message_id, $vars) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';

        $more = array('id'=>$message_id);
        $vars['link'] = KBClientController::getAdminRefLink('feedback', 'feedback', false, 'answer', $more);
        
        $files = array();
        if($this->getSetting('contact_attachment_email')) {
            foreach($vars['attachment'] as $file) {
                $files[] = $file;
            }
        }
        
        $attachments = array();
        foreach($vars['attachment'] as $file) {
            $attachments[] = basename($file);
        }        
        
        $vars['attachment'] = implode("\n", $attachments);
        
        $m = new KBClientMailSender();
        return $m->sendContactNotification($vars, $files);
    }    
    
    
    function sendRatingNotification($vars) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $m = new KBClientMailSender();
        return $m->sendRatingNotification($vars);
    }    
    
    
    function sendToFriend($vars, $article = array()) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $m = new KBClientMailSender();
        return $m->sendToFriend($vars, $article);
    }    
    
    
    function sendConfirmRegistration($vars, $view) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $view_key = $view->controller->view_key;
        $kb_path = $view->controller->kb_path; // APP_CLIENT_PATH
        
        $code = md5($vars['email']);
        $vars['link'] = sprintf("%s?%s=confirm&ec=%s", $kb_path . 'index.php', $view_key, $code);
        $vars['confirm_link'] = $vars['link'];
        $vars['code'] = $code;
        $vars['site_link'] = $view->getLink('confirm');
        
        $m = new KBClientMailSender();
        return $m->sendConfirmRegistration($vars);
    }
    
    
    function sendRegistrationConfirmed($user_id, $view) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $user = $this->getUserInfo($user_id);
        $vars =& $user;
        $vars['link'] = $view->controller->getLink('login');
        
        $m = new KBClientMailSender();
        return $m->sendRegistrationConfirmed($vars);
    }
    
    
    function sendApproveRegistrationAdmin($user_id) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $user_msg = AppMsg::getMsgs('user_msg.ini', 'public');
        $user = $this->getUserInfo($user_id);
        
        $vars = array();
        $data = array('first_name', 'last_name', 'middle_name', 'email', 'phone', 'user_comment');
        foreach($user as $k => $v) {
            if(in_array($k, $data)) {
                $user_details[] = sprintf('%s: %s', $user_msg[$k . '_msg'], $v);
            }
        }
        
        $vars = array();
        $more = array('id'=>$user['id']);
        $vars['link'] = KBClientController::getAdminRefLink('users', 'user', false, 'update', $more);
        $vars['user_details'] = implode("\n", $user_details);
        
        $m = new KBClientMailSender();
        return $m->sendApproveRegistrationAdmin($vars);
    }    
    
    
    function sendApproveRegistrationUser($user_id) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $user = $this->getUserInfo($user_id);
        
        $m = new KBClientMailSender();
        return $m->sendApproveRegistrationUser($user);
    }        
    
    
    function sendGeneratedPassword($user, $password, $link) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $m = new KBClientMailSender();
        return $m->sendGeneratedPassword($user, $password, $link);
    }
    
    
    function sendResetPasswordLink($user, $code, $link) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';
        
        $m = new KBClientMailSender();
        return $m->sendResetPasswordLink($user, $code, $link);
    }
    
    
    function sendApproveCommentAdmin($comment_id, $entry_id, $category_id, $vars) {
        
        require_once APP_CLIENT_DIR . 'client/inc/KBClientMailSender.php';

        $more = array('id'=>$comment_id);
        $vars['link'] = KBClientController::getAdminRefLink('knowledgebase', 'kb_comment', false, 'update', $more);
        
        $vars['entry_id'] = $entry_id;
        $vars['category_id'] = $category_id;
        $vars['message'] = $vars['comment'];
        
        $m = new KBClientMailSender();
        return $m->sendApproveCommentAdmin($vars);
    }
    
      
    function __sleep(){
        //echo 'This method was called prior to serializing the object!<br />';
        return array_keys(get_object_vars($this));       

    }
    
    // define '__wakeup()' method
    function __wakeup(){
        //parent::__construct();
        $reg =& Registry::instance();
        $this->db =& $reg->getEntry('db');        
        //echo 'This method was called after unserializing the object!<br />';
    }
}


class KBClientQuickModel extends BaseModel
{

    var $tables = array('entry'=>'kb_entry', 'topic' => 'forum_entry');
    
    
    function getEntryTitles($entry_id, $table) {
        $model = new KBClientQuickModel();
        $sql = "SELECT title, url_title FROM {$model->tbl->$table} WHERE id = %d";
        $sql = sprintf($sql, $entry_id);
        $result =& $model->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
}
?>