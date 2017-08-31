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

require_once 'core/app/BulkModel.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModelBulk.php';


class FileEntryModelBulk extends KBEntryModelBulk
{

    var $actions = array('category_move', 'category_add',
                         'tag', 'private', 'public', 
                         'schedule', 'parse',
                         'sort_order', 'custom',
                         'status'
                         // 'remove'
                        //, 'delete'
                        );
                            
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
                
        if(!$manager->show_bulk_sort) {
            unset($actions['sort_order']);
        }
        
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }

    
/*
    function delete($ids) {
        
        // to skip enties that have inline links to articles to be deleted
        // $related_ids in array (deleted entry[] = entry that has reference)
        $related_ids = $this->model->getEntryToRelated($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        $this->model->delete($ids, true); // false to skip sort updating  ???
        return array_keys($related_ids);
    }    */

    
    
    function parse($values, $ids) {

        if(empty($values)) {
            return;
        }
        
        require_once 'eleontev/Dir/MyDir.php';
        require_once 'eleontev/Dir/mime_content_type.php';        
        
        $ids_str = $this->model->idToString($ids);
        
        $this->model->setSqlParams("AND e.id IN($ids_str)");
        
        $limit = -1;
        $rows = &$this->model->getRecords($limit);
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            $file = $this->model->getFileDir($row);
            if(!$file) {
                continue;
            }
            
            $upload = $this->model->getFileData($file);
            
            $file_save = array();
            $file_save['id'] = $row['id'];
            $file_save['filetype'] = addslashes($upload['type']);
            $file_save['md5hash'] = $upload['md5hash'];            
            $file_save['date_updated'] = 'date_updated';
            $file_save['filename_index'] = addslashes($upload['name_index']);
                    
            if(in_array('filesize', $values)) {
                $file_save['filesize'] = $upload['size'];
            }
                        
            if(in_array('filetext', $values)) {
                $file_save['filetext'] = '';        

                if($this->model->setting['file_extract']) {

                    require_once APP_EXTRA_MODULE_DIR . 'file_extractors/FileTextExctractor.php';

                    $extractor = new FileTextExctractor($upload['extension'], $this->model->setting['extract_tool']);
                    //$extractor->setDecode('windows-1251', 'UTF-8'); // example
                    $extractor->setTool($this->model->setting['extract_tool']);
                    $extractor->setExtractDir($this->model->setting['extract_save_dir']);

                    $file_save['filetext'] = addslashes($extractor->getText($upload['to_read']));
                }      
            }
            
            $this->updateFile($file_save);    
        }
    }
    
    
    function updateFile($val) {
        $sql = ModifySql::getSql('UPDATE', $this->model->tbl->entry, $val, false, 'id');
        $sql = str_replace("'date_updated'", 'date_updated', $sql);
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }    
}
?>