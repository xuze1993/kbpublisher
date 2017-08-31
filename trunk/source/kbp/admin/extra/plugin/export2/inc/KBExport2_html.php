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


class KBExport2_html extends KBExport2
{
    
    var $toc_cat_link_str = '#cat_%d';
    var $toc_entry_link_str = '#entry_%d_%d';
       

    function &export($tree_helper) {
        
        $this->checkAvailability();
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_page.html');
        
        $tpl->tplAssign('title', $this->config['title']);

        list($entries, $content) = $this->proceedCategories($tree_helper);
        
        $toc = $this->getToc($tree_helper, $entries);
        $tpl->tplAssign('content', $toc . $content);
        
        $tpl->tplParse();
        $this->writeTmpFile('index', $tpl->tplPrint(1));
        
        $files_num = 1;
        $filesize = WebUtil::getFileSize($this->config['temp_dir'] . 'output/index.html');
        $msg = 'Files generated: %d, Size: %s';
        $msg = sprintf($msg, $files_num, $filesize);
        $this->log($msg);
        
        $data = $this->compress();
        return $data;
    }
    
    
    function proceedCategories($tree_helper) {
        
        $full_path = $this->manager->getCategorySelectRangeFolow();
        
        $entries_names = array();
        $entries_with_related = array();
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_html.html');
        
        $entries_with_related = array();

        foreach($tree_helper as $cat_id => $level) {
            
            $tpl->tplSetNeeded('category_row/category_info');
            
            $v['hnum'] = 1;

            // get entries
            $entries = $this->getEntries($cat_id);

            // custom
            if($entries) {
                $ids = $this->manager->getValuesString($entries, 'id');
                $custom =  $this->manager->getCustomDataByEntryId($ids, true);                
            }

            foreach(array_keys($entries) as $k) {
                $row = $entries[$k];
                
                $this->parseEntry($row, $custom, $full_path, $entries_with_related);
            
                $cat_type = $this->manager->getCategoryType($cat_id);
                $nobreak_types = array('faq', 'faq2');                
                
                // entry info
                if($this->config['print_entry_info']) {
                    $tpl->tplSetNeeded('entry_row/entry_info');
                    $tpl->tplAssign($this->view->msg);
                }
                
                $row['anchor'] = sprintf('entry_%d_%d', $row['id'], $cat_id);
                $row['hnum'] = 2;
    
                $tpl->tplParse($row, 'category_row/entry_row');
                
                $entries_names[$row['category_id']][$row['id']] = $row['title'];
            }
                

            $v['title'] = $this->manager->categories[$cat_id]['name'];
            $v['description'] = $this->manager->categories[$cat_id]['description'];
            $v['anchor'] = sprintf('cat_%d', $cat_id);
            
            // decode
            if($this->decode_utf) {
                $v['title'] = $this->decodeUTF8($v['title']);
                $v['description'] = $this->decodeUTF8($v['description']);
            }
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse($v, 'category_row');
            
            
            $tpl->tplParse();
            $tpl->tplPostParse(array($this, 'replaceMSWordCharacters'));
        }
        
        $data = $tpl->tplPrint(1);
        
        if ($this->convert_links) {
            $entry_to_cats = $this->getEntryToCatsArray($entries_names);
            $this->parseRelated($data, $entry_to_cats);   
        }
        
        return array($entries_names, $data);   
    }
    
}
?>