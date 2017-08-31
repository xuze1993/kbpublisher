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

class KBClientSearchModel_mysql extends KBClientSearchModel
{    
        
    var $sql_params_group;
    var $fmodel;

    // by default if no category specified KBP will search in "main" category
    // set it to "category" to always search in all possible categories
    // please note some performace issues is possible, queries could take more time
    // index or category
    var $select_type = 'index';
    
    var $count_limit = 100; // to limit sql in count entries
        
    
    
    // ARTICLE // ------------------
    
    function getArticleSqlSelect() {
        
        $select = "
            e.id AS 'id_',
            e.id AS 'id',
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,        
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords, -- fix for api  
            #GROUP_CONCAT(cat.name, '-' ,cat.id, '-', cat.private) AS categories,
            {$this->sql_params_select}";
        
        return $select;    
    }
    
    
    function getArticleSqlIndex() {
        $select = $this->getArticleSqlSelect();
        return $this->_getEntriesSqlIndex($select, $this);
    }
    
    
    function getArticleCountSqlIndex() {
        return $this->_getEntryCountSqlIndex($this);
    }
    
    
    // in category select we need distinct or group by
    function getArticleSqlCategory() {
        $select = $this->getArticleSqlSelect();        
        return $this->_getEntriesSqlCategory($select, $this);
    }
        

    function getArticleCountSqlCategory() {
        return $this->_getEntryCountSqlCategory($this);
    }
    
    
    function getArticleDataByIds($ids) {
        $sql = "SELECT id, title, body, private, url_title, entry_type, hits
        FROM {$this->tbl->entry} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function &getArticleList($limit, $offset) {
        $sql = ($this->select_type == 'index') ? $this->getArticleSqlIndex() 
                                               : $this->getArticleSqlCategory();
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        $rows = $result->GetAssoc();
        
        if($rows) {
            $ids = array_keys($rows);
            $ids_str = implode(',', $ids);
            $rows2 = $this->getArticleDataByIds($ids_str);
            foreach($ids as $id) {
                $rows[$id] = $rows2[$id] + $rows[$id];
            }
        }
        
        return $rows;
    }
    
    
    function getArticleCount() {
        $sql = ($this->select_type == 'index') ? $this->getArticleCountSqlIndex() 
                                               : $this->getArticleCountSqlCategory();
                                               
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $sql);
    
        return $result->fields['num'];
    }
    
    
    function getArticleSearchData($limit, $offset) {
        $count = $this->getArticleCount();
        $rows = $this->getArticleList($limit, $offset);
        
        return array($count, $rows);
    }
    
    
    // FILE // --------------------
    
    function getFilesSqlSelect() {
        
        $select = "
            e.id AS 'id_',
            e.id,
            e.title,
            e.description,
            e.filename,
            e.filesize,
            e.private,
            e.downloads,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords, -- fix for api
            {$this->sql_params_select}";
        
        return $select;    
    }
        
    
    function getFilesSqlIndex($manager) {
        $select = $this->getFilesSqlSelect();
        return $this->_getEntriesSqlIndex($select, $manager);    
    }    
    
    
    function getFileCountSqlIndex($manager) {
        return $this->_getEntryCountSqlIndex($manager);
    }
    

    function getFilesSqlCategory($manager) {
        $select = $this->getFilesSqlSelect();
        return $this->_getEntriesSqlCategory($select, $manager);
    }
    
    
    function getFileCountSqlCategory($manager) {
        return $this->_getEntryCountSqlCategory($manager);
    }
    
    
    function getFileList($limit, $offset, $manager) {
        $sql = ($this->select_type == 'index') ? $this->getFilesSqlIndex($manager) 
                                               : $this->getFilesSqlCategory($manager);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
    
        //echo $this->getExplainQuery($this->db, $result->sql);    
        return $result->GetArray();
    }
    
    
    function getFileCount($manager) {
        $sql = ($this->select_type == 'index') ? $this->getFileCountSqlIndex($manager) 
                                               : $this->getFileCountSqlCategory($manager);
                                               
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $sql);
        
        return $result->fields['num'];
    }
    
    
    function getFileSearchData($limit, $offset, $manager) {
        $count = $this->getFileCount($manager);
        $rows = $this->getFileList($limit, $offset, $manager);
        
        return array($count, $rows);
    }

    
    
    // NEWS // --------------------------
    
    function getNewsSqlIndex($manager) {
        
        $sql = "
        SELECT 
            e.id AS 'id_',
            e.*,
            YEAR(e.date_posted) AS 'category_id',
            0 AS 'category_private',
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_posted) AS ts_updated,
            e.date_posted AS date_updated,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->news} e
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})
        GROUP BY e.id
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }


    function getNewsCountSqlIndex() {
        
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS num        
        FROM 
            ({$this->tbl->news} e
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }

    
    function getNewsList($limit, $offset, $manager) {
        $sql = $this->getNewsSqlIndex($manager);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }
    
    
    function &getNewsCount($manager) {
        $sql = $this->getNewsCountSqlIndex($manager);
        
        // echo $this->getExplainQuery($this->db, $sql);
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        
        return $result->fields['num'];
    }
    
    
    function getNewsSearchData($limit, $offset, $manager) {
        $count = $this->getNewsCount($manager);
        $rows = $this->getNewsList($limit, $offset, $manager);
        
        return array($count, $rows);
    }
    
    
    // FORUM // ------------------------------------     

	function getForumEntriesSqlIndex($manager) {
		
		$sql = "
		SELECT 
			e.id AS 'id_',
			e.*,
			cat.id AS category_id,
			cat.name AS category_name,
			cat.private AS category_private,	
			UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
			UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
			{$this->sql_params_select}
		
		FROM 
			({$manager->tbl->entry} e, 
			{$manager->tbl->category} cat
			{$this->sql_params_from})
			
		{$this->entry_role_sql_from}
			
		WHERE 1
			AND cat.id = e.category_id
			AND e.active IN ({$this->entry_published_status})
			AND cat.active = 1
			AND {$this->entry_role_sql_where}
			AND {$this->sql_params}
		{$this->entry_role_sql_group}
		{$this->sql_params_group}
		{$this->sql_params_order}";
		
		//echo "<pre>"; print_r($sql); echo "</pre>";
		//echo '<pre>', print_r('==============================', 1), '</pre>';
		return $sql;
	}
	
	
	// in category select we need distinct or group by
	function getForumEntriesSqlCategory($manager) {
		
		$sql = "
		SELECT 
			e.id AS 'id_',
			e.*,
			cat.id AS category_id,
			cat.name AS category_name,
			cat.private AS category_private,			
			UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
			UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
			{$this->sql_params_select}
		
		FROM 
			({$manager->tbl->entry} e,
			{$manager->tbl->category} cat,
			{$manager->tbl->entry_to_category} e_to_cat
			{$this->sql_params_from})
			
		{$this->entry_role_sql_from}
			
		WHERE 1
			AND e.id = e_to_cat.entry_id
			AND cat.id = e_to_cat.category_id
			AND e.active IN ({$this->entry_published_status})
			AND cat.active = 1
			AND {$this->entry_role_sql_where}
			AND {$this->sql_params}
		GROUP BY e.id
		{$this->sql_params_order}";
		
		//echo "<pre>"; print_r($sql); echo "</pre>";
		return $sql;
	}


	function getForumEntryList($limit, $offset, $manager) {
		$sql = ($this->select_type == 'index') ? $this->getForumEntriesSqlIndex($manager) 
		                                       : $this->getForumEntriesSqlCategory($manager);
		$result =& $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
		
		//echo $this->getExplainQuery($this->db, $result->sql);
		return $result->GetArray();
	}
    
    
    function getForumTopicCountSqlIndex($manager) {
        return $this->_getEntryCountSqlIndex($manager);
    }
    
    
    function getForumTopicCountSqlCategory($manager) {
        return $this->_getEntryCountSqlCategory($manager);
    }
    
    
    function getForumTopicCount($manager) {
        $sql = ($this->select_type == 'index') ? $this->getForumTopicCountSqlIndex($manager) 
                                               : $this->getForumTopicCountSqlCategory($manager);
                                               
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $sql);
        
        return $result->fields['num'];
    }
    
    
    function getForumMessageCountSqlIndex($manager) {
        
        $sql = "SELECT COUNT(DISTINCT(m.id)) AS 'num'
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE 1
            AND cat.id = e.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }

    
    function getForumMessageCountSqlCategory($manager) {
        
        $sql = "SELECT COUNT(DISTINCT(m.id)) AS 'num' 
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
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
    
    
    function getForumMessageCount($manager) {
        $sql = ($this->select_type == 'index') ? $this->getForumMessageCountSqlIndex($manager) 
                                               : $this->getForumMessageCountSqlCategory($manager);
                                               
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $sql);
        
        return $result->fields['num'];
    }
    
    
    function getForumSearchData($limit, $offset, $manager, $count_method) {
        $count = $this->$count_method($manager);
        $rows = $this->getForumEntryList($limit, $offset, $manager);
        
        return array($count, $rows);
    }
    
    
    // TROUBLE // --------------------  
    
    function getTrouableSqlSelect() {
        $select = "
            e.id AS 'id_',
            e.*,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            {$this->sql_params_select}";

        return $select;
    }


    function getTroubleSqlIndex($manager) {
        $select = $this->getTrouableSqlSelect();
        return $this->_getEntriesSqlIndex($select, $manager);
    }    
    
    
    function getTroubleCountSqlIndex($manager) {
        return $this->_getEntryCountSqlIndex($manager);
    }
    
    
    function getTroubleSqlCategory($manager) {
        $select = $this->getTrouableSqlSelect();
        return $this->_getEntriesSqlCategory($select, $manager);
    }
    
    
    function getTroubleCountSqlCategory($manager) {
        return $this->_getEntryCountSqlCategory($manager);
    }
    
    
    function getTroubleList($limit, $offset, $manager) {       
        $sql = ($this->select_type == 'index') ? $this->getTroubleSqlIndex($manager) 
                                               : $this->getTroubleSqlCategory($manager);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
    
        //echo $this->getExplainQuery($this->db, $result->sql);    
        return $result->GetArray();
    }
    
    
    function getTroubleCount($manager) {
        $sql = ($this->select_type == 'index') ? $this->getTroubleCountSqlIndex($manager) 
                                               : $this->getTroubleCountSqlCategory($manager);
        
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $sql);
        
        return $result->fields['num'];
    }


    // PRIVATE // ------------------
    
    function _getEntriesSqlIndex($select, $manager) {
        
        // 2016-07-29 eleontev 
        // fix to search in all categories, not in default only
        return $this->_getEntriesSqlCategory($select, $manager);
        
        
        $sql = "
        SELECT {$select}
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND cat.id = e.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        GROUP BY e.id
        {$this->entry_role_sql_group}
        {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }
    
    
    function _getEntriesSqlCategory($select, $manager) {
        
        $sql = "
        SELECT {$select}
        FROM 
            ({$manager->tbl->entry} e,
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        GROUP BY e.id
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function _getEntryCountSqlIndex($manager) {
        
        // 2016-07-29 eleontev 
        // fix to search in all categories, not in default only
        return $this->_getEntryCountSqlCategory($manager);
        
        
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS 'num'
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE 1
            AND cat.id = e.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }

    
    function _getEntryCountSqlCategory($manager) {
        
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS 'num' 
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
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
    
    
    // GENERATOR // ------------------
    
    function filterById($ids) {
        $where = sprintf("AND e.id IN (%s)", implode(',', $ids));
        $this->setSqlParams($where);
    }
    
    
    function filterByTitle($str) {
        
        if($this->values['in'] == 'file') {
            
            $select = "MATCH (e.title, e.filename_index) AGAINST ('$str') AS score";
            $this->setSqlParamsSelect($select);
        
            $where = "AND MATCH (e.title, e.filename_index) AGAINST ('$str' IN BOOLEAN MODE)";
            $this->setSqlParams($where);
            
        } else {
            
            $select = "MATCH (e.title) AGAINST ('$str') AS score";
            $this->setSqlParamsSelect($select);
        
            $where = "AND MATCH (e.title) AGAINST ('$str' IN BOOLEAN MODE)";
            $this->setSqlParams($where);
        }
    }
    
    
    function filterByTag($tag_ids) {
        $tag_ids = ($tag_ids) ? implode(',', $tag_ids) : 0;
            
        $w = array();
        $w[] = "AND tag_to_e.entry_id = e.id";
        $w[] = "AND tag_to_e.entry_type = '{$this->entry_type}'";
        $w[] = "AND tag_to_e.tag_id IN ({$tag_ids})";                
          
        $where = implode("\n", $w);
        $this->setSqlParams($where);
        
        $from = sprintf(', %s %s', $this->tbl->tag_to_entry, 'tag_to_e');
        $this->setSqlParamsFrom($from);
    }
    
    
    function filterByAuthor($ids) {
        $where = sprintf("AND e.author_id IN (%s)", implode(',', $ids));
        $this->setSqlParams($where);
    }
    
    
    function filterArticleByAllFields($str) {
		$f = 'e.title, e.body_index, e.meta_keywords, e.meta_description';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);            
    }
    
    
    function filterFileByAllFields($str) {
		$f = 'e.title, e.filename_index, e.meta_keywords, e.description, e.filetext';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);
    }
    
    
    function filterByFilename($str) {
        $str = str_replace('*', '%', $str);
        $where = "AND e.filename LIKE '$str'";
        $this->setSqlParams($where);
    }
	
	
    function filterByArticleAttachments($str) {
		$f = "f.title, f.filename_index, f.meta_keywords, f.description, f.filetext";
	
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
    
        $from = sprintf(', %s %s', $this->tbl->file_entry, 'f');
        $from .= sprintf(', %s %s', $this->tbl->attachment_to_entry, 'a');
		$this->setSqlParamsFrom($from);
	
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $where .= " AND e.id = a.entry_id AND f.id = a.attachment_id";
        $where .= " AND f.active = 1";
        $where .= " AND a.attachment_type IN(1,3)"; // attachment only
        $this->setSqlParams($where); 
	}
	
    
    function filterNewsByAllFields($str) {
		$f = 'e.title, e.body_index, e.meta_keywords';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);    
    }
    
    
    function filterForumByAllFields($str, $topic_id) {
        $select = "MATCH (m.message_index) AGAINST ('$str') AS score, m.id AS message_id, m.message";
        $this->setSqlParamsSelect($select);
        
		$w[] = "AND MATCH (m.message_index) AGAINST ('$str' IN BOOLEAN MODE)";
		$w[] = "AND e.id = m.entry_id";
        
        if ($topic_id) {
            $w[] = 'AND m.entry_id = ' . $topic_id;
        } 
	
		$where = implode("\n", $w);
        $this->setSqlParams($where);
        
        $from = sprintf(', %s %s', $this->tbl->forum_message, 'm');
        $this->setSqlParamsFrom($from);
        
		//$sql['group'] = 'e.id';
        
    }
    
    
    function filterEmpty($entry_type) {
        $select = "UNIX_TIMESTAMP(e.date_updated) AS score";
        
        if($entry_type == 'news') {
            $select = "UNIX_TIMESTAMP(e.date_posted) AS score";                
        }
        
        if($entry_type == 'forum') {
            $select = 'm.id AS message_id, m.message, UNIX_TIMESTAMP(e.date_posted) AS score';
            
            $from = sprintf(', %s %s', $this->tbl->forum_message, 'm');
            $this->setSqlParamsFrom($from);
        }
        
        $this->setSqlParamsSelect($select);
    }
    
    
    function filterByEntryType($c) {
        $c = implode(',', $c);
        $where = "AND e.entry_type IN($c)";
        $this->setSqlParams($where);
    }
    
    
    function filterByCategory($c) {
        $csql = implode(',', $c);
        
        //$sql = "e_to_cat.category_id IN($c)";
        $where = "AND cat.id IN($csql)";
        $this->setSqlParams($where);
    }
    
    
    function filterByCustomDate($entry_type) {
        
        if(isset($this->values['date_from'])) {
            $min = $this->values['date_from'];
            $min = (int) $min;
        }
        
        if(isset($this->values['date_to'])) {
            $max = $this->values['date_to'];
            $max = (int) $max;    
        }
        
        $field = $this->_getFilterDateField($entry_type);
                                
        // we have date active
        if(!empty($max) && !empty($min)) {
            $where = "AND $field BETWEEN '{$min}' AND '{$max}'";
        
        } elseif(!empty($min)) {
            $where = "AND $field >= '{$min}'";
        
        } elseif(!empty($max)) {
            $where = "AND $field <= '{$max}'";
        }
        
        $this->setSqlParams($where);
    }
    
    
    function filterByDate($entry_type, $match) {
        $field = $this->_getFilterDateField($entry_type);
        $period = strtoupper($match[2]);
        $sql = sprintf("AND %s >= DATE_SUB(CURDATE(), INTERVAL %s %s)", $field, $match[1], $period);
        $this->setSqlParams($sql);
    }
    
    
    function _getFilterDateField($entry_type) {
        // added or updated
        @$field = ($this->values['pv'] == 'u') ? 'e.date_updated' : 'e.date_posted';        
        return $field;
    }


    function setCustomFieldParams($manager) {
        $v = RequestDataUtil::stripVars($this->values['custom']);
        $custom_sql = $this->cf_manager->getCustomFieldSql($v);
        
        $this->setSqlParams('AND ' . $custom_sql['where']);
        $this->setSqlParamsJoin($custom_sql['join']);
    }
    
    
    function setOrderParams($values, $entry_type = 'article') {
    
        $val = 'ORDER BY e.date_updated DESC';
        if($entry_type == 'news') {
            $val = 'ORDER BY e.date_posted DESC';
        }
        
        if(KBClientSearchHelper::isOrderByScore($values)) {
            $val = 'ORDER BY score DESC';
                
            //When MATCH() is used in a WHERE clause, 
            //rows returned are automatically sorted with the highest relevance first.
            // looks like it does not work on MySQL 4
            //$val = '';
        }
        
        $this->setSqlParamsOrder($val);
    }
    
    
    // HIGHLIGHTING // ------------------
    
    // function highlight($title, $body, $query, $summary_limit) {
    //     $ret = array();
    //     $ret['title'] = DocumentParser::getTitleSearch($title, $query);
    //     $ret['body'] = DocumentParser::getSummarySearch($body, $query, $summary_limit);
    //     return $ret;
    // }
    
    
    function highlightTitle($str, $query, $keywords) {
        return DocumentParser::getTitleSearch($str, $query);
    }
    
    
    function highlightBody($str, $query, $keywords, $limit) {
        return DocumentParser::getSummarySearch($str, $query, $limit);
    }
    

    function getKeywords() {
        return false;
    }
    
}
?>