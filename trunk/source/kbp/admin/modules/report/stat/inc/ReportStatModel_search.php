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


class ReportStatModel_search extends ReportStatModel
{

    var $tables = array('entry'=>'report_search');
    var $custom_tables =  array();
    
    
    function getRecordsCount($mode) {
        switch ($mode) {
            case 'most_searched':
                return $this->getMostLessPopularCount();
                break;
                
            case 'less_searched':
                return $this->getMostLessPopularCount();
                break;
        }       
    }
    
    
    function getRecords($mode, $limit, $offset) {
        switch ($mode) {
            case 'most_searched':
                return $this->getMostPopular($limit, $offset);
                break;
                
            case 'less_searched':
                return $this->getLessPopular($limit, $offset);
                break;
        }       
    }
    
    
    function getMostLessPopularCount() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->entry}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');       
    }
    
    
    function _getMostLessPopular($limit = 10, $offset = 0, $order) {
        $sql = "SELECT search_string AS title, search_num AS num  
        FROM {$this->tbl->entry}
        ORDER BY num {$order}";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetArray();        
    }
    

    function getMostPopular($limit = 10, $offset = 0) {
        return $this->_getMostLessPopular($limit, $offset, 'DESC');
    }
    
    
    function getLessPopular($limit = 10, $offset = 0) {
        return $this->_getMostLessPopular($limit, $offset, 'ASC');
    }
            
}
?>