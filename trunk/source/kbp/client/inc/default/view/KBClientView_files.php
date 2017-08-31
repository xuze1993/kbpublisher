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


class KBClientView_files extends KBClientView_common
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
                        $this->controller->goAccessDenied('files');
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
        
        
        $tpl = new tplTemplatez($this->getTemplate('category_list.html'));
        
        foreach($rows as $k => $v) {
            $i = 0;
            
            foreach($v as $k1 => $v1) {
                
                $private = $this->isPrivateEntry(false, $v1['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);
                
                $v1['td_width'] = $td_width;
                $v1['description'] = nl2br($v1['description']);
                
                $cat_id = $this->controller->getEntryLinkParams($v1['id'], $v1['name']);
                $v1['category_link'] = $this->getLink('files', $cat_id);            
                
                // no preview if private
                if(!$this->getSummaryLimit($manager, $private)) {
                    $v1['description'] = '';
                }
                
                $tpl->tplParse($v1, 'row_tr/row_td'); // parse nested
                
                $i ++;
            }
            
            $empty_cells_needed = ($num_category_td - $i) * 2;
            if ($empty_cells_needed) {
                for($j = 0; $j < $empty_cells_needed; $j ++) {
                    $tpl->tplParse(null, 'row_tr/row_empty_td'); 
                }
            }
        
            // do it nested
            $tpl->tplSetNested('row_tr/row_empty_td');
            $tpl->tplSetNested('row_tr/row_td');
            $tpl->tplParse('', 'row_tr');    
        }
        
        $tpl->tplAssign('list_title', $title);
        $tpl->tplAssign('title_colspan', $num_category_td*2);
        $tpl->tplAssign('meta_title', $this->meta_title);
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
                $data['data1'] = &$this->_getRecentlyPosted($manager, $recent_num);
                $data['data2'] = &$this->_getMostViewed($manager, $most_num);
                
                $tpl->tplParse($data);            
                return $tpl->tplPrint(1);                
            
            } elseif($most_num) {
                return $this->_getMostViewed($manager, $most_num);
                
            } elseif($recent_num) {
                return $this->_getRecentlyPosted($manager, $recent_num);
            }
            
            $ret = '';
            return $ret;
        }
    }
        
    
    // parse data with files
    function &parseFileList(&$manager, $rows, $title, $by_page = '', $type = '') {
        
        $umsg = AppMsg::getMsgs('user_msg.ini', 'public');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];        
        
        if(!$rows) {
            $empty = '';
            return $empty;
        }
        
        $tpl = new tplTemplatez($this->getTemplate('file_list.html'));
        
        // what date to display
        $date = 'ts_updated';
        if($by_page && strpos($manager->getSetting('entry_sort_order'), 'added') !== false) {
            $date = 'ts_posted';
        }        
        
        if($manager->getSetting('preview_show_hits')) {
            $tpl->tplSetNeededGlobal('show_hits');
        }        
        
        // subscribe
        $subsc_allowed = $this->isSubscriptionAllowed('allow_subscribe_entry', $manager);
        if($subsc_allowed) {
        
            $ids = $manager->getValuesString($rows, 'id');     
            $subscribed = ($ids) ? $manager->getEntrySubscribedByIds($ids, 2) : array();
            $tpl->tplSetNeededGlobal('subscribe');
                    
            //xajax
            $ajax = &$this->getAjax('entry');
            $ajax->view = &$this;
            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeFileResponse'));
        }
        
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
    
            $row['sid'] = $row['id'] . $type;
            $row['padding_value'] = ($row['title'] || $row['description']) ? 3 : 0;
            $row['margin_value'] = ($row['description']) ? 3 : 0;
            
            // title first, bold if exists
            if($row['title']) {
                $filename = $row['filename'];
                $row['filename'] = $row['title'];
                $row['title'] = $filename;
            }
            
            $row['filesize'] = WebUtil::getFileSize($row['filesize']);
            
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $ext = _substr($row['filename'], _strrpos($row['filename'], ".")+1);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, false, $ext);

            $row['description'] = nl2br($row['description']);
            
            // no preview if private
            if(!$this->getSummaryLimit($manager, $private)) {
                $row['description'] = '';
            }

            $tpl->tplAssign('updated_date', $this->getFormatedDate($row['ts_updated']));
            // $tpl->tplAssign('entry_link', $this->getLink('download', $this->category_id, $row['id']));
           
		   	
		   	$tpl->tplAssign('target', $this->isPrivateEntryLocked($manager->is_registered, $private) ? '_self' : '_blank');
		   	$tpl->tplAssign('entry_link', $this->getLink('file', $this->category_id, $row['id'], false, array('f'=>1)));
		   	$tpl->tplAssign('download_link', $this->getLink('file', $this->category_id, $row['id']));
            
            if($subsc_allowed) {
                if($manager->is_registered && isset($subscribed[$row['id']])) {
                    $tpl->tplAssign('subscribe_yes_display', 'none');
                    $tpl->tplAssign('subscribe_no_display', 'inline');
                } else {
                    $tpl->tplAssign('subscribe_yes_display', 'inline');
                    $tpl->tplAssign('subscribe_no_display', 'none');
                }                
            }
            
            $tpl->tplParse($row, 'row');
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
    
    
    function &_getCategoryEntries($manager, $num) {
            
        $manager->setSqlParams("AND cat.id = {$this->category_id}");
        $bp = $this->pageByPage($num, $manager->getEntryCount());
         
        $sort = $manager->getSortOrder();
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        $rows = $manager->getEntryList($bp->limit, $bp->offset, 'category');
        
        if(!$rows && !$this->num_subcategories) {
            $msg = $this->getActionMsg('success', 'no_category_files');
            return $msg;
        }
        
        $title = $this->meta_title;
        return $this->parseFileList($manager, $this->stripVars($rows), $title, $bp);
    }
    

    function &_getMostViewed($manager, $num) {
        $manager->setSqlParamsOrder('ORDER BY e.downloads DESC');        
        $rows = $manager->getEntryList($num, 0, 'index', 'FORCE INDEX (downloads)');
        return $this->parseFileList($manager, 
                                        $this->stripVars($rows), 
                                        $this->msg['most_downloaded_files_title_msg'],
                                        false,
                                        'most');
    }
    
    
    function &_getRecentlyPosted($manager, $num) {
        $manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');        
        $rows = $manager->getEntryList($num, 0, 'index', 'FORCE INDEX (date_updated)');
        return $this->parseFileList($manager, 
                                        $this->stripVars($rows), 
                                        $this->msg['recently_posted_files_title_msg'],
                                        false,
                                        'recent');
    }
}
?>