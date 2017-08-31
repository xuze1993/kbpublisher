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


class KBClientView_print extends KBClientView_common
{

    function execute($manager) {
        
        if($this->view_id == 'print-glossary') {
            return $this->getGlossary($manager);
        
        } elseif($this->view_id == 'print-cat') {
            return $this->getFaq($manager);
        
        } elseif($this->view_id == 'print-news') {
            return $this->getNews($manager);
        
        } elseif($this->view_id == 'print-topic') {
            return $this->getTopic($manager);
        
        } elseif($this->view_id == 'print-trouble') {
            return $this->getTrouble($manager);
        
        } elseif($this->view_id == 'print-step') {
            return $this->getTroubleStep($manager);
        
        } else {
            if (!empty($_GET['id'])) {
                return $this->getArticleList($manager);
                
            } else {
                return $this->getArticle($manager);
            }
        }
    }
    
    
    function getArticle($manager, $row = false) {
        
        if (!$row) {
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
        }
        
        $row = $this->stripVars($row);
        if(empty($row)) { return; }
        
        $this->meta_title = $row['title'];
        $related = &$manager->getEntryRelatedInline($this->entry_id);
        
        $tpl = new tplTemplatez($this->template_dir . 'article_print.html');
    
    
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }        
        
        if(DocumentParser::isLink($row['body'])) {
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                        $related, $row['id'], $this->controller);
        }

        if(DocumentParser::isCode($row['body'])) {
            if ($this->view_id == 'send') {
                DocumentParser::parseCode($row['body'], $manager, $this->controller);     
            } else {
                DocumentParser::parseCodePrint($row['body']);
            }  
        }
        
        if(DocumentParser::isCode2($row['body'])) {
            DocumentParser::parseCode2($row['body'], $this->controller);
        }
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows, true);

        $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
        $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
        $tpl->tplAssign('custom_tmpl_bottom_block', $this->parseCustomData($custom_data[3], 3));
        
        
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$row['category_id']];
        $tpl->tplAssign('category_title_full', $full_path);    
        $tpl->tplAssign('category_title', $row['category_name']);
		
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
        $tpl->tplAssign('entry_link', $this->getLink('entry', $this->category_id, $entry_id));        
        
		$tpl->tplAssign('formated_date', $this->getFormatedDate($row['ts_updated']));
        $tpl->tplAssign('revision', $manager->getRevisionNum($this->entry_id));
        
        $tpl->tplParse(array_merge($row, $this->msg));
        return $tpl->tplPrint(1);
    }
    
    
    function getArticleList($manager) {
        
        $manager->setSqlParams(sprintf("AND e.id IN (%s)", implode(',', $_GET['id'])));
        $rows = $manager->getEntryList(-1, -1);
        
        $data = array();
        foreach ($rows as $row) {
            $this->entry_id = $row['id'];
            $data[] = $this->getArticle($manager, $row);
        }
        
        return implode('<div style="page-break-after: always;"></div>', $data);
    }
    
    
    function getGlossary($manager) {
        
        $title = $manager->getSetting('header_title') . ' - ' . $this->msg['menu_glossary_msg'];
        $this->meta_title = $title;
        
        $tpl = new tplTemplatez($this->template_dir . 'glossary_print.html');
        
        if(isset($_GET['let'])) {
            $l = addslashes(urldecode($_GET['let']));
            $manager->setSqlParams("AND phrase LIKE '$l%'");
        }
        
        $rows = $manager->getGlossary(30, $this->entry_id);
        $rows = $this->stripVars($rows, array('definition'));
            
        foreach($rows as $k => $v) {
            DocumentParser::parseCurlyBracesSimple($v['definition']);
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('title', $title);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getFaq($manager) {
    
        $title = $this->stripVars($manager->categories[$this->category_id]['name']);
        $this->meta_title = $title;
    
        $category_id = $this->category_id;
    
        $manager->setSqlParams("AND cat.id = '{$category_id}'");
        $manager->setSqlParamsOrder('ORDER BY e.sort_order');
        $rows = $manager->getEntryList(-1, 0, 'category');
        $rows = $this->stripVars($rows);
    
        $ids = $manager->getValuesString($rows, 'id');
        $related = &$manager->getEntryRelatedInline($ids);
    
        $tpl = new tplTemplatez($this->template_dir . 'article_print_faq.html');   
        
        foreach(array_keys($rows) as $k) {
            
            if(DocumentParser::isLink($rows[$k]['body'])) {
                DocumentParser::parseLink($rows[$k]['body'], array($this, 'getLink'), $manager, 
                                            $related, $rows[$k]['id'], $this->controller);
            }
            
            if(DocumentParser::isTemplate($rows[$k]['body'])) {
                DocumentParser::parseTemplate($rows[$k]['body'], array($manager, 'getTemplate'));
            }            
            
            DocumentParser::parseCurlyBraces($rows[$k]['body']);
            
            $tpl->tplParse($rows[$k], 'row');
        }
        
                
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$category_id];
        $tpl->tplAssign('category_title_full', $full_path);
        $tpl->tplAssign('entry_link', $this->getLink('index', $this->category_id, $this->entry_id));
        $tpl->tplAssign('category_title', $title);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getNews($manager) {
        
        $row = $manager->getNewsById($this->entry_id);
        $row = $this->stripVars($row);
        if(empty($row)) { return; }
        
        $this->meta_title = $row['title'];
        
        $tpl = new tplTemplatez($this->template_dir . 'news_entry_print.html');
        
        $row['formatted_date'] = $this->getFormatedDate($row['date_posted']);
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom    
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows);

        $row['custom_tmpl_top'] = $this->parseCustomData($custom_data[1], 1);
        $row['custom_tmpl_bottom'] = $this->parseCustomData($custom_data[2], 2);
        
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title']);
        $tpl->tplAssign('entry_link', $this->getLink('news', false, $entry_id));
        
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    
    
    // function getTrouble($manager) {
    //
    //     $padding = 25;
    //
    //     $entry = $manager->getEntry($this->entry_id);
    //     $rows = $manager->getSteps($this->entry_id);
    //     if(empty($row)) { return; }
    //
    //     $this->meta_title = $row['title'];
    //
    //
    //     $tpl = new tplTemplatez($this->template_dir . 'trouble_entry_print.html');
    //
    //
    //     $manager->tbl->related_to_entry = $manager->tbl->article_to_step;
    //     $manager->tbl->entry = $manager->tbl->kb_entry;
    //     $manager->tbl->category = $manager->tbl->kb_category;
    //
    //     //$related = &$manager->getEntryRelatedInline(implode(',', array_keys($rows)));
    //     $related = &$manager->getEntryRelatedInline($this->entry_id);
    //
    //
    //     $tree = new TreeHelper();
    //     foreach($rows as $id => $row) {
    //         $tree->setTreeItem($id, $row['parent_id']);
    //     }
    //
    //     $tree_helper = $tree->getTreeHelper(0);
    //
    //     $step_num = array();
    //
    //     foreach($tree_helper as $id => $level) {
    //
    //         // step num
    //         if ($rows[$id]['active']) {
    //             if (!empty($step_num)) {
    //                 $max_level = max(array_keys($step_num));
    //                 $levels_to_clear = $max_level - $level;
    //
    //                 for ($i = 0;$i < $levels_to_clear; $i ++) {
    //                     unset($step_num[count($step_num) - 1]);
    //                 }
    //             }
    //
    //
    //             if (!isset($step_num[$level])) {
    //                 $step_num[$level] = 0;
    //             }
    //             $step_num[$level] ++;
    //
    //             $v['step_num'] = implode('.', $step_num);
    //         }
    //
    //
    //         $v['title'] = $rows[$id]['title'];
    //         $v['padding'] = $padding*$level;
    //         $v['padding_category'] = 5;
    //
    //         if(DocumentParser::isLink($rows[$id]['body'])) {
    //             DocumentParser::parseLink($rows[$id]['body'], array($this, 'getLink'), $manager,
    //                                         $related, $id, $this->controller);
    //         }
    //
    //         if(DocumentParser::isTemplate($rows[$id]['body'])) {
    //             DocumentParser::parseTemplate($rows[$id]['body'], array($manager, 'getTemplate'));
    //         }
    //
    //         DocumentParser::parseCurlyBraces($rows[$id]['body']);
    //
    //         $tpl->tplParse(array_merge($v, $rows[$id]), 'row_title');
    //         $tpl->tplParse(array_merge($v, $rows[$id]), 'row_body');
    //     }
    //
    //     $full_path = &$manager->getCategorySelectRangeFolow();
    //     $full_path = $full_path[$entry['category_id']];
    //     $tpl->tplAssign('category_title_full', $full_path);
    //     $tpl->tplAssign('entry_link', $this->getLink('trouble', $this->category_id, $this->entry_id));
    //
    //     $tpl->tplParse($entry);
    //     return $tpl->tplPrint(1);
    // }
    //
    //
    // function getTroubleStep($manager) {
    //
    //     $row = &$manager->getStepById($this->entry_id, $this->category_id);
    //     $row = $this->stripVars($row);
    //     if(empty($row)) { return; }
    //
    //     $this->meta_title = $row['title'];
    //     $entry = &$manager->getEntry($row['entry_id']);
    //
    //     $tpl = new tplTemplatez($this->template_dir . 'trouble_step_print.html');
    //
    //
    //     if(DocumentParser::isTemplate($row['body'])) {
    //         DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
    //     }
    //
    //     if(DocumentParser::isLink($row['body'])) {
    //         DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager,
    //                                     $related, $row['id'], $this->controller);
    //     }
    //
    //     if(DocumentParser::isCode($row['body'])) {
    //         if ($this->view_id == 'send') {
    //             DocumentParser::parseCode($row['body'], $manager, $this->controller);
    //         } else {
    //             DocumentParser::parseCodePrint($row['body']);
    //         }
    //     }
    //
    //     DocumentParser::parseCurlyBraces($row['body']);
    //
    //     $full_path = &$manager->getCategorySelectRangeFolow();
    //     $full_path = $full_path[$entry['category_id']];
    //     $tpl->tplAssign('category_title_full', $full_path);
    //     $tpl->tplAssign('entry_link', $this->getLink('trouble', $this->category_id, $this->entry_id));
    //
    //     $tpl->tplParse(array_merge($row, $this->msg));
    //     return $tpl->tplPrint(1);
    // }
    
    
    function getTopic($manager) {
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        
        $row = $this->stripVars($row);
        if(empty($row)) {
            return;
        }
        
        $this->meta_title = $row['title'];
        
        $tpl = new tplTemplatez($this->template_dir . 'forum_topic_print.html');
        
        $messages = $manager->getEntryMessages($this->entry_id);
        
        foreach($messages as $id => $msg) {
            $v['message'] = $msg['message'];
            
            $v['date_posted_formatted'] = $this->getFormatedDate($msg['date_posted'], 'datetime');
            $v['user'] = $msg['first_name'] . ' ' . $msg['last_name'];
            
            $v['by_msg'] = $this->msg['by_msg'];
            
            $tpl->tplParse($v, 'row');
        }
        
        $status_list = ListValueModel::getListSelectRange('forum_status', false);
        $tpl->tplAssign('status_string', $status_list[$row['active']]);
        
        $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($row['date_posted'], 'datetime'));
        
        if(DocumentParser::isCode2($tpl->parsed['row'])) {
            DocumentParser::parseCode2($tpl->parsed['row'], $this->controller);
        }
        
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$row['category_id']];
        $tpl->tplAssign('category_title_full', $full_path);
        $tpl->tplAssign('category_title', $row['category_name']);
		
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title']);
        $tpl->tplAssign('entry_link', $this->getLink('topic', $this->category_id, $entry_id));
        
        $tpl->tplParse(array_merge($row, $this->msg));
        return $tpl->tplPrint(1);
    }

}
?>