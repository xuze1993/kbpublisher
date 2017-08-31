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


class FileRuleModel extends AppModel
{
    var $tbl_pref_custom = '';
    var $tables = array('table'=>'entry_rule');
                
    
    function isDirectoryAdded($dir, $id = false) {
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE directory = '$dir'";
        
        if ($id) {
            $sql .= " AND id != '$id'";    
        }
                          
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }
    
    
    function isSubDirectory($dir, $id = false) {
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE LOCATE(directory, '$dir')";
        
        if ($id) {
            $sql .= " AND id != '$id'";    
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }


    function save($obj) {
        
        if($obj->get('id')) {
            $this->update($obj);
            $id = $obj->get('id');
        } else {
            $id = $this->add($obj);
        }
        
        return $id;
    }
    
    
    function &readDirectory($dirname, $one_level = true) {
        $d = new MyDir;
        $d->one_level = $one_level;
        $d->full_path = true;
        
        // $d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS', 'etc');
        // $d->setSkipRegex('#^\.ht*#i');
        
        $dirname = str_replace('\\', '/', realpath($dirname));
        $files = &$d->getFilesDirs($dirname);
                
        return $files;
    }

}
?>