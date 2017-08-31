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


class ReportStatModel_file extends ReportStatModel
{

    var $tables = array('entry'=>'file_entry', 'category'=>'file_category', 'user');
    var $custom_tables =  array();
    var $manager2;
        
        
    function getRecordsCount($mode) {
        switch ($mode) {
            case 'most_downloaded':
                return $this->getMostViewedCount();
                break;
        }       
    }
    
    
    function getRecords($mode, $limit, $offset) {
        switch ($mode) {
            case 'most_downloaded':
                return $this->getMostViewed($limit, $offset);
                break;
        }       
    }
    
    
    function getMostViewedCount() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->entry} e";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');       
    }
    
    
    function getMostViewed($limit = 10, $offset = 0) {
        $sql = "SELECT id AS eid, id, filename AS title, downloads AS num 
        FROM {$this->tbl->entry} e ORDER BY downloads DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();        
    }
            
}
?>