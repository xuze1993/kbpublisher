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

require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchHelper.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchEngine.php';
require_once 'eleontev/Util/LogUtil.php';

require_once 'core/common/CommonCustomFieldModel.php';
require_once 'core/common/CommonCustomFieldView.php';


class KBClientView_search_list extends KBClientView_search
{

    var $num_per_page = 10;
    var $view_manager = array();

    var $spell_suggest;
    var $spell_mistake;


    function &execute(&$manager) {

        $this->advanced_search = (isset($_GET['period']));
        $this->advanced_search = false;

        $this->home_link = true;
        $this->parse_form = false; //$top;
        $this->meta_title = $this->msg['search_result_msg'];
        $this->category_nav_generate = false;


        $search_msg = ($this->mobile_view) ? $this->msg['search_msg'] : $this->msg['advanced_search_msg'];
        if($this->advanced_search || $this->mobile_view) {
            $link = $this->getLinkToSearchForm();
            $nav = array($link => $search_msg, $this->msg['search_result_msg']);
        } else {
            $nav = $this->msg['search_result_msg'];
        }

        $this->nav_title = $nav;

        $in_vals = KBClientSearchHelper::getInValue($_GET, $manager);
        $in = $in_vals['in'];
        $by = $in_vals['by'];
		
        // echo '<pre>', print_r($in, 1), '</pre>';
        // echo '<pre>', print_r("================", 1), '</pre>';

        if ($manager->getSetting('search_spell_suggest')) {
            $_query = SphinxModel::getSphinxString($_GET['q']);
            
            $wildcards = array('*');
            if (SphinxModel::isSphinxOnSearch($_GET['q'])) {
                $wildcards[] = '?';
            }
            
            $special_query = false;
            foreach ($wildcards as $wildcard) {
                if (strpos($_query, $wildcard) !== false) {
                    $special_query = true;
                }
            }
            
            if (!$special_query) {
                $spell_suggest = $this->spellSuggest($_query, $manager);
                
                if ($spell_suggest) {
                    $this->spell_mistake = $_GET['q'];
                    $this->spell_suggest = $spell_suggest;
                    
                    if ($_query != $_GET['q']) {
                        $this->spell_suggest = 'sphinx:' . $this->spell_suggest;
                    }
                    
                    $_GET['q'] = $this->spell_suggest;
                }
            }
            
        }

        $file_manager = false;
        if(strpos($in, 'file') !== false) {
            $file_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'files');

        } elseif(strpos($in, 'trouble') !== false) {
            $file_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'trouble');
            
        } elseif(strpos($in, 'forum') !== false) {
            $file_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'forums');
        }
        
        $search_str = (!empty($_GET['q'])) ? $_GET['q'] : false;
        $this->engine_name = $this->getSearchEngineName($manager, $search_str);

        $data = '';
        if (!$this->mobile_view) {
            $data = $this->getListForm($manager, $file_manager, $in, $by);
        }

        $data .= $this->getList($manager, $file_manager, $in, $by);

        return $data;
    }


    function getList($manager, $extra_manager, $in, $by) {

        if(strpos($in, 'article') !== false) {
            return $this->getArticleList($manager, $by);

        } elseif(strpos($in, 'file') !== false) {
            return $this->getFileList($manager, $extra_manager);

        } elseif(strpos($in, 'news') !== false) {
            return $this->getNewsList($manager);

        } elseif(strpos($in, 'forum') !== false) {
            return $this->getForumList($manager, $in);
            
        } elseif(strpos($in, 'trouble') !== false) {
            return $this->getTroubleList($manager, $extra_manager);

        } else {
            return $this->getAllList($manager);
        }
    }


    function getListForm($manager, $file_manager, $in, $by) {

        $tpl = new tplTemplatez($this->getTemplate('search_list_form.html'));
        
        if($manager->getSetting('search_suggest')) {
            $tpl->tplSetNeeded('/search_suggest');
            $tpl->tplAssign('suggest_link', $this->controller->kb_path . 'endpoint.php?type=suggest');
        }
        
        $this->controller->getView('search_form');
        $view = new KBClientView_search_form($manager);
        
        $search_form = $view->getForm($manager, false);
        $tpl->tplAssign('search_form', $search_form);
        
        //xajax
        $ajax = &$this->getAjax('search');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getCategories', $ajax, 'ajaxGetCategories'));
        $xajax->registerFunction(array('getCustomFields', $ajax, 'ajaxGetCustomFields'));
        
        $manager->cf_manager = new CommonCustomFieldModel();
        
        //$params = $this->getSearchParams();
        $params = array();
        $data = array();

        // search in
        /*$range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_in_range');
        $data['in']['title'] = $this->msg['search_in_msg'];
        $data['in']['value'] = (isset($range_msg[$in])) ? $range_msg[$in] : $range_msg['all'];
        
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
        $by_msg = (isset($range_msg[$by])) ? $range_msg[$by] : $range_msg['all'];
        $data['in']['value'] = sprintf('%s (%s)', $data['in']['value'], $by_msg);

        // category
        if($in == 'article' || $in == 'file' || $in == 'forum') {
            if(!empty($params['c'])) {
                $data['c']['title'] = ($in == 'forum') ? $this->msg['forum_msg'] : $this->msg['category_msg'];
                $data['c']['value'] = array();

                if(strpos($in, 'article') !== false) {
                    $cats = &$manager->getCategorySelectRangeFolow();
                } else {
                    $cats = &$file_manager->getCategorySelectRangeFolow();
                }

                $child = (isset($params['cp'])) ? ' + ' . $this->msg['all_child_msg'] : '';
                foreach($params['c'] as $id) {
                    if(isset($cats[$id])) {
                        $data['c']['value'][] = $cats[$id] . $child;
                    }
                }

                $data['c']['value'] = $this->stripVars($data['c']['value']);
                $data['c']['value'] = implode('<br />', $data['c']['value']);
            }
        }

        // article type
        if($in == 'article') {
            if(!empty($params['et'])) {
                $data['et']['title'] = $this->msg['entry_type_msg'];
                $data['et']['value'] = array();
                $type = ListValueModel::getListRange('article_type', false);
                foreach($params['et'] as $id) {
                    if(isset($type[$id])) {
                        $data['et']['value'][] = $type[$id];
                    }
                }

                $data['et']['value'] = $this->stripVars($data['et']['value']);
                $data['et']['value'] = implode(', ', $data['et']['value']);
            }
        }

        // period
        if(isset($params['period']) && $params['period'] != 'all') {

            $posted = ($params['pv'] == 'u') ? $this->msg['period_updated_desc_msg']
                                             : $this->msg['period_posted_desc_msg'];

            if($params['period'] == 'custom') {
                $data['p']['title'] = $this->msg['dates_range_desc_msg'];

                require_once 'eleontev/HTML/DatePicker.php';
                $from = (!empty($params['is_from']))
                    ? $this->getFormatedDate(strtotime($_GET['date_from'])) : '...';
                $to = (!empty($params['is_to']))
                    ? $this->getFormatedDate(strtotime($_GET['date_to'])) : '...';

                $str = '%s %s - %s';
                $data['p']['value'] = sprintf($str, $posted, $from, $to);

            } elseif(preg_match("/last_(\d+)_(day|year)/", $params['period'])) {
                $data['p']['title'] = $this->msg['dates_range_desc_msg'];

                $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_period_range');
                $str = '%s %s';
                $data['p']['value'] = sprintf($str, $posted, $range_msg[$params['period']]);
            }
        }

        // custom
        if(!empty($params['custom'])) {

            foreach(array_keys($params['custom']) as $k) {
                if(empty($params['custom'][$k])) {
                    unset($params['custom'][$k]);
                }
            }
            
            if($params['custom']) {

                $cf_manager = new CommonCustomFieldModel();
                $cf_manager->etype = $manager->entry_type;
                $custom = CommonCustomFieldView::getCustomData($params['custom'], $cf_manager, 'checkbox');

                $data['custom']['title'] = $this->msg['search_extra_msg'];
                $data['custom']['value'] = array();

                $str = '%s: %s';
                foreach($custom as $k => $v) {
                    
                    // do not parse checkbox
                    if(strpos($v['value'], 'checkbox') === false) {
                        $v['value'] = $this->stripVars($v['value']);
                    }
                    
                    $data['custom']['value'][] = sprintf($str, $v['title'], $v['value']);   
                }
                
                $data['custom']['value'] = implode('<br />', $data['custom']['value']);
            }
        }


        // check if only in title or in tags and set select
        if(count($data) == 1 && $by != 'id') {
            $top_in_range = $this->getSearchInRange($manager);

            $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
            $by_msg = (isset($range_msg[$by])) ? $range_msg[$by] : $range_msg['all'];
            foreach($top_in_range as $k => $v) {
                $top_in_range[$k] = sprintf('%s (%s)', $v, $by_msg);
            }

            $select = new FormSelect();
            $select->setSelectName('in');
            $select->setSelectWidth(250);
            $select->setOnChangeSubmit(true);

            if(isset($top_in_range[$in])) {
                $select->setRange($top_in_range);
                $data['in']['value'] = $select->select($in);
                unset($params['in']); //remove from hidden
            }
        }

        if($data) {
            $tpl->tplSetNeededGlobal('search_options');
            foreach(array_keys($data) as $k) {
                $v = $data[$k];
                $tpl->tplParse($v, 'search_options_row');
            }
        }*/
        
        unset($params['q']);
        
        if(!$this->controller->mod_rewrite) {
            $params['View'] = 'search';
        }

        $a['hidden'] = http_build_hidden($params, true);
        $a['advanced_search_link'] = $this->getLinkToSearchForm();
        $a['action_link'] = $this->controller->getLink('search');
        
        // in 6.0 wrong translate tool need to convert to &gt; and &lt;
		// <b>+apple +(&gt;turnover &lt;strudel)</b><br />
		// <b>+apple +(>turnover <strudel)</b><br />
		$msg['body'] = AppMsg::getMsgMutliIni('text_msg.ini', 'public', 'search_help_' . $this->engine_name);
		$msg['body'] = preg_replace('#\+\(>(\w+)\s<(\w+)\)#', '+(&gt;$1 &lt;$2)', $msg['body']);
		
        $tpl->tplAssign('search_help_block', $msg['body']);

        // change to original if suggest
        if(!empty($this->spell_mistake)) {
            $tpl->tplAssign('q', $this->stripVars($this->spell_mistake, array()));
        }

        $tpl->tplParse($a);
        return $tpl->tplPrint(1);
    }


    // parse data with articles
    function &getArticleList($manager, $by = false) {
		
        $num_per_page = $this->num_per_page;

        $sengine = $this->getSearchEngine($manager, $_GET, 'article');
        $smanager = $sengine->manager;
        
        $bp = $this->getPageByPageObj('page', $num_per_page, $_GET);
        
        list($count, $rows) = $smanager->getArticleSearchData($bp->limit, $bp->offset, $by, $manager);
        
        $bp->countAll($count);

        // rows
        $rows = $this->stripVars($rows);
        $num_rows = count($rows);

        // log
        $exitcode = ($num_rows > 10) ? 11 : $num_rows;
        $smanager->logUserSearch($_GET, 1, $exitcode);


        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));
        $tpl->tplAssign('article_description_padding', 3);

        if(!$rows) {
            $msg = $this->getNoRecordsMsg();
            if ($this->mobile_view) {
                return $msg;
                
            } else {
                $tpl->tplAssign('msg', $this->getNoRecordsMsg());
            }

        } else {
            
            // redirect if one article and search by article id and not advanced search
            if($num_rows == 1 && $by == 'id' && count($this->getSearchParams()) <= 3) {

                $row = array_values($rows);
                $row = $row[0];
                $redirect = true;

                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                if($private) {
                    // 2 = display with lock sign
                    if($manager->getSetting('private_policy') == 2) {
                        if(!$manager->isUserPrivIgnorePrivate()) {
                            $redirect = false;
                        }
                    }
                }

                if($redirect) {
                    $this->controller->go('entry', $row['category_id'], $row['id']);
                }
            }


            $article_staff_padding = '0';

            // entry_type
            $type = ListValueModel::getListRange('article_type', false);

            $full_categories = &$manager->getCategorySelectRangeFolow();
            $full_categories = $this->stripVars($full_categories);

            //coments
            $comments = array();
            if($this->isCommentable($manager) && $manager->getSetting('preview_show_comments')) {
                $entry_ids = $manager->getValuesString($rows);
                $comments = $manager->getCommentsNumForEntry($entry_ids);
            }

            //rating
            $rating = array();
            if($this->isRatingable($manager) && $manager->getSetting('preview_show_rating')) {
                $entry_ids = $manager->getValuesString($rows);
                $rating = $manager->getRatingForEntry($entry_ids);
            }

            // if($manager->getSetting('preview_show_date')) {
                $tpl->tplSetNeededGlobal('show_date');
                $article_staff_padding = '3';
            // }

            if($manager->getSetting('preview_show_hits')) {
                $tpl->tplSetNeededGlobal('show_hits');
                $article_staff_padding = '3';
            }
            
            $keywords = $smanager->getKeywords();

            foreach(array_keys($rows) as $k) {
                $row = $rows[$k];
                //echo $row['category_name'];
                $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
                $row['category_link'] = $this->getLink('index', $cat_id);
                $row['full_category'] = $full_categories[$row['category_id']];

                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'article');

                $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
                $row['entry_link'] = $this->getLink('entry', $row['category_id'], $entry_id);
				if($by == 'attachment') {
					$row['entry_link'] .= '#anchor_entry_attachment';
				}

                $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);
                $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);

                $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                $row['title'] = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                $row['body'] = $smanager->highlightBody($row['body'], $_GET['q'], $keywords, $summary_limit);

                if($this->isRatingable($manager, $row['ratingable'])) {
                    if($manager->getSetting('preview_show_rating')) {
                        $tpl->tplSetNeeded('row/show_rate');
                        $row['rating'] = (isset($rating[$row['id']])) ? $rating[$row['id']]['rating'] : 0;
                        $row['rating'] = $this->_getRating($row['rating']);
                        $article_staff_padding = '3';
                    }
                }

                if($this->isCommentable($manager, $row['commentable'])) {
                    if($manager->getSetting('preview_show_comments')) {
                        $row['comment_num'] = (isset($comments[$row['id']])) ? $comments[$row['id']] : 0;
                        $tpl->tplSetNeeded('row/show_comments');
                        $article_staff_padding = '3';
                    }
                }


                $row['article_staff_padding'] = $article_staff_padding;
                $tpl->tplParse($row, 'row');
            }
        }

        // spell suggest
        if($this->spell_suggest) {
            $a = $this->getSpellSuggestData();
            $tpl->tplParse($a, 'spell_suggest');
        }

        // by page
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $smanager->count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }


        $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);
        $tpl->tplAssign('comment_num_msg', $this->msg['comment_num_msg']);

        $title = sprintf('%s - %s', $this->msg['search_result_msg'], $this->msg['entry_title_msg']);
        $tpl->tplAssign('list_title', $title);

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    // parse data with files
    function &getFileList($manager, $file_manager) {

        $num_per_page = $this->num_per_page;

        // reassign manager
        if($this->view_id != 'files') {
            $manager = &$file_manager;
        }
        
        $sengine = $this->getSearchEngine($manager, $_GET, 'file');
        $smanager = $sengine->manager;
        
        $bp = $this->getPageByPageObj('page', $num_per_page, $_GET);
        
        $count = 0; $rows = array();
        if($manager->getSetting('module_file')) {
            list($count, $rows) = $smanager->getFileSearchData($bp->limit, $bp->offset, $manager);
        }
        
        $bp->countAll($count);

        // rows
        $rows = $this->stripVars($rows);
        $num_rows = count($rows);

        // log
        $exitcode = ($num_rows > 10) ? 11 : $num_rows;
        $smanager->logUserSearch($_GET, 2, $exitcode);


        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));
        $tpl->tplAssign('article_description_padding', 3);

        if(!$rows) {
            $msg = $this->getNoRecordsMsg();
            if ($this->mobile_view) {
                return $msg;
                
            } else {
                $tpl->tplAssign('msg', $this->getNoRecordsMsg());
            }

        } else {

            $full_categories = &$manager->getCategorySelectRangeFolow();
            $full_categories = $this->stripVars($full_categories);


            $tpl->tplSetNeededGlobal('show_filesize');
            $article_staff_padding = '3';

            // the same as for article
            // if($manager->getSetting('preview_show_date')) {
                $tpl->tplSetNeededGlobal('show_date');
                $article_staff_padding = '3';
            // }

            if($manager->getSetting('preview_show_hits')) {
                $tpl->tplSetNeededGlobal('show_hits');
                $article_staff_padding = '3';
            }

            $keywords = $smanager->getKeywords();

            foreach(array_keys($rows) as $k) {
                $row = $rows[$k];

                // $row['padding_value'] = ($row['title'] || $row['description']) ? 3 : 0;
                // $row['margin_value'] = ($row['description']) ? 3 : 0;
                $row['filesize'] = WebUtil::getFileSize($row['filesize']);
                // $row['description'] = nl2br($row['description']);
                
                $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
                $row['category_link'] = $this->getLink('files', $cat_id);
                $row['full_category'] = $full_categories[$row['category_id']];

                $ext = _substr($row['filename'], _strrpos($row['filename'], ".")+1);
                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'file', $ext);

                $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);

				$row['entry_link_options'] = 'target="_blank"';
				if($this->isPrivateEntryLocked($manager->is_registered, $private)) {
					$row['entry_link_options'] = '';
				}
				
			   	$row['entry_link'] = $this->getLink('file', $this->category_id, $row['id'], false, array('f'=>1));
			   	$row['download_link'] = $this->getLink('file', $this->category_id, $row['id']);
				$tpl->tplSetNeeded('row/download_link');

                // adapt values for template
                // $row['title'] = $row['filename'];
                $row['views_num_msg'] = $this->msg['downloads_num_msg'];
                $row['hits'] = $row['downloads'];

                $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                $title = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                $filename = $smanager->highlightTitle($row['filename'], $_GET['q'], $keywords);
                $description = $smanager->highlightBody($row['description'], $_GET['q'], $keywords, $summary_limit);
                
                $row['title'] = (empty($title)) ? $filename : $title;
                $row['body'] = (empty ($title)) ?  '' : '<b>' . $filename . '</b><br />';
                $row['body'] .= nl2br($description);

                $row['article_staff_padding'] = $article_staff_padding;
                $tpl->tplParse($row, 'row');
            }
        }

        // spell suggest
        if($this->spell_suggest) {
            $a = $this->getSpellSuggestData();
            $tpl->tplParse($a, 'spell_suggest');
        }

        // by page
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $smanager->count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }


        $title = sprintf('%s - %s', $this->msg['search_result_msg'], $this->msg['file_title_msg']);
        $tpl->tplAssign('list_title', $title);

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function &getNewsList($manager) {

        $num_per_page = $this->num_per_page;

        // reassign manager
        if($this->view_id != 'news') {
            $manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'news');
        }
        
        
        $sengine = $this->getSearchEngine($manager, $_GET, 'news');
        $smanager = $sengine->manager;
        //$smanager->setSqlParams('AND ' . $manager->getPrivateSql('news'), 'category');
        
        $bp = $this->getPageByPageObj('page', $num_per_page, $_GET);
        
        $count = 0; $rows = array();
        if($manager->getSetting('module_news')) {
            list($count, $rows) = $smanager->getNewsSearchData($bp->limit, $bp->offset, $manager);
        }
        
        $bp->countAll($count);

        // rows
        $rows = $this->stripVars($rows);
        $num_rows = count($rows);

        // log
        $exitcode = ($num_rows > 10) ? 11 : $num_rows;
        $smanager->logUserSearch($_GET, 3, $exitcode);


        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));

        if(!$rows) {
            $msg = $this->getNoRecordsMsg();
            if ($this->mobile_view) {
                return $msg;
                
            } else {
                $tpl->tplAssign('msg', $this->getNoRecordsMsg());
            }

        } else {

            $tpl->tplSetNeededGlobal('show_date');
            $article_staff_padding = '3';

            // the same as for article
            if($manager->getSetting('preview_show_hits')) {
                $tpl->tplSetNeededGlobal('show_hits');
                $article_staff_padding = '3';
            }
            
            $keywords = $smanager->getKeywords();

            foreach(array_keys($rows) as $k) {
                $row = $rows[$k];

                $link = $this->getLink('news', date('Y', $row['ts_posted']));
                $row['category_link'] = preg_replace("#(news/)(\d{4})#", "$1c$2", $link);
                $row['full_category'] = $this->msg['news_title_msg'];

                $private = $this->isPrivateEntry($row['private'], false);
                $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'news');

                $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], false);
                $row['entry_link'] = $this->getLink('news', false, $entry_id);

                $row['updated_date'] = $this->getFormatedDate($row['date_posted']);
                
                $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                $row['title'] = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                $row['body'] = $smanager->highlightBody($row['body'], $_GET['q'], $keywords, $summary_limit);

                $row['article_staff_padding'] = $article_staff_padding;
                $tpl->tplParse($row, 'row');
            }
        }

        // spell suggest
        if($this->spell_suggest) {
            $a = $this->getSpellSuggestData();
            $tpl->tplParse($a, 'spell_suggest');
        }

        // by page
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $smanager->count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }

        $title = sprintf('%s - %s', $this->msg['search_result_msg'], $this->msg['news_title_msg']);
        $tpl->tplAssign('list_title', $title);

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }
    
    
	// parse data with forum
	function &getForumList($manager, $in) {

		$num_per_page = $this->num_per_page;
        
		// reassign manager
		if($this->view_id != 'forums') {
			$manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'forums');
		}
		
        
        $sengine = $this->getSearchEngine($manager, $_GET, 'forum');
        $smanager = $sengine->manager;
        
        $bp = $this->getPageByPageObj('page', $num_per_page, $_GET);
        
        $count = 0; $rows = array();
        if($manager->getSetting('module_forum') && BaseModel::isModule('forum')) {
            $count_method = ($in == 'forum') ? 'getForumMessageCount' : 'getForumTopicCount';
            list($count, $rows) = $smanager->getForumSearchData($bp->limit, $bp->offset, $manager, $count_method);
        }
        
        $bp->countAll($count);

        // rows
        $rows = $this->stripVars($rows);
        $num_rows = count($rows);

        // log
        $exitcode = ($num_rows > 10) ? 11 : $num_rows;
        $smanager->logUserSearch($_GET, 2, $exitcode);
        
        
        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));
        $tpl->tplAssign('article_description_padding', 3);
        
        
        if(!$rows) {
            $msg = $this->getNoRecordsMsg();
            if ($this->mobile_view) {
                return $msg;
                
            } else {
                $tpl->tplAssign('msg', $this->getNoRecordsMsg());
            }

        } else {
            
			$full_categories = &$manager->getCategorySelectRangeFolow();
			$full_categories = $this->stripVars($full_categories);
            
            // if($manager->getSetting('preview_show_date')) {
                $tpl->tplSetNeededGlobal('show_date');
                $article_staff_padding = '3';
            // }

            //if($manager->getSetting('preview_show_hits')) {
                $tpl->tplSetNeededGlobal('show_hits');
                $article_staff_padding = '3';
            //}
            
			foreach(array_keys($rows) as $k) {
				$row = $rows[$k];
				
                $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
				$row['category_link'] = $this->getLink('forums', $cat_id);
				$row['full_category'] = $full_categories[$row['category_id']];

				$private = $this->isPrivateEntry($row['private'], $row['category_private']);
                
                $icon_key = ($row['active'] == 2) ? 'topic_closed' : 'topic';
				$row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $icon_key);	

				$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
				
				$more = array();
				if($in == 'forum') {
				    $more['message_id'] = $row['message_id'];
				}

				$row['entry_link'] = $this->getLink('topic', false, $entry_id, false, $more);
				$row['updated_date'] = $this->getFormatedDate($row['ts_updated']);
                
                $row['comment_num_msg'] = $this->msg['forum_message_num_msg'];
                $row['comment_num'] = $row['posts'];
                $tpl->tplSetNeeded('row/show_comments');
                
                $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                $row['body'] = DocumentParser::getSummarySearch($row['message'], $_GET['q'], $summary_limit);
                $row['title'] = DocumentParser::getTitleSearch($row['title'], $_GET['q']);
               
				$row['article_staff_padding'] = $article_staff_padding;
				$tpl->tplParse($row, 'row');
			}
        }
        
        // spell suggest
        if($this->spell_suggest) {
            $a = $this->getSpellSuggestData();
            $tpl->tplParse($a, 'spell_suggest');
        }
		
        // by page
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $smanager->count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }
		
		$title = sprintf('%s - %s', $this->msg['search_result_msg'], $this->msg['forum_title_msg']);
		$tpl->tplAssign('list_title', $title);		

		$tpl->tplParse();

		return $tpl->tplPrint(1);
	}


    function getLimitVars($bp) {
        
        $ret = array(
            'limit' => $bp->limit,
            'offset' => $bp->offset,
            'slice_offset' => 0
        );
        
        $multiple = ($this->engine_name == 'mysql');
        if($multiple && $bp->cur_page > 1) {
            $ret = array(
                'limit' => $bp->limit * $bp->cur_page,
                'offset' => 0,
                'slice_offset' => $bp->limit * $bp->cur_page - $bp->limit
            );
        }
        
        return $ret;
    }


    // parse data with all
    function &getAllList($manager) {

        $num_per_page = $this->num_per_page;
        //$num_per_page = 2;

        $trows2 = array();
        $trows2['article'] = array('index', 'entry',    false,   'article', $this->msg['entry_title_msg']);
        $trows2['file']    = array('files', 'download', 'file',  'file',    $this->msg['file_title_msg']);
        $trows2['news']    = array('news',  'news',     'news',  'news',    $this->msg['news_title_msg']);
        $trows2['forum']   = array('forums', 'topic',   'topic', 'forum',   $this->msg['forum_title_msg']);

        // options
        $search = $_GET;
        foreach(array_keys($search) as $k) {
            if(!in_array($k, array('q', 'period', 'pv', 'date_from', 'date_to', 'is_from', 'is_to', 'by'))) {
               unset($search[$k]);
            }
        }

        $trows = array();
        $search['in'] = 'article';
        $sengine = $this->getSearchEngine($manager, $search, 'all');
        
        $bp = $this->getPageByPage($num_per_page);
        $limits = $this->getLimitVars($bp);
        
        list($count, $rows, $managers) = $sengine->getAllSearchData($manager, $this->controller, 
                                                            $search, $limits['limit'], $limits['offset']);
        
        $smanager = $sengine->manager;
        
        // articles
        if(!empty($rows['article'])) {
            $rows['article'] = $this->stripVars($rows['article']);
            
            $article_type = ListValueModel::getListRange('article_type', false);
            $full_categories['article'] = &$manager->getCategorySelectRangeFolow();
            $full_categories['article'] = $this->stripVars($full_categories['article']);

            foreach(array_keys($rows['article']) as $entry_id) {
                @$score = $rows['article'][$entry_id]['score'];
                $trows[] = array($score, 'article', $entry_id, 1);
            }
        }


        // files
        if(!empty($rows['file'])) {
            $rows['file'] = $this->stripVars($rows['file']);
            
            $full_categories['file'] = $managers['file']->getCategorySelectRangeFolow();
            $full_categories['file'] = $this->stripVars($full_categories['file']);

            foreach(array_keys($rows['file']) as $entry_id) {
                @$score = $rows['file'][$entry_id]['score'];
                $trows[] = array($score, 'file', $entry_id, 2);
            }
        }


        // news
        if(!empty($rows['news'])) {
            $rows['news'] = $this->stripVars($rows['news']);
            
            foreach(array_keys($rows['news']) as $entry_id) {
                @$score = $rows['news'][$entry_id]['score'];
                $trows[] = array($score, 'news', $entry_id, 3);
            }
        }
        
        
        // forum
        if(!empty($rows['forum'])) {
            $rows['forum'] = $this->stripVars($rows['forum']);
            
            $full_categories['forum'] = $managers['forum']->getCategorySelectRangeFolow();
            $full_categories['forum'] = $this->stripVars($full_categories['forum']);
            
            foreach(array_keys($rows['forum']) as $entry_id) {
                @$score = $rows['forum'][$entry_id]['score'];
                $trows[] = array($score, 'forum', $entry_id, 4);
            }
        }
        
        uasort($trows, 'kbpSortByScore');
        $trows_count = array_slice($trows, $limits['slice_offset'], 11, true);
        $trows = array_slice($trows, $limits['slice_offset'], $num_per_page, true);
        
        // log
        $exitcode = (count($trows_count) > 10) ? 11 : count($trows);
        $smanager->logUserSearch($_GET, 0, $exitcode);

        // parse
        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));
        $tpl->tplAssign('article_description_padding', 3);
        $tpl->tplSetNeededGlobal('show_date');

        if(!$trows) {
            $msg = $this->getNoRecordsMsg();
            if ($this->mobile_view) {
                return $msg;
                
            } else {
                $tpl->tplAssign('msg', $this->getNoRecordsMsg());
            }

        } else {
            $keywords = $smanager->getKeywords();
            
            foreach(array_keys($trows) as $k) {

                $trow = $trows[$k];
                $entry_id = $trow[2];
                $record_type = $trow[1];
                $trow2 = $trows2[$record_type];
                $row = $rows[$record_type][$entry_id];

                if($record_type === 'news') {
                    $row['category_link'] = $this->getLink($trow2[0], $row['category_id']);
                    
                    // required for rewrite
                    $row['category_link'] = preg_replace("#(news/)(\d{4})#", "$1c$2", $row['category_link']);
                    $row['full_category'] = $trow2[4].' -> '.$row['category_id'];
                } else {
                    
                    $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
                    $row['category_link'] = $this->getLink($trow2[0], $cat_id);
                    $row['full_category'] = $trow2[4].' -> '.$full_categories[$record_type][$row['category_id']];
                }

                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                $ext = false;
                if(isset($row['filename'])) {
                    $ext = _substr($row['filename'], _strrpos($row['filename'], ".")+1);
                }

                // $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $trow2[2], $ext);
                $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $record_type, $ext);
                $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);

                $entry_id = $row['id'];
                if($record_type !== 'file') {
                    $url_title = (isset($row['url_title'])) ? $row['url_title'] : '';
                    $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $url_title);
                }
                
                if($record_type === 'forum') {
                    $more = array('message_id' => $row['message_id']);
                    $row['entry_link'] = $this->getLink($trow2[1], false, $entry_id, false, $more);
                    $row['body'] = $row['message'];
                    
                } else {
                    $row['entry_link'] = $this->getLink($trow2[1], false, $entry_id);
                }


                if($record_type === 'article') {
                    $row['entry_id']
                        = $this->getEntryPrefix($row['id'], $row['entry_type'], $article_type, $manager);
                }

                if($record_type === 'file') {
                    $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                    $title = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                    $filename = $smanager->highlightTitle($row['filename'], $_GET['q'], $keywords);
                    $description = $smanager->highlightBody($row['description'], $_GET['q'], $keywords, $summary_limit);
                    
                    $row['title'] = (empty($title)) ? $filename : $title;
                    $row['body'] = (empty($title)) ?  '' : '<b>' . $filename . '</b><br />';
                    $row['body'] .= nl2br($description);
                    
					$row['entry_link_options'] = 'target="_blank"';
					if($this->isPrivateEntryLocked($manager->is_registered, $private)) {
						$row['entry_link_options'] = '';
					}
					
				   	$row['entry_link'] = $this->getLink('file', $this->category_id, $row['id'], false, array('f'=>1));
				   	$row['download_link'] = $this->getLink('file', $this->category_id, $row['id']);
					
					$tpl->tplSetNeeded('row/download_link');

                } else {
                    $summary_limit = $this->getSummaryLimit($manager, $private, 300);
                    $row['title'] = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                    $row['body'] = $smanager->highlightBody($row['body'], $_GET['q'], $keywords, $summary_limit);
                }

                $row['article_staff_padding'] = 3;
                $tpl->tplParse($row, 'row');
            }
        }


        // search results
        $params = $this->getSearchParams(false);
        $params['s'] = 1;
        foreach($count as $k => $v) {
            if ($v == 0) {
                continue;
            }
            
            $a = array();
            $trow2 = $trows2[$k];

            $params['in'] = $trow2[3];
            $sign = ($this->controller->mod_rewrite) ? '?' : '&';
            $link = $this->getLink('search') . $sign . http_build_query($params);

            $a['search_in_link'] = $link;
            $a['search_in_title'] = $trow2[4];

            $tpl->tplParse($a, 'row_count');
        }


        // spell suggest
        if($this->spell_suggest) {
            $a = $this->getSpellSuggestData();
            $tpl->tplParse($a, 'spell_suggest');
        }
        
        $bp->countAll(array_sum($count));

        $count_limit = $smanager->count_limit*3;
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }


        $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);
        $tpl->tplAssign('comment_num_msg', $this->msg['comment_num_msg']);
        $tpl->tplAssign('list_title', $this->msg['search_result_msg']);

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    // will be used in quick responce
    function &getEntryListQuickResponce($manager, $values) {
        
        $this->engine_name = $this->getSearchEngineName($manager, 'always');
        
        if ($this->engine_name == 'sphinx') {
            $keywords = explode(' ', $values['q']);
            $values['q'] = implode(' | ', $keywords);
        }
        
        $sengine = $this->getSearchEngine($manager, $values);
        $smanager = $sengine->manager;
        
        list($count, $rows) = $smanager->getArticleSearchData(5, 0);
        
        // $rows = $this->stripVars($rows, array('body'), 'not_display_123');

        if(!$rows) {
            $a = false; return $a;
        }

        $utf_replace = true;
        if(strtolower($this->encoding) != 'utf-8') {
            $utf_replace = false;
        }

        if($utf_replace) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';
        }


        $tpl = new tplTemplatez($this->template_dir . 'article_list_responce.html');
        $tpl->tplAssign('article_description_padding', 2);

        // entry_type
        $type = ListValueModel::getListRange('article_type', false);

        $full_categories = &$manager->getCategorySelectRangeFolow();
        $full_categories = $this->stripVars($full_categories);
        
        $keywords = $smanager->getKeywords();
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];

            $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
            $row['category_link'] = $this->getLink('index', $cat_id);
            $row['full_category'] = $full_categories[$row['category_id']];

            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $row['category_id'], $entry_id);

            $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);
            $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);

            $summary_limit = $this->getSummaryLimit($manager, $private, 300);
            $row['title'] = $smanager->highlightTitle($row['title'], $values['q'], $keywords);
            $row['body'] = $smanager->highlightBody($row['body'], $values['q'], $keywords, $summary_limit);

            // 2011-01-11 without this if bad utf8 IE gives xml error,
            // firefox does not display anything
            if($utf_replace) {
                if(!utf8_compliant($row['title'])) {
                    $row['title'] = utf8_bad_replace($row['title'], '?');
                }

                if(!utf8_compliant($row['body'])) {
                    $row['body'] = utf8_bad_replace($row['body'], '?');
                }
            }

            $tpl->tplParse($row, 'row');
        }


        $tpl->tplAssign('base_href', $this->controller->kb_path);
        $tpl->tplAssign('msg', $this->getActionMsg('success', 'quick_response', 'xml'));
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }


    function getSearchEngine($manager, $values, $entry_type = 'article') {
        return KBClientSearchEngine::factory($this->engine_name, $manager, $values, $entry_type);
    }
    
    
    function getSearchEngineName($manager, $search_str) {
        $sphinx = SphinxModel::isSphinxOnSearch($search_str, $manager->setting);
        return ($sphinx) ? 'sphinx' : 'mysql';
    }


    function getRelevancy($score, $cur_page, $first_row) {

        if($cur_page == 1 && empty($first_row)) {
            $_SESSION['biggest_score_'] = $score;
        }

        if($_SESSION['biggest_score_'] != 0) {
            $score = $score/$_SESSION['biggest_score_']*100;
        } else {
            $score = '100'; // if only one record
        }

        $ret = sprintf('%01.2f%s ', $score, '%'); // [%01.0f%s]

        return $ret;
    }


    function getNoRecordsMsg() {
        require_once 'eleontev/HTML/BoxMsg.php';
        $str = '<a href="%s">%s</a>';
        $vars['link'] = sprintf($str, $this->getLinkToSearchForm(), $this->msg['advanced_search_msg']);
        return AppMsg::afterActionBox('no_search_result', 'error', 'public', $vars);
    }


    function getSearchParams($strip = true) {
        $arr = $_GET;
        unset($arr['s']); // ?
        unset($arr['sb']); // search button
        if(isset($arr['bp'])) {
            unset($arr['bp']);
        }

        if(isset($arr['q'])) {
            $arr['q'] = trim($arr['q']);
        }

        foreach($this->controller->query as $v) {
            unset($arr[$v]);
        }
        
        if (!empty($arr['topic_id'])) {
            $_GET['q'] = sprintf('topic:%s %s', $arr['topic_id'], $arr['q']);
            unset($_GET['topic_id']);
            unset($arr['topic_id']);
        }

        if($strip) {
            $arr = $this->stripVars($arr, array(), 'qweqweqe'); // 3 param for stripslashes
        }

        return $arr;
    }


    function getLinkToSearchForm($msg = false) {
        $arr = $this->getSearchParams(false);
        $sign = ($this->controller->mod_rewrite) ? '?' : '&';
        return $this->controller->getLink('search', false, false, $msg) . $sign . http_build_query($arr);
    }


    function goToSearchForm($msg = false) {
        $link = $this->controller->_replaceArgSeparator($this->getLinkToSearchForm($msg));
        header("Location: " . $link);
        exit;
    }


    function goToSearchFormNoRecords() {
        $link = $this->controller->_replaceArgSeparator($this->getLinkToSearchForm('no_search_result'));
        header("Location: " . $link);
        exit;
    }


    // BY PAGE // -------------------------------

    // function getPageByPage($limit, $count) {
    //
    //     $bp = $this->getPageByPageObj('page', $limit, $_GET);
    //     $bp->countAll($count);
    //
    //     return $bp;
    // }


    function &getPageByPage($limit, $multiple = false) {

        $bp = $this->getPageByPageObj('page', $limit, $_GET);
        if($multiple) {
            $bp->setMultiple(4);
        }

        return $bp;
    }


    function getSearchResult($bp, $count_limit) {
        $msg = $this->msg['search_found_msg'];
        if($count_limit <= $bp->num_records) {
            $msg = $this->msg['search_found_about_msg'];
        }

        return sprintf($msg, $bp->num_records);
    }


    function isPageByPageBottom($bp) {
        return ($bp->num_pages > 1);
    }


    // SPELL SUGGEST // ---------------------------

    // return spell suggest if any, false otherwise
    function spellSuggest($str, $manager) {
        
        require_once 'eleontev/SpellSuggest.php';
        require_once APP_MODULE_DIR . '/setting/public_setting/SettingValidatorPublic.php';

        $ret = false;

        // ignore too small or empty
        $str = trim($str);
        if(empty($str) || _strlen($str) <= 3) {
            return $ret;
        }
        
        // ignore suggest, clicked on search instead
        if(isset($_COOKIE['kb_spell_ignore_'])) {
            if($_COOKIE['kb_spell_ignore_'] == md5($str)) {
                return $ret;
            }
        }

        $spell_checker = $manager->getSetting('search_spell_suggest');

        // validate, enchant could be exported from cloud
        $checkers = array('pspell', 'enchant'); 
        if(in_array($spell_checker, $checkers)) {
            $method = sprintf('validate%s', ucwords($spell_checker));
            $val = SettingValidatorPublic::$method($manager->setting);
            if(is_array($val)) {
                return $ret;
            }
        }

        $method = sprintf('get%sSuggest', ucwords($spell_checker));
        if (method_exists($this, $method)) {
            $ret = $this->$method($str, $manager);
        }
        
        if (!empty($ret)) {
            $best = key($ret);
            return ($str == $best) ? false : $best;
        }
        
        return $ret;
    }


    function getPspellSuggest($str, $manager) {
        
        $dictionary = $manager->getSetting('search_spell_pspell_dic');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);

        return SpellSuggest_pspell::suggest($dictionary, $custom_words, $str);
    }


    function getBingSuggest($str, $manager) {
        
        $key = $manager->getSetting('search_spell_bing_spell_check_key');
        $url = $manager->getSetting('search_spell_bing_spell_check_url');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);

        return SpellSuggest_bing::suggest($key, $url, $str, $custom_words);
    }


    function getEnchantSuggest($str, $manager) {
        
        $provider = $manager->getSetting('search_spell_enchant_provider');
        $dictionary = $manager->getSetting('search_spell_enchant_dic');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);

        return SpellSuggest_enchant::suggest($provider, $dictionary, $custom_words, $str);
    }


    function getSpellSuggestData() {

        $suggest = $this->spell_suggest;
        $mistake = $this->spell_mistake;

        $a = array();

        $sign = ($this->controller->mod_rewrite) ? '?' : '&';
        $params = $this->getSearchParams(false);
        $params['s'] = 1;

        $params['q'] = $suggest;
        $link = $this->getLink('search') . $sign . http_build_query($params);
        $a['suggest_link'] = $link;
        $a['suggest_str'] = $this->stripVars($suggest, array());

        $params['q'] = $mistake;
        $link = $this->getLink('search') . $sign . http_build_query($params);
        $a['mistake_link'] = $link;
        $a['mistake_str'] = $this->stripVars($mistake, array());
        $a['mistake_str_encoded'] = md5($mistake);

        return $a;
    }

}


function kbpSortByScore($a, $b) {
    return $a[0] < $b[0];
}
?>