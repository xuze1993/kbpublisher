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

class KBClientView_category extends KBClientView_common
{
    
    var $padding = 15; // child's padding
    var $children_shown = 3; // subcategories to display by default
    var $num_td = 3; // columns
    var $top_category_padding = array('column' => 15, 'tree' => 5); // depends on a view
    

    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['category_title_msg'];
        $this->nav_title = $this->msg['category_title_msg'];
        
        $data = &$this->getCategoriesList($manager, $this->nav_title);
        
        return $data;
    }
    
    
    function &getCategoriesList($manager, $title) {
        
        $rows = $manager->categories;
        $rows = $this->stripVars($rows);
        
        // if (isset($_SESSION['kb_category_view_']) && $_SESSION['kb_category_view_'] == 'tree') {
        $view = (isset($_COOKIE['kb_category_view_']) && $_COOKIE['kb_category_view_'] == 'tree') ? 'tree' : 'column';
        
        if ($view == 'tree') {
            $this->num_td = 1;
        }
        
        $tree = $manager->getTreeHelperArray($manager->categories);
        $top_ids = array();
        $top_children = array();
        foreach($tree as $id => $level) {
            if($level == 0) {
                $top_ids[] = $id;
                $top_id = $id;
                
            } else {
                $top_children[$top_id][] = $id;
            }
        }
        
        $num = ($n = count($top_ids)) ? $n : 1;
        $rows = array_chunk($top_ids, $this->num_td);

        // td width
        if($num < $this->num_td) {
            $td_width = round(100 / $num);
            
        } else {
            $td_width = round(100 / $this->num_td);
        }
        
        $tpl = new tplTemplatez($this->getTemplate('category_map.html'));
        
        //xajax
        $ajax = &$this->getAjax('category_filter');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('setCategoryView', $ajax, 'ajaxSetCategoryView'));
		

        foreach($rows as $k => $v) {
            $i = 0;

            foreach($v as $k1 => $top_id) {
                
                $v1 = $manager->categories[$top_id];

                $v1['td_width'] = $td_width;
                $v1['link'] = $this->getLink('index', $top_id);
                $v1['top_category_padding'] = $this->top_category_padding[$view];
                
                $private = $this->isPrivateEntry(false, $v1['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);                
                //$v1['description'] = nl2br($v1['description']);
                
                if (!empty($top_children[$top_id])) {
                    $j = 1;
                    
                    foreach ($top_children[$top_id] as $child_id) {
                        $v2 = $manager->categories[$child_id];
                        $v2['link'] = $this->getLink('index', $child_id);
                        
                        $level = $tree[$child_id];
                        $v2['padding'] = ($level - 1) * $this->padding;
                        
                        if ($j <= $this->children_shown) {
                            $v2['display'] = 'block';
                            $v2['class'] = 'visible';
                            $show_all_padding = $v2['padding'];
                                 
                        } else {
                            $v2['display'] = 'none';
                            $v2['class'] = 'unvisible';
                        }
                        
                        $tpl->tplParse($v2, 'row_tr/row_td/child_category');
                        
                        $j ++;
                    }
                    
                    $children_num = count($top_children[$top_id]);
                    if ($children_num > $this->children_shown) {
                        $tpl->tplSetNeeded('row_td/show_all_children');
                        $tpl->tplAssign('show_all_padding', $show_all_padding);
                    }
                }
                
                $tpl->tplSetNested('row_tr/row_td/child_category');
                
                $tpl->tplParse($v1, 'row_tr/row_td'); // parse nested

                $i ++;
            }

            $empty_cells_needed = ($this->num_td - $i) * 2;
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
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
}
?>