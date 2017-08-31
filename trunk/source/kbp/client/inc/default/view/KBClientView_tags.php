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

require_once 'core/base/SphinxModel.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchModel.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchModel_sphinx.php';


class KBClientView_tags extends KBClientView_common
{


    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_tags_msg'];
        $this->nav_title = $this->msg['menu_tags_msg'];
        
        $data = &$this->getTagsList($manager, $this->nav_title);
        
        return $data;
    }
    
    
    function &getTagsList($manager, $title) {
        
        $num_td = 4;
        $limit = 40;
        
        $sort_param = 'ORDER BY entry_num DESC';
        
        $bp_hidden = false;
        if(!empty($_GET['qf'])) {
            $str = addslashes(stripslashes($_GET['qf']));
            $bp_hidden = array('qf' => $_GET['qf']);
            
            
            if(SphinxModel::isSphinxOnSearch($str)) {
                $sphinx['match'] = $str;
                $sphinx['where'] = 'AND active = 1';
                
                $options = array('index' => 'tag', 'limit' => $limit, 'sort' => $sort_param);
                $params = KBClientSearchModel_sphinx::parseFilterSql($sphinx, $options);
                
                $manager->setSqlParams($params['where']);
            
                if (!empty($params['sort'])) {
                    $sort_param = $params['sort'];
                }
                
                if (!empty($params['count'])) {
                    $count = $params['count'];
                }
                
            } else {
                $manager->setSqlParams("AND title LIKE '%{$str}%' OR description LIKE '%{$str}%'");
            }
        }
        
        $manager->setSqlParamsOrder($sort_param);
        
        if (!isset($count)) {
            $count = $manager->getTagCount();
        }
        
        $by_page = $this->pageByPage($limit, $count, false, false, $bp_hidden);
        // echo '<pre>', print_r($by_page, 1), '</pre>';
        
        $rows = $manager->getTagList($by_page->limit, $by_page->offset);
        $rows = $this->stripVars($rows);

        $num = ($n = count($rows)) ? $n : 1;
        $rows = array_chunk($rows, $num_td);

        // td width
        if($num < $num_td) { $td_width = round(100/$num); } 
        else               { $td_width = round(100/$num_td); }
        
        
        $tpl = new tplTemplatez($this->getTemplate('tags_list.html'));

        foreach($rows as $k => $v) {
            $i = 0;

            foreach($v as $k1 => $v1) {

                $v1['td_width'] = $td_width;
                $more = array('s' => 1, 'q' => $v1['title'], 'in' => 'all', 'by' => 'keyword');
                $v1['tag_link'] = $this->getLink('search', false, false, false, $more);
                $v1['description'] = nl2br($v1['description']);

                $tpl->tplParse($v1, 'row_tr/row_td'); // parse nested

                $i ++;
            }

            $empty_cells_needed = ($num_td - $i) * 1;
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

        $form_hidden = '';
        if(!$this->controller->mod_rewrite) {
            $arr = array($this->controller->getRequestKey('view') => 'tags');
            $form_hidden = http_build_hidden($arr, true);
        }

        $tpl->tplAssign('hidden_search', $form_hidden);
        $tpl->tplAssign('form_search_action', $this->getLink('tags'));
        $tpl->tplAssign('qf', $this->stripVars(trim(@$_GET['qf']), array(), 'asdasdasda'));        
        
        $tpl->tplAssign('list_title', $title);
        
        // by page
        if($by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
}
?>