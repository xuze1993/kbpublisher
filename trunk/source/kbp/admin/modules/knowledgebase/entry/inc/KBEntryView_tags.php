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


class KBEntryView_tags extends AppView 
{
    
    var $template = 'tags_list.html';
 

    function execute(&$obj, &$manager, $form_action = false, $form_params = false) {
        $num_td = 4;
        $limit = 50;
        
        $bp_hidden = false;
        if(isset($_GET['qf'])) {
            $str = addslashes(stripslashes($_GET['qf']));
            $bp_hidden = array('qf' => $_GET['qf']);
            $manager->tag_manager->setSqlParams("AND title LIKE '%{$str}%' OR description LIKE '%{$str}%'");
        }
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $manager->tag_manager->setSqlParamsOrder('ORDER BY title');

        // header generate
        $bp =& $this->pageByPage($limit, $manager->tag_manager->getRecordsSql());
        
        // get records
        $rows = $this->stripVars($manager->tag_manager->getRecords($bp->limit, $bp->offset));

        
        $num = ($n = count($rows)) ? $n : 1;
        $rows = array_chunk($rows, $num_td);
        $ids = array();

        // td width
        if($num < $num_td) { $td_width = round(100/$num); } 
        else               { $td_width = round(100/$num_td); }
        

        foreach($rows as $k => $v) {
            $i = 0;

            foreach($v as $k1 => $v1) {

                $v1['td_width'] = $td_width;
                $more = array('s' => 1, 'q' => $v1['title'], 'in' => 'article_keyword');
                $v1['tag_link'] = $this->getLink('search', false, false, false, $more);
                $v1['description'] = nl2br($v1['description']);
                
                $ids[] = $v1['id'];

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
        
        $tpl->tplAssign('tag_ids', implode(',', $ids));
        
        
        // search
        if (!$form_action) {
            $form_action = $this->getActionLink('tags');
            
            $form_params = array(
                'module' => $this->controller->module,
                'page' => $this->controller->page,
                'action' => 'tags',
                'popup' => 1);
        }
        
        $tpl->tplAssign('form_search_action', $form_action);
        $tpl->tplAssign('hidden_search', http_build_hidden($form_params, true));

        $tpl->tplAssign('qf', $this->stripVars(trim(@$_GET['qf']), array(), 'asdasdasda'));
        
        // by page
        if($bp->num_pages > 1) {
            $tpl->tplAssign('page_by_page', $bp->navigate());
            $tpl->tplSetNeeded('/by_page');
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
}
?>