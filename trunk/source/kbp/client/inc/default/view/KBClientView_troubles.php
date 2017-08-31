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


class KBClientView_troubles extends KBClientView_common
{    

    var $num_subcategories = 0;

    
    function &execute(&$manager) {

        if(!$this->category_id) {
            $rows = $this->stripVars($manager->getCategoryList($this->top_parent_id));
            $title = $this->msg['category_title_msg'];
            $this->meta_title = '';
            
        } else {
            
            // does not matter why no category, deleted, or inactive or private
            if(!isset($manager->categories[$this->category_id])) { 
                
                // new private policy, check if entry exists 
                if($manager->is_registered) { 
                    if($manager->isCategoryExistsAndActive($this->category_id)) {
                        $this->controller->goAccessDenied('troubles');
                    }
                }
                
                $this->controller->goStatusHeader('404');
            }            
            
            $rows = $this->stripVars($manager->getCategoryList($this->category_id));
            $title =  $this->msg['subcategory_title_msg'];
            $this->meta_title = $this->stripVars($manager->categories[$this->category_id]['name']);
            $this->num_subcategories = count($rows);
        }
        
                             
        $data = array();
        $data[2] = &$this->getEntryList($manager);
        $data[1] = $this->getCategoryList($rows, $title, $manager, ($data[2]));
        ksort($data);

        $data = implode('', $data);
                                     
        return $data;
    }
    
    
    function getCategoryList($rows, $title, $manager, $is_articles = true) {
        
        if(!$rows || !$this->display_categories) {
            return;
        }
        
        // no articles and 'num_category_cols' set to 0
        // we need display categories not to show empty page
        $num_category_td = $manager->getSetting('num_category_cols');
        if($num_category_td == 0) {
            if($is_articles) {
                return;
            }
            
            $num_category_td = 1;
        }

                
        $num = count($rows);
        $rows = array_chunk($rows, $num_category_td);
        
        // td width
        if($num < $num_category_td) { $td_width = round(100/$num); } 
        else                        { $td_width = round(100/$num_category_td); }
        
        
        $tpl = new tplTemplatez($this->template_dir . 'category_list.html');

        foreach($rows as $k => $v) {
            foreach($v as $k1 => $v1) {
                
                $v1['td_width'] = $td_width;
                $v1['category_link'] = $this->getLink('troubles', $v1['id']);
                
                $private = $this->isPrivateEntry(false, $v1['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);                
                
                $tpl->tplParse($v1, 'row_tr/row_td'); // parse nested
            }
        
            // do it nested
            $tpl->tplSetNested('row_tr/row_td');
            $tpl->tplParse('', 'row_tr');    
        }
        
        $tpl->tplAssign('list_title', $title);
        $tpl->tplAssign('title_colspan', $num_category_td*2);
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    function &getEntryList(&$manager) {
        
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        // category articles
        if($this->category_id) {

            $num = $manager->getSetting('num_entries_per_page');
            return $this->_getCategoryEntries($manager, $num);
        
        // top articles
        } else {
            
            $most_num   = $manager->getSetting('num_most_viewed_entries');
            $recent_num = $manager->getSetting('num_recently_posted_entries');
            
            if($most_num && $recent_num) {
                $tpl = new tplTemplatez($this->getTemplate('top_article_format.html'));
                
                $data = array();                                             
                $data['data1'] = &$this->_getMostViewed($manager, $most_num);
                $data['data2'] = &$this->_getRecentlyPosted($manager, $recent_num);  
                
                $tpl->tplParse($data);            
                return $tpl->tplPrint(1);                
            
            } elseif($most_num) {
                return $this->_getMostViewed($manager, $most_num);
                
            } elseif($recent_num) {
                return $this->_getRecentlyPosted($manager, $recent_num);
            }
        }
    }
    
    
    function &parseTroubleList(&$manager, &$rows, $title, $by_page = '', $sort_select = false) {
        
        if(!$rows) { $empty = ''; return $empty; }
        
        $tpl = new tplTemplatez($this->getTemplate('trouble_list.html'));
        
        $article_staff_padding = '1';
        $article_description_padding = 3;
        if($manager->getSetting('preview_trouble_limit') == 0) { 
            $article_description_padding = 0; 
            $article_staff_padding = 0;
        }
        $tpl->tplAssign('article_description_padding', $article_description_padding);
        
        // what date to display
        $date = 'ts_updated';
        if($by_page && strpos($manager->getSetting('entry_sort_order'), 'added') !== false) {
            $date = 'ts_posted';
        }
    
        // staff
        if($manager->getSetting('preview_show_date')) {
            $tpl->tplSetNeededGlobal('show_date');
            $article_staff_padding = '3';
        }                
        
        if($manager->getSetting('preview_show_hits')) {
            $tpl->tplSetNeededGlobal('show_hits');
            $article_staff_padding = '3';
        }
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);
            $row['updated_date'] = $this->getFormatedDate($row[$date]);

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('trouble', $row['category_id'], $entry_id);            
            
            $summary_limit = $this->getSummaryLimit($manager, $private);
            $row['body'] = DocumentParser::getSummary($row['body'], $summary_limit);
                        
            if($this->isRatingable($manager)) {
                if($manager->getSetting('preview_show_rating')) {
                    $tpl->tplSetNeeded('row/show_rate');
                    $row['rating'] = $this->_getRating($row['rating']);                    
                    $article_staff_padding = '3';
                }
            }           
            
            $row['article_staff_padding'] = $article_staff_padding;
            $tpl->tplParse($row, 'row');
        }
        

        // by page
        if($by_page && $by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
        
        
        $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);  
        $tpl->tplAssign('sort_by_msg', $this->msg['sort_by_msg']);        
        $tpl->tplAssign('list_title', $title);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }    
    
    
    function &_getCategoryEntries($manager, $num) {
            
        $manager->setSqlParams("AND cat.id = {$this->category_id}");
        $bp = $this->pageByPage($num, $manager->getEntryCount());
         
        $sort = $manager->getSortOrder();
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        $rows = $manager->getEntryList($bp->limit, $bp->offset, 'category');
        
        if(!$rows && !$this->num_subcategories) {
            $msg = $this->getActionMsg('success', 'no_trouble');
            return $msg;
        }
        
        return $this->parseTroubleList($manager, $this->stripVars($rows), $this->msg['trouble_title_msg'], $bp);
    }
    

    function &_getMostViewed($manager, $num) {
        $manager->setSqlParamsOrder('ORDER BY e.hits DESC');        
        $rows = $manager->getEntryList($num, 0, 'index', 'FORCE INDEX (hits)');
        return $this->parseTroubleList($manager, 
                                        $this->stripVars($rows), 
                                        $this->msg['most_viewed_entries_title_msg'],
                                        '',
                                        'most');
    }
    
    
    function &_getRecentlyPosted($manager, $num) {
        $manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');        
        $rows = $manager->getEntryList($num, 0, 'index', 'FORCE INDEX (date_updated)');
        return $this->parseTroubleList($manager, 
                                        $this->stripVars($rows), 
                                        $this->msg['recently_posted_troubles_title_msg'],
                                        '',
                                        'recent');
    }
}
?>