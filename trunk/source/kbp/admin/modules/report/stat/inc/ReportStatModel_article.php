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


class ReportStatModel_article extends ReportStatModel
{

    var $tables = array('entry'=>'kb_entry', 'category'=>'kb_category', 
                        'kb_comment', 'kb_rating', 'kb_glossary', 'user');
    var $custom_tables =  array();
    var $manager2;
        
        
    function getRecordsCount($mode) {
        switch ($mode) {
            case 'most_viewed':
                return $this->getMostViewedCount();
                break;
                
            case 'most_useful':
                return $this->getMostUsefulUselessCount();
                break;
                
            case 'most_useless':
                return $this->getMostUsefulUselessCount();
                break; 
                
            case 'most_commented':
                return $this->getMostCommentedCount(); 
                break;
        }       
    }
    
    
    function getRecords($mode, $limit, $offset) {
        switch ($mode) {
            case 'most_viewed':
                return $this->getMostViewed($limit, $offset);
                break;
                
            case 'most_useful':
                return $this->getMostUseful($limit, $offset);
                break;
                
            case 'most_useless':
                return $this->getMostUseless($limit, $offset);
                break; 
                
            case 'most_commented':
                return $this->getMostCommented($limit, $offset); 
                break;
        }       
    }
    
        
    function getCountAllComment() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->kb_comment}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    

    function getCommentStatus() {
        $sql = "SELECT e.active, COUNT(e.id) AS num
        FROM {$this->tbl->kb_comment} e
        GROUP BY e.active";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();        
    }

    
    function getCountAllGlossary() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->kb_glossary}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');
    }
    
    
    function getMostViewedCount() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->entry} e";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
    
    
    function getMostViewed($limit = 10, $offset = 0) {
        $sql = "SELECT id AS eid, id, title, hits AS num 
            FROM {$this->tbl->entry} e ORDER BY hits DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();        
    }
    
    
    function getMostUsefulUselessCount() {
        $sql = "SELECT COUNT(*) AS num
            FROM ({$this->tbl->entry} e, {$this->tbl->kb_rating} r)
            WHERE e.id = r.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');      
    }

    
    function _getMostUsefulUseless($limit, $offset, $order) {
        $sql = "SELECT 
            e.id AS eid, 
            e.id, 
            e.title, 
            (r.rate/r.votes) AS num
        FROM ({$this->tbl->entry} e, {$this->tbl->kb_rating} r)
        WHERE e.id = r.entry_id
        ORDER BY num {$order}";
        
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        return $result->GetAssoc();        
    }

    
    function getMostUseful($limit = 10, $offset = 0) {
        return $this->_getMostUsefulUseless($limit, $offset, 'DESC');
    }
    
    
    function getMostUseless($limit = 10, $offset = 0) {
        return $this->_getMostUsefulUseless($limit, $offset, 'ASC');
    }
    
    
    function getMostCommented($limit = 10, $offset = 0) {
        $sql = "SELECT e.id AS eid, e.id, e.title, COUNT(c.id) AS num 
        FROM ({$this->tbl->entry} e, {$this->tbl->kb_comment} c)
        WHERE e.id = c.entry_id
        GROUP BY e.id 
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getMostCommentedCount() {
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS num 
        FROM ({$this->tbl->entry} e, {$this->tbl->kb_comment} c)
        WHERE e.id = c.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num'); 
    }    
}
?>