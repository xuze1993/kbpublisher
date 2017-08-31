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


class ReportStatModel_feedback extends ReportStatModel
{

    var $tables = array('entry'=>'feedback', 'kb_rating_feedback', 'user', 'kb_comment');
    var $custom_tables =  array();
    var $manager2;
    
    
    function getCountAllArticleComment() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->kb_comment}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getCountAllRatingComment() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->kb_rating_feedback}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getArticleCommentStatus() {
        $sql = "SELECT active, COUNT(id) AS num
        FROM {$this->tbl->kb_comment}
        GROUP BY active";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
        
    
    function getRatingCommentStatus() {
        $sql = "SELECT active, COUNT(id) AS num
        FROM {$this->tbl->kb_rating_feedback}
        GROUP BY active";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
            
    
    function getFeedbackStatus() {
        $sql = "SELECT answered, COUNT(id) AS num
        FROM {$this->tbl->entry}
        GROUP BY answered";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
            
    
    function getFeedbackStatus2() {
        $sql = "SELECT placed, COUNT(id) AS num
        FROM {$this->tbl->entry} 
        GROUP BY placed";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
        
}
?>