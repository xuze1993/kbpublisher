<?php
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php'; 
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel_dir.php';
require_once APP_MODULE_DIR . 'file/draft/inc/FileDraftModel.php';


class FileDirectoryModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('entry_rule', 'file_entry', 'entry_draft');
    var $fe_model; // FileEntryModel
    var $setting = array();
    var $entry_type = 2;

    
    function getRules() {
        $sql = "SELECT * FROM {$this->tbl->entry_rule} 
        WHERE entry_type = '{$this->entry_type}' AND active = 1";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        return $result->GetAssoc();
    }
    
    
    function getFiles($directory, $parse_child) {
            
        $sql = "SELECT 
            id,
            date_posted,
            date_updated, 
            author_id, 
            md5hash,
            downloads,
            directory,
            filename,
            filename_disk
        FROM {$this->tbl->file_entry}
        WHERE directory %s";
        
        $sql = sprintf($sql, ($parse_child) ? "LIKE '{$directory}%'" : "= '{$directory}'");
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }

        $data = array();
        while($row = $result->FetchRow()) {
            $dirname = preg_replace("#[/\\\]+$#", '', trim($row['directory'])); // remove trailing slash
            $fname = ($row['filename_disk']) ? $row['filename_disk'] : $row['filename'];
            $file = $dirname . '/' . $fname;
            unset($row['directory']);
            unset($row['filename']);
            $data[$file] = $row;
        }
        
        return $data;
    }
    
    
    function getDraftFiles($directory, $parse_child) {
            
        $sql = "SELECT *
        FROM {$this->tbl->entry_draft}
        WHERE entry_type = 2
            AND entry_obj LIKE '%%s:9:\"directory\";s:%%:\"%s%s\"%%'";
        
        $sql = sprintf($sql, $directory, ($parse_child) ? '%' : '');
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }

        $data = array();
        while($row = $result->FetchRow()) {
            $entry_obj = unserialize($row['entry_obj']);
            
            $dirname = preg_replace("#[/\\\]+$#", '', trim($entry_obj->get('directory')));
            $fname = ($entry_obj->get('filename_disk')) ? $entry_obj->get('filename_disk') : $entry_obj->get('filename');
            $file = $dirname . '/' . $fname;
            
            $data[$file] = $entry_obj->get();
            $data[$file]['draft_id'] = $row['id'];
        }
        
        return $data;
    }
    
    
    function getData($file) {

        $data = $this->fe_model->getFileData($file);        
        $data['filetype'] = addslashes($data['type']);
        $data['filename'] = addslashes($data['name']);
        $data['filesize'] = $data['size'];
        $data['filename_index'] = addslashes($data['name_index']);
        $data['filename_disk'] = addslashes($data['name_disk']);

        if($this->setting['file_extract']) {
                
            require_once APP_EXTRA_MODULE_DIR . 'file_extractors/FileTextExctractor.php';
                    
            $extractor = new FileTextExctractor($data['extension'], $this->setting['extract_tool']);
            //$extractor->setDecode('windows-1251', 'UTF-8'); // example
            $extractor->setTool($this->setting['extract_tool']);
            $extractor->setExtractDir($this->setting['extract_save_dir']);
                            
            $data['filetext'] = addslashes($extractor->getText($file));
        }  
        
        $data['date_posted'] = false;

        return $data;
    }


    function updateExecution($id) {        
        $sql = "UPDATE {$this->tbl->entry_rule} SET date_executed = NOW() WHERE id = %d";
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }


    function addFile($obj, $action) {
        // trigger_error inside save, 
        // it will not die only for adding to kbp_file_entry
        return $this->fe_model->save($obj, $action, true);
    }
    
    
    function addDraft($obj, $action) {
        return $this->fd_model->save($obj);
    }


    function setFileStatus($file_ids, $status) {
        $this->fe_model->delete_mode = 2; // allowed by ids
        $file_ids = $this->fe_model->idToString($file_ids);
        
        $sql = "UPDATE {$this->fe_model->tbl->entry} 
        SET active = '%d', date_updated = date_updated WHERE id IN (%s)";
        $sql = sprintf($sql, $status, $file_ids);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }

    
    function deleteFile($file_ids) {
        $this->fe_model->delete_mode = 2; // allowed by ids
        $this->fe_model->delete($file_ids, false, false); // .. $from_disk = falses, $update_sort = false
        return true;
    }

}
?>