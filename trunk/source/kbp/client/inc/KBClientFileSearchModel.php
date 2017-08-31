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

class KBClientFileSearchModel extends KBClientFileModel
{    
    
    var $select_type = 'index';
    

    function getEntriesSqlIndex() {
        
        $sql = "
        SELECT 
            e.*,
            1 as filetext,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
            
        WHERE 1
            AND cat.id = e.category_id
            AND cat.active = 1
            AND e.active IN ({$this->entry_published_status})
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }    
    
    
    function getEntryCountSqlIndex() {
        
        $sql = "SELECT COUNT(*)
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND cat.id = e.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    

    function getEntriesSqlCategory() {
        
        $sql = "
        SELECT 
            e.*,
            1 as filetext,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat 
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
            
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND cat.active = 1
            AND e.active IN ({$this->entry_published_status})
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        GROUP BY e.id
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getEntryCountSqlCategory() {
        
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS num
        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat 
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }    
    
    
    function getEntryList($limit, $offset) {
        $sql = ($this->select_type == 'index') ? $this->getEntriesSqlIndex() : $this->getEntriesSqlCategory();
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetArray();
    }
    
    
    function getEntryCount() {
        $sql = ($this->select_type == 'index') ? $this->getEntryCountSqlIndex() : $this->getEntryCountSqlCategory();
        $data =  $this->db->GetCol($sql) or die(db_error($sql));
        return $data[0];
    }
}
?>