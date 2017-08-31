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


class ReportStatModel extends BaseModel
{
    var $tbl_pref_custom = '';
    var $tables = array();
    var $custom_tables =  array();    
    
    
    static function factory($view) {
        
        $class = 'ReportStatModel_' . $view;
        $file = $class . '.php';
        
        require_once APP_MODULE_DIR . 'report/stat/inc/' . $file;
        return new $class;
    }
    
 
   function checkPriv(&$priv, $action) {
        $priv->setCustomAction('file', 'select');
        $priv->check($action);
    }    
    
    
    // STATUS // ----------------
    
    function getEntryStatus() {
        $sql = "SELECT e.active, COUNT(e.id) AS num
        FROM {$this->tbl->entry} e
        GROUP BY e.active";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
    
    
    function getCategoryStatus() {
        $sql = "SELECT e.active, COUNT(e.id) AS num
        FROM {$this->tbl->category} e
        GROUP BY e.active";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    

    // PUBLIC & PRIVATE // ------------------------------
    
    function getEntryPrivate() {
        $sql = "SELECT e.private, COUNT(e.id) AS num
        FROM {$this->tbl->entry} e
        GROUP BY e.private";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
    
    
    function getCategoryPrivate() {
        $sql = "SELECT e.private, COUNT(e.id) AS num
        FROM {$this->tbl->category} e
        GROUP BY e.private";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->GetAssoc();
    }
        
    
    // COUNT ALL // --------------
    
    function getCountAll() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->entry}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getCountAllCategory() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->category}";
        $result = $this->db->Execute($sql) or die(db_error($sql));    
        return $result->Fields('num');        
    }
    
    
    function getCountAllAuthor() {
        $sql = "SELECT COUNT(DISTINCT author_id) AS num FROM {$this->tbl->entry}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
}
?>