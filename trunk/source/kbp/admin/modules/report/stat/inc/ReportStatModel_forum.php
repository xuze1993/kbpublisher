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


class ReportStatModel_forum extends ReportStatModel
{

    var $tables = array(
        'entry' => 'forum_entry',
        'category' => 'forum_category',
        'message' => 'forum_message',
        'user');
    var $custom_tables =  array();
    var $manager2;
        
        
    function getRecordsCount($mode) {
        switch ($mode) {
            case 'most_viewed_topic':
                return $this->getMostViewedCount();
                break;
                
            case 'most_commented_topic':
                return $this->getMostCommentedCount(); 
                break;
        }       
    }
    
    
    function getRecords($mode, $limit, $offset) {
        switch ($mode) {
            case 'most_viewed_topic':
                return $this->getMostViewed($limit, $offset);
                break;
                
            case 'most_commented_topic':
                return $this->getMostCommented($limit, $offset); 
                break;
        }       
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
    
    
    function getMostCommented($limit = 10, $offset = 0) {
        $sql = "SELECT e.id AS eid, e.id, e.title, COUNT(c.id) AS num 
        FROM ({$this->tbl->entry} e, {$this->tbl->message} c)
        WHERE e.id = c.entry_id
        GROUP BY e.id 
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getMostCommentedCount() {
        $sql = "SELECT COUNT(DISTINCT(e.id)) AS num 
        FROM ({$this->tbl->entry} e, {$this->tbl->message} c)
        WHERE e.id = c.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num'); 
    }
            
}
?>