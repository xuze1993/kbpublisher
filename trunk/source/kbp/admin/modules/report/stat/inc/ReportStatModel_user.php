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


class ReportStatModel_user extends ReportStatModel
{

    var $tables = array('entry'=>'user', 'priv', 'priv_name', 'user_role', 'user_company',
                        'kb_entry', 'file_entry', 'kb_comment', 'user_subscription',
                        'feedback', 'kb_rating_feedback');
    var $custom_tables =  array();
    var $manager2;
        
    
        
        
    function getRecordsCount($mode) {
        switch ($mode) {
            case 'most_author':
                return $this->getMostAuthorCount();
                break;
                
            case 'most_fileauthor':
                return $this->getMostFileAuthorCount();
                break;
                
            case 'most_commenter':
                return $this->getMostCommenterCount();
                break;
                
            case 'most_feedback':
                return $this->getMostFeedbackCount();
                break;
            
            case 'most_article_feedback':
                return $this->getMostArticleFeedbackCount();
                break;
        }       
    }
    
    
    function getRecords($mode, $limit, $offset) {
        switch ($mode) {
            case 'most_author':
                return $this->getMostAuthor($limit, $offset);
                break;
                
            case 'most_fileauthor':
                return $this->getMostFileAuthor($limit, $offset);
                break;
                
            case 'most_commenter':
                return $this->getMostCommenter($limit, $offset);
                break; 
            
            case 'most_feedback':
                return $this->getMostFeedback($limit, $offset);
                break;
                
            case 'most_article_feedback':
                return $this->getMostArticleFeedback($limit, $offset);
                break;
        }       
    }
    
    
    function getCountAllPrivilege() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->priv_name}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getCountAllRole() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->user_role}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getCountAllCompany() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->user_company}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num'); 
    }


    function getUserByPrivilege() {
        $sql = "SELECT priv_name_id, COUNT(priv_name_id) AS num 
        FROM {$this->tbl->priv} 
        GROUP BY user_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();        
    }

    
    function getPrivileges() {
        $msg = AppMsg::getMsgs('privileges_msg.ini');
        $sql = "SELECT id AS eid, id, name AS title
        FROM {$this->tbl->priv_name} n
        ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {

            if(empty($row['title'])) {
                $row['title'] = $msg[$row['id']]['name'];
            }        

            $data[$row['id']] = $row;
        }

        return $data;        
    }
    
    
    function getMostAuthorCount() {
        $sql = "SELECT COUNT(DISTINCT(u.id)) AS num 
            FROM (
                {$this->tbl->entry} u,
                {$this->tbl->kb_entry} e) 
            WHERE e.author_id = u.id ";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');       
    }
    
    
    function getMostAuthor($limit = 10, $offset = 0) {
        $sql = "SELECT u.id as eid, u.id, u.first_name, u.last_name, COUNT(*) AS num 
        FROM 
             ({$this->tbl->entry} u, 
             {$this->tbl->kb_entry} e)
        WHERE e.author_id = u.id
        GROUP BY e.author_id
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();    
    }
    
    
    function getMostFileAuthorCount() {
        $sql = "SELECT COUNT(DISTINCT(u.id)) AS num   
            FROM 
                ({$this->tbl->entry} u, 
                {$this->tbl->file_entry} e)
            WHERE e.author_id = u.id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');      
    }
    
    
    function getMostFileAuthor($limit = 10, $offset = 0) {
        $sql = "SELECT u.id as eid, u.id, u.first_name, u.last_name, COUNT(*) AS num 
        FROM 
            ({$this->tbl->entry} u, 
            {$this->tbl->file_entry} e)
        WHERE e.author_id = u.id
        GROUP BY e.author_id
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();    
    }
    
    
    function getMostCommenterCount() {
        $sql = "SELECT COUNT(DISTINCT(u.id)) AS num   
            FROM 
                ({$this->tbl->entry} u, 
                {$this->tbl->kb_comment} c)
            WHERE u.id = c.user_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');      
    }
    
    
    function getMostCommenter($limit = 10, $offset = 0) {
        $sql = "SELECT u.id as eid, u.id, u.first_name, u.last_name, COUNT(*) AS num 
        FROM 
            ({$this->tbl->entry} u, 
            {$this->tbl->kb_comment} c)
        WHERE u.id = c.user_id
        GROUP BY c.user_id
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();        
    }
    
    
    function getMostFeedbackCount() {
        $sql = "SELECT COUNT(DISTINCT(u.id)) AS num   
            FROM 
                ({$this->tbl->entry} u, 
                {$this->tbl->feedback} f)
            WHERE u.id = f.user_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');      
    }
    
    
    function getMostFeedback($limit = 10, $offset = 0) {
        $sql = "SELECT u.id as eid, u.id, u.first_name, u.last_name, COUNT(*) AS num 
        FROM 
            ({$this->tbl->entry} u, 
            {$this->tbl->feedback} f)
        WHERE u.id = f.user_id
        GROUP BY f.user_id
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();        
    }
    
    
    function getMostArticleFeedbackCount() {
        $sql = "SELECT COUNT(DISTINCT(u.id)) AS num   
            FROM 
                ({$this->tbl->entry} u, 
                {$this->tbl->kb_rating_feedback} f)
            WHERE u.id = f.user_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');      
    }
    
    
    function getMostArticleFeedback($limit = 10, $offset = 0) {
        $sql = "SELECT u.id as eid, u.id, u.first_name, u.last_name, COUNT(*) AS num 
        FROM 
            ({$this->tbl->entry} u, 
            {$this->tbl->kb_rating_feedback} f)
        WHERE u.id = f.user_id
        GROUP BY f.user_id
        ORDER BY num DESC";
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));    
        return $result->GetAssoc();        
    }
    
    
    function getUsersBySubscribtionAll() {
        $sql = "SELECT entry_type, COUNT(*) AS num 
        FROM {$this->tbl->user_subscription}
        WHERE entry_id = 0 
        GROUP BY entry_type";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
    
    
    function getUsersBySubscribtionConcrete() {
        $sql = "SELECT entry_type, COUNT(DISTINCT user_id) AS num 
        FROM {$this->tbl->user_subscription}
        WHERE entry_id != 0 AND entry_type != 3
        GROUP BY entry_type";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
}
?>