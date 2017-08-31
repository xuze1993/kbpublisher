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

class KBClientTroubleModel extends KBClientModel_common
{
    var $tbl_pref_custom = 'trouble_';
    var $tables = array('entry', 'category', 'entry_to_category', 'entry_step', 
                        'rating', 'rating_feedback', 'comment', 'related_to_entry',
                        'article_to_step', 'attachment_to_step',
                        'attachment_to_entry' => 'attachment_to_step',
                        'custom_data');
    
    var $custom_tables = array('list_value',
                               'user',
                               'user_company', 
                               'file_entry',
                               'data_to_value'=>'data_to_user_value',
                               'data_to_value_string'=>'data_to_user_value_string',
                               'kb_entry',
                               'kb_category',
                               'article_template',
                               'entry_hits',
                               'custom_field',
                               'custom_field_to_category',
                               'custom_field_range_value');
    
    
    // rules id in data to user rule
    var $role_entry_read_id = 109;    
    var $role_entry_write_id = 110;    
    
    var $entry_list_id = 7; // id in list statuses 
    var $entry_type = 4; // entry type in entry_hits
        
    var $session_vote_name = 'kb_trouble_vote_';  
    var $session_view_name = 'kb_trouble_view_';
    
    
    // CATEGORIES
    
    function getCategoryType($category_id) {
        return 1;
    }    
    
    
    // ENTRIES // ------------------------    
    
    // it is for sake of speed on index page
    function getEntriesSqlIndex($force_index = false) {
        
        $sql = "SELECT 
            e.*,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            r.votes AS votes,
            (r.rate/r.votes) AS rating
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat)
            
        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id            
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
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            r.votes AS votes,
            (r.rate/r.votes) AS rating
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat)
            
        LEFT JOIN {$this->tbl->rating} r ON e.id = r.entry_id    
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
    
    
    function getCategoryEntries($category_id, $entry_id, $limit = -1, $offset = 0) {
        $result = &$this->getCategoryEntriesResult($category_id, $entry_id, $limit, $offset, false);
        return $result->GetArray();
    }
    
    
    function getEntry($id) {
        $sql = "SELECT * FROM {$this->tbl->entry} WHERE id = %d";
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();        
    }
    
    
    function getStepsNum($id) {
        $sql = "SELECT COUNT(*) as num FROM {$this->tbl->entry_step} WHERE entry_id = %d";
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
    
    
    function getStepById($id) {
        $sql = "SELECT s.*, e.title as entry_title, r.related_entry_id 
        FROM ({$this->tbl->entry_step} s,
            {$this->tbl->entry} e)
            
        LEFT JOIN {$this->tbl->related_to_entry} r ON r.step_id = %d
        WHERE s.entry_id = e.id 
        AND s.id = %d";
        
        $sql = sprintf($sql, $id, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();        
    }
    
    
    function getEntryByStepId($id) {
        $sql = "SELECT e.* 
        FROM ({$this->tbl->entry_step} s,
            {$this->tbl->entry} e)
        WHERE e.id = s.entry_id 
        AND s.id = %d";
        
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();        
    }
    
    
    function getSteps($entry_id, $parent_id = false) {
        $sql = "SELECT *, id 
        FROM {$this->tbl->entry_step}
        WHERE entry_id = '{$entry_id}' 
        AND active = 1";
        
        if ($parent_id !== false) {
            $sql .= " AND parent_id = '{$parent_id}'";
        }
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();  
    }  
        
}
?>