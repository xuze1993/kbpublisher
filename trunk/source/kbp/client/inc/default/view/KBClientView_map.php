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


class KBClientView_map extends KBClientView_common
{    
    
    var $padding = 25;
    

    function &execute(&$manager) {
        
        $rows = &$manager->categories;
        $title = $this->msg['map_msg'];
                
        $this->home_link = true;
        $this->meta_title = $title;
        $this->nav_title = $title;

        $data = &$this->getList($rows, $manager);

        return $data;
    }
        
    
    function &getList($rows, $manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'article_list_map.html');
        
        $sort =  $this->_getSortOrder($manager->getSetting('entry_sort_order'));
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);        
        
        $tree_helper = &$manager->getTreeHelperArray($rows);
        
        foreach($tree_helper as $cat_id => $level) {
                        
            $cid = $this->controller->getEntryLinkParams($rows[$cat_id]['id'], $rows[$cat_id]['name']);
            $v['category_link'] = $this->getLink('index', $cid);
            
            $private = $this->isPrivateEntry(false, $rows[$cat_id]['private']);            
            $v['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);
            
            $v['title'] = $rows[$cat_id]['name'];
            $v['padding'] = $this->padding*$level;
            
            if(isset($i) && $level == 0) {
                $tpl->tplSetNeeded('category_row/level_0');
            }
            
            $v['category_row_class'] = ($level == 0) ? 'tdSubTitle' : '';
            
            $entries = $manager->getCategoryEntries($cat_id, 0);
            foreach($entries as $k1 => $v1) {
                $entry_id = $this->controller->getEntryLinkParams($v1['entry_id'], $v1['title'], $v1['url_title']);
                $v1['entry_link'] = $this->getLink('entry', $cat_id, $entry_id);
                
                $private = $this->isPrivateEntry($v1['private'], $rows[$cat_id]['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'list');                
                
                $tpl->tplParse($v1, 'category_row/entry_row');
            }
            
            $i = 1;
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse(array_merge($v, $rows[$cat_id]), 'category_row');
        }        
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    function _getSortOrder($setting_sort) {
        $sort = array('name'         => 'e.title',
                      'sort_order'   => 'e.sort_order',
                      'added_desc'   => 'e.date_posted DESC',
                      'added_asc'    => 'e.date_posted ASC',
                      'updated_desc' => 'e.date_updated DESC',
                      'updated_asc'  => 'e.date_updated ASC',
                      'hits_desc'    => 'e.hits DESC',
                      'hits_asc'     => 'e.hits ASC');
        
        return $sort[$setting_sort];    
    }    
}
?>