<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class KBClientFileModel extends KBClientModel_common
{
    var $tbl_pref_custom = 'file_';
    
    var $custom_tables = array('user',
                               'feedback',
                               'list_value',
                               'file_entry',
                               'file_entry_to_category',
                               'data_to_value'=>'data_to_user_value',
                               'data_to_value_string'=>'data_to_user_value_string',
                               'glossary'=>'kb_glossary',
                               'user_subscription',
                               'entry_hits',
                               'custom_field',
                               'custom_field_to_category',
                               'custom_field_range_value',
                               'tag',
                               'tag_to_entry');
    
    
    // rules id in data to user rule
    var $role_entry_read_id = 102;
    var $role_entry_write_id = 106;    
    var $role_category_read_id = 2;
    var $role_category_write_id = 6;
    
    var $entry_list_id = 2; // id in list statuses 
    var $entry_type = 2; // entry type in entry_hits, entry_schedule  
    var $entry_type_cat = 12; // entry type for category
    
    
    // CATEGORIES
    
    function getCategoriesSql($sort, $type_param = 'all') {
        $sql = "SELECT id, parent_id, name, sort_order, private
        FROM {$this->tbl->category} c FORCE INDEX ( sort_order )
        WHERE c.active = 1
        ORDER BY {$sort}";

        return $sql;
    }
    
    
    function getCategoryType($category_id) {
        return 1;
    }    
    
    
    // FILES // ------------------------    
    
    // it is for sake of speed on index page
    function getEntriesSqlIndex($force_index = false) {
        
        $sql = "SELECT 
            e.*,
            1 as filetext,
            e.description AS body, 
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated
        
        FROM 
            -- ({$this->tbl->entry} e {$force_index}, 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat)
            
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND cat.id = e.category_id
            AND cat.active = 1
            AND e.active IN ({$this->entry_published_status})            
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
            1 as filetext,
            e.description AS body, 
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat)
            
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND cat.active = 1
            AND e.active IN ({$this->entry_published_status})
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}    
        {$this->entry_role_sql_group}    
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getSortOrder($setting_sort = false) {
        $setting_sort = ($setting_sort) ? $setting_sort : $this->getSetting('entry_sort_order');
        $sort = array('name'         => 'e.title',
                      'filename'     => 'e.filename',
                      'sort_order'   => 'e_to_cat.sort_order',
                      'added_desc'   => 'e.date_posted DESC',
                      'added_asc'    => 'e.date_posted ASC',
                      'updated_desc' => 'e.date_updated DESC',
                      'updated_asc'  => 'e.date_updated ASC',
                      'hits_desc'    => 'e.downloads DESC',
                      'hits_asc'     => 'e.downloads ASC');
        
        return $sort[$setting_sort];    
    }        
}
?>