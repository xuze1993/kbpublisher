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


class TagModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'tag', 'tag', 'tag_to_entry',
        'tag_to_entry_update', 'entry_task');
    
    var $entry_type = 22;
    
    static $keyword_delimeter = ','; // for meta_keywords field


    public function getKeywordDelimeter() {
        return self::$keyword_delimeter;
    }

 
    function isInUse($ids) {
        $sql = "SELECT 1 FROM {$this->tbl->tag_to_entry} WHERE tag_id IN ($ids)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
     }
    
    
    function getReferencedEntriesNum($ids) {
        $sql = "SELECT tag_id, entry_type, COUNT(*) as num
            FROM {$this->tbl->tag_to_entry} 
            WHERE tag_id IN ($ids) 
            GROUP BY entry_type, tag_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $data = array();
        while($row = $result->FetchRow()) {  
            $data[$row['tag_id']][$row['entry_type']] = $row['num'];
        }

        return $data;
    }
    
    
    function getSuggestList($limit = false, $offset = 0) {
        $sql = "SELECT t.*, COUNT(te.tag_id) as num
                FROM {$this->tbl->table} t
                
                LEFT JOIN {$this->tbl->tag_to_entry} te
                ON t.id = te.tag_id
                
                GROUP BY t.id
                ORDER BY num DESC, t.title";
                
        if ($limit) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));           
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));        
        }
        
        return $result->GetArray();
    }
    
    
    function isTagExists($title, $id = false) {
        $sql = "SELECT id FROM {$this->tbl->table} WHERE title = '{$title}'";
        $sql .= ($id) ? sprintf(' AND id != %d', $id) : '';
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }    
        
        
    // ACTION LOG // --------------------

    function addTagSyncTask($tags, $action) {
    
        require_once 'eleontev/SQL/MultiInsert.php';
    
        $data = array();
        $tags = (is_array($tags)) ? $tags : array($tags);
        $rule_id = array_search('sync_meta_keywords', $this->entry_task_rules);
        foreach($tags as $tag_id) {
            $data[$tag_id] = array($rule_id, $tag_id, $action);
        }
    
        if($data) {  
            $sql = "REPLACE {$this->tbl->entry_task} (rule_id, entry_id, value1) VALUES ?";      
            $sql = MultiInsert::get($sql, $data);
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    function isTagUpdateTask() {
        $rule_id = array_search('update_meta_keywords', $this->entry_task_rules);
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->entry_task} 
        WHERE rule_id = '{$rule_id}' AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    // DELETE // --------------------- 
    
    function deleteTag($record_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // function deleteTagToEntries($record_id) {
    //     $sql = "DELETE FROM {$this->tbl->tag_to_entry} WHERE tag_id IN ({$record_id})";
    //     return $this->db->Execute($sql) or die(db_error($sql));
    // }
    
    
    function delete($record_id) {
        $record_id = $this->idToString($record_id);
        $this->deleteTag($record_id);
        // $this->deleteTagToEntries($record_id);
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);
    }

 
    // ENTRY TO TAG // ------------------    
    
    static function parseTagOnAdding($tag) {
        // $tag = _strtolower($tag);
        $tag = trim(preg_replace("#['\",]#u", " ", $tag));
        return $tag;
    }
        
    
    function parseTagString($str) {
        
        $tags = array();

        $pattern = '#"(.*?)"#';
        preg_match_all($pattern, $str, $match);

        if($match) {
            $tags = $match[1];
            $str = str_replace($match[0], '', $str);
        }

        $pattern = '#[\s+]#';
        $match = preg_split($pattern, $str, -1, PREG_SPLIT_NO_EMPTY);
        if($match) {
            $tags = array_merge($tags, $match);
        }
        
        foreach($tags as $k => $tag) {
            $tags[$k] = $this->parseTagOnAdding($tag);
        }        
        
        return $tags;
    }
    
    
    function getTagByIds($ids) {
        $sql = "SELECT id, title FROM {$this->tbl->tag} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // return $result->GetArray();
        return $result->GetAssoc();
    }


    function getKeywordsStringByIds($ids) {
        $tags = $this->getTagByIds($ids);
        // return $this->getValuesString($tags, 'title', $this->getKeywordDelimeter()); 
        return implode($this->getKeywordDelimeter(), $tags);
    }


    function getTagByEntryId($record_id) {
        
        $sql = "
        SELECT 
            t.id, 
            t.title 
        FROM 
            {$this->tbl->tag} t, 
            {$this->tbl->tag_to_entry} te
        WHERE 1
            AND te.entry_id IN ({$record_id}) 
            AND te.tag_id = t.id
            AND te.entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getTagToEntry($record_id) {
        
        $sql = "
        SELECT 
            te.entry_id,
            t.id,
            t.title 
        FROM 
            {$this->tbl->tag} t, 
            {$this->tbl->tag_to_entry} te
        WHERE 1
            AND te.entry_id IN ({$record_id}) 
            AND te.tag_id = t.id
            AND te.entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
     
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']][$row['id']] = $row['title'];
        }
        
        return $data;        
    }    

        
    function &getTagByTitleResult($tags) {
        
        $tags = (is_array($tags)) ? $tags : array($tags);
        $tags = array_map('trim', $tags);        
        $tags = implode("','", $tags);
        
        $sql = "SELECT * FROM {$this->tbl->tag} WHERE title IN ('{$tags}')";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
    

    function getTagIds($tags) {
        $result =& $this->getTagByTitleResult($tags);
        return array_keys($result->GetAssoc());
    }
    
    
    function getTagTitles($tags) {
        $result =& $this->getTagByTitleResult($tags);
        return $result->GetAssoc();
    }


    function getTagArray($tags) {
        $result =& $this->getTagByTitleResult($tags);
        return $result->GetArray();
    }
    
    
    function saveTag($tags) {
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        $tags = (is_array($tags)) ? $tags : array($tags);
        $data = array();
        foreach($tags as $title) {
            if(!$this->isTagExists($title)) {
                $data[] = array($title);
            }
        }
        
        if($data) {
            $sql = MultiInsert::get("INSERT {$this->tbl->tag} (title, date_posted) VALUES ?", $data, 'NOW()');
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    function saveTagToEntry($values, $record_id, $entry_type = false) {
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        if(empty($values)) {
            return;
        }
        
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $tags = array();
        foreach($record_id as $entry_id) {
            foreach($values as $tag_id) {
                $tags[] = array($entry_id, $entry_type, $tag_id);
            } 
        }
        
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->tag_to_entry} (entry_id, entry_type, tag_id) 
                                    VALUES ?", $tags);
                                        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteTagToEntry($entry_id, $entry_type = false, $tag_id = false) {
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;  
        
        $sql = "DELETE FROM {$this->tbl->tag_to_entry}
            WHERE entry_id IN ({$entry_id})
            AND entry_type = '{$entry_type}'";
            
        if ($tag_id) {
            $sql .= ' AND tag_id = ' . $tag_id;
        }
            
        return $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>