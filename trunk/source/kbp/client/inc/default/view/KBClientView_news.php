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


class KBClientView_news extends KBClientView_common
{

    // it will overwrite left menu
    function &getLeftMenu($manager) {
        
        require_once $this->controller->common_dir . 'KBClientMenu.php';
        
        $menu_type = $manager->getSetting('view_menu_type');
        
        // old menu
        if(strpos($menu_type, '55') !== false || $menu_type == 'followon') {
            
            require_once $this->controller->working_dir . 'KBClientMenu2.php';
            
            $menu = new KBClientMenu_news2($this);
            return $menu->getTreeMenu($manager);    
        
        // ajax menu
        } else {
        
            $menu = new KBClientMenu_news($this);
            return $menu->getLeftMenu($manager);    
        }
    }
    
    
    function parseCategoryLink($link) {
        return preg_replace("#(news/)(\d{4})#", "$1c$2", $link);
    }    
        
    
    function &getListIndexPage($manager, $limit) {
        
        $rows = $this->stripVars($manager->getNewsList($limit, 0));
        if(!$rows) { 
            $ret = false; 
            return $ret; 
        }
        
        return $this->_getList($manager, $rows, $this->msg['news_title_msg']);        
    }
    
    
    function &getList(&$manager) {
        
        $limit = $manager->getSetting('num_entries_per_page');
        $bp = $this->pageByPage($limit, $manager->getNewsCount($this->category_id));
        
        $rows = $this->stripVars($manager->getNewsList($bp->limit, $bp->offset, $this->category_id));
        if(!$rows) {
            $msg = $this->getActionMsg('success', 'no_news'); 
            return $msg; 
        }
        
        $title =  $this->msg['news_title_msg'];
        
        return $this->_getList($manager, $rows, $title, $bp);
    }
    
    
    function &_getList(&$manager, $rows, $title, $by_page = false) {
    
        $tpl = new tplTemplatez($this->getTemplate('news_list.html'));            
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
        
            $private = $this->isPrivateEntry($row['private'], false);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);        
            $row['formatted_date'] = $this->getFormatedDate($row['date_posted']);
            
            $summary_limit = $this->getSummaryLimit($manager, $private);
            $row['body'] = DocumentParser::getSummary($row['body'], $summary_limit);
            
            $entry_id_param = $this->controller->getEntryLinkParams($row['id'], $row['title'], false);            
            $row['entry_link'] = $this->getLink('news', false, $entry_id_param);
            
            $tpl->tplParse($row, 'row');
        }
        
        if ($this->mobile_view && !$by_page) {
            $tpl->tplSetNeededGlobal('section_link');
            $tpl->tplAssign('section_link', $this->getLink('news'));
        }
        
        // by page
        if($by_page && $by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
                
        $tpl->tplAssign('list_title', $title);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);    
    }    
}
?>