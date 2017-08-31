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


class KBExport2_htmlsep extends KBExport2
{
    
    var $toc_cat_link_str = '%d.html';
    var $toc_entry_link_str = '%d_%d.html';
    

    function &export($tree_helper) {
        
        $this->checkAvailability();
         
        list($entries, $filesize) = $this->proceedCategories($tree_helper);
        
        $files_num = count($entries, COUNT_RECURSIVE) - count($entries);
        
        $msg = 'Files generated: %d, Size: %s';
        $msg = sprintf($msg, $files_num, WebUtil::getFileSize($filesize));
        $this->log($msg);

        // create the index file
        $index_file = $this->getIndexFile($tree_helper, $entries);
        $this->writeTmpFile('index', $index_file);
        
        $data = $this->compress();
        return $data;
    }
    
    
    function getIndexFile($tree_helper, $entries) {
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_page.html');
        
        $tpl->tplAssign('title', $this->config['title']);
        
        $toc = $this->getToc($tree_helper, $entries);
        $tpl->tplAssign('content', $toc);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1); 
    }
    
    
    function getCategoryFile($id, $prev_id, $next_id) {
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_htmlsep_category.html');
        
        if ($prev_id) {
            $tpl->tplSetNeeded('/prev_link');
            $tpl->tplAssign('prev_id', $prev_id);
        }
        
        if ($next_id) {
            $tpl->tplSetNeeded('/next_link');
            $tpl->tplAssign('next_id', $next_id);
        }
        
        $tpl->tplAssign($this->view->msg);
        
        $tpl->tplParse($this->manager->categories[$id]);
        return $tpl->tplPrint(1); 
    }
    
    
    function proceedCategories($tree_helper) {
        
        $full_path = $this->manager->getCategorySelectRangeFolow();
        
        $entries_names = array();
        $entries_with_related = array();
        
        $prev_id = false;
        $next_id = false;
        
        $filesize = 0;
        
        $cat_ids = array_keys($tree_helper);
        foreach($cat_ids as $cat_key => $cat_id) {
            $level = $tree_helper[$cat_id];
            
            $entries = $this->getEntries($cat_id);
            
            // write a category file
            if (!empty($entries)) {
                $next_id = $entries[0]['id'] . '_' . $cat_id;
                
            } else {
                $next_id = isset($cat_ids[$cat_key + 1]) ? $cat_ids[$cat_key + 1] : false;
            }
            
            $data = $this->getCategoryFile($cat_id, $prev_id, $next_id);
            $this->writeTmpFile($cat_id, $data);
            
            $prev_id = $cat_id;

            // custom
            if($entries) {
                $ids = $this->manager->getValuesString($entries, 'id');
                $custom =  $this->manager->getCustomDataByEntryId($ids, true);                
            }
            
            $count = count($entries);
            
            foreach(array_keys($entries) as $entry_key) {
                $row = $entries[$entry_key];
                
                if ($entry_key != $count - 1) {
                    $next_id = $entries[$entry_key + 1]['id'] . '_' . $cat_id;
                    
                } else {
                    $next_id = isset($cat_ids[$cat_key + 1]) ? $cat_ids[$cat_key + 1] : false;
                }
                
                $this->parseEntry($row, $custom, $full_path, $entries_with_related);

                $data = $this->getEntryFile($row, $prev_id, $next_id);
                $this->writeTmpFile($row['id'] . '_' . $cat_id, $data);
                
                $filesize += strlen($data);
                $entries_names[$row['category_id']][$row['id']] = $row['title'];
                $prev_id = $row['id'] . '_' . $cat_id; 
            }
        }
        
        if ($this->convert_links) {
            $entry_to_cats = $this->getEntryToCatsArray($entries_names);
            
            foreach ($entries_with_related as $entry_id) {
                foreach ($entry_to_cats[$entry_id] as $cat_id) {
                    $filename = $entry_id . '_' . $cat_id;
                    $data = $this->readTmpFile($filename);
                    
                    $this->parseRelated($data, $entry_to_cats);
                    
                    $this->writeTmpFile($filename, $data);
                }
            }
        }
        
        return array($entries_names, $filesize);
    }

}
?>