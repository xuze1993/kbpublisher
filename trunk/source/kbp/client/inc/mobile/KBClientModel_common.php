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

class KBClientModel_common extends KBClientModel
{

    function setCustomSettings($controller) { 
  
        $this->setting['page_to_load'] = 'Default';
        $this->setting['view_header'] = 1;
        $this->setting['article_block_position'] = 'bottom';
        $this->setting['preview_article_limit'] = 0;
        $this->setting['preview_show_rating'] = 0;
        
        //$this->setting['show_pdf_link'] = 0;
        //$this->setting['show_pdf_category_link'] = 0;
        
        $this->setting['nav_prev_next'] = 'no';
        $this->setting['num_entries_category'] = 1; // others in entry view
        $this->setting['comments_entry_page'] = 0;
        
        if (($controller->view_id == 'index' && !$controller->category_id) || $controller->view_id == 'files') {
            $this->setting['num_category_cols'] = 0;
        }
        
    }
    
    
    function getCategoryType($category_id) {
        // $cat_type = array(1 => 'default', 2 => 'faq', 3 => 'book', 4 => 'faq');
        $cat_type = array(1 => 'default', 2 => 'faq', 3 => 'default', 4 => 'faq');
        $type = $this->categories[$category_id]['type'];
        return (isset($cat_type[$type])) ? $cat_type[$type] : 'default';
    }

}
?>