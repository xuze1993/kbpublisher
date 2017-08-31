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


class SubscriptionView_list_articles_cat extends SubscriptionView_list
{
    
    var $tmpl = 'articles_list_cat.html';
    var $table_name = 'category_articles';
    var $entry_type = 11;
    var $client_view_str = 'index';

    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        
        $bp = $this->getPageByPage($manager);
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $is_all = (isset($rows[0]['entry_id']) && $rows[0]['entry_id'] == 0);
        $add_button = ($is_all) ? false : true;


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        // header generate
        $title = $this->getTitle($manager);
        $tpl->tplAssign($this->getHeader($manager, $title, $add_button, $bp));

        if($is_all) {
            $entries[0]['id'] = 'all';
            $entries[0]['cat_path'] = $this->msg['all_categories_msg'];
            
        } else {
            
            $ids = $manager->getValuesString($rows, 'entry_id');

            // rows
            $table_name = $this->table_name;
            $manager->tbl->table = $manager->tbl->$table_name;

            $entries = ($ids) ? $manager->getRowsByIds($ids) : array();
            $entries = $this->stripVars($entries);
    
            $am = $this->getCategoryManager($manager);
            $full_categories = &$am->cat_manager->getSelectRangeFolow();
            $full_categories = $this->stripVars($full_categories);

            foreach($entries as $id => $row) {
                $entries[$id]['cat_path'] = $full_categories[$id];
            }            
        }
        
        $this->msg['delete_msg'] = $this->msg['unsubscribe_msg'];
        $cc = &$this->getClientController();
        foreach($entries as $row) {       
            //$obj->set($row);

            $row['entry_link'] = $cc->getLink($this->client_view_str, $row['id']);
            if($row['entry_link'] == 'all') {
                $row['entry_link'] = $cc->getLink($this->client_view_str);
            }    

            $tpl->tplAssign($this->getViewListVars($row['id'], true));     
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($this->msg);

        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getCategoryManager($manager) {
        return $manager->getArticleManager();
    }
}
?>