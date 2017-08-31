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

class FileEntryModel_dir extends FileEntryModel
{


    function sendFileDownload($data, $attachment = true) {;
        FileEntryDownload_dir::sendFileDownload($data, $this->setting['file_dir'], $attachment);
    }
    
    
    function getFileDir($data) {
        return FileEntryDownload_dir::getFileDir($data, $this->setting['file_dir']);
    }        
    

    // ACTIONS // ---------------------
    // we just delete old if update and file
    // file already saved by upload method in dir implementation
    function saveFileData($bin_data, $id = null) {
    
        //when update some file
        if($id) { $this->deleteFileData($id, false); }
        
        return $id;
    }
    
    
    function getFileContent($filename) {
        return true;
    }
    
    
    function upload($rename_file = true, $file = false, $dir = false) {
            
        require_once 'eleontev/Dir/Uploader.php';
    
        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();
        $upload->setAllowedExtension($this->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($this->setting['file_denied_extensions']);
        $upload->setMaxSize($this->setting['file_max_filesize']);
        
        if (!$dir) {
            $dir = $this->setting['file_dir'];    
        }
        $upload->setUploadedDir($dir);
        
        if($rename_file) {
            $upload->setRenameValues('date');
        } else {
            $upload->setRenameValues(false);
        }
        
        if ($file) {
            $f = $upload->upload(array('file_1' => $file));
        } else {
            $f = $upload->upload($_FILES);
        }

        if(isset($f['bad'])) {
            $f['error_msg'] = $f['bad'];
        } else {
            
            // $data['filename'] = $f['good'][1]['name'];
            $data['filename'] = $f['good'][1]['name_orig'];
            $data['filename_disk'] = $f['good'][1]['name'];
            $data['directory'] = $dir;
            $data['sub_directory'] = '';
            $f['good'][1]['to_read'] = $this->getFileDir($data);
            $f['good'][1]['directory'] = $dir;
        
            // 2015-07-23 revrite to be compatible, when added filename_disk
            $f['good'][1]['name'] = $data['filename']; // it will be filename
            $f['good'][1]['name_disk'] = $data['filename_disk']; // it will be filename_disk
        }
        
        return $f;
    }

    
    function &readDirectory($dirname, $one_level = true, $dirs = false) {
        
        require_once 'eleontev/Dir/MyDir.php';
        
        $d = new MyDir;
        $d->one_level = $one_level;
        $d->full_path = true;
        
        $d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS', 'etc');
        $d->setSkipFiles('.DS_Store');
        //$d->setSkipFiles('robots.txt');
        $d->setSkipRegex('#^\.ht*#i');
        $d->setAllowedExtension($this->setting['file_allowed_extensions']);
        $d->setDeniedExtension($this->setting['file_denied_extensions']);
        
        $dirname = str_replace('\\', '/', realpath($dirname));
        $files = &$d->getFilesDirs($dirname);
        
        // add dirs to output        
        if ($dirs) {
            $files = array($files, $d->getDirs($dirname));
        } 
        
        return $files;
    }
    
    
    function getFileData($file) {
        
        $d = new MyDir;
        $data = array();
        
        $data['name'] = $d->getFilename($file);
        $data['type'] = mime_content_type($file);
        $data['tmp_name'] = '';
        $data['extension'] = $d->getFileExtension($file);
        $data['size'] = filesize($file);
        $data['md5hash'] = md5_file($file); 
        $data['to_read'] = $file;
        $data['directory'] = $d->getFileDirectory($file);
        
        // add trailing slash
        $data['directory'] = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
        $data['directory'] = $data['directory'] . '/';
        
        $data['name_index'] = $this->getFilenameIndex($data['name']);
        $data['name_disk'] = $data['name'];
        
        return $data;
    }
    
    
    function getFilenameIndex($str) {
        return _strtolower(str_replace('_', ' ', _substr($str, 0, _strrpos($str, '.'))));
    }
    
    
    function save($obj, $action = 'insert', $is_file = false, $cron = false) {
                                      
        // sorting manipulations
        //$action = (!$obj->get('id')) ? 'insert' : 'update';
        $sort_values = $this->updateSortOrder($obj->get('id'), 
                                              $obj->getSortValues(), 
                                              $obj->getCategory(),
                                              $action);        
                                              
        // for sort order in main table now always 1
        $obj->set('sort_order', 1);                                              
        
                          
        if(in_array($action, array('insert', 'clone'))) {
                                
            $id = $this->add($obj);
            
            // for cron, error could be in filename, text, etc
            if($id === false) {
                return false;
            }
            
            $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);
            $this->saveSchedule($obj->getSchedule(), $id);
            
            if($obj->get('private')) {
                $this->saveRoleToEntryObj($obj, $id);
            }
            
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id);
            $this->cf_manager->save($obj->getCustom(), $id);                    
            $this->addHitRecord($id);
            
        } else {
        
            $id = $obj->get('id');
        
            // we have old filetext and no new filetext
            if(!$is_file) {
                unset($obj->properties['filetext']);
            }
        
            $ret = $this->update($obj, $id);
            
            // for cron, error could be in filename, text, etc 
            if($ret === false) {
                return false;
            }
            
            
            $this->deleteEntryToCategory($id);
            $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);
            
            $this->deleteSchedule($id);
            $this->saveSchedule($obj->getSchedule(), $id);
                        
            $this->deleteRoleToEntry($id);
            $this->saveRoleToEntryObj($obj, $id);
            
            $this->tag_manager->deleteTagToEntry($id); 
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id); 
            
            $this->cf_manager->delete($id);
            $this->cf_manager->save($obj->getCustom(), $id);
        }
        
        return $id;
    }
    
    
    function getSetting($key) {
        return $this->setting[$key];
    }
    
    
    // DELETE RELATED // --------------------- 
    
    function deleteFileData($record_id, $from_disk) {
        if($from_disk) {
            unlink($this->getFileDir($this->getById($record_id)));
        }
    }
}
?>