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


class SubscriptionView_list_comments extends SubscriptionView_list
{

    var $tmpl = 'articles_list.html';
    var $table_name = 'articles';
    

  
    function execute(&$obj, &$manager) {      

        $this->addMsg('user_msg.ini');
        
        $bp = $this->getPageByPage($manager);
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        // header generate
        $title = $this->getTitle($manager);
        $tpl->tplAssign($this->getHeader($manager, $title, false, $bp));

        // note message
        $tpl->tplAssign('user_note_msg', AppMsg::hintBoxCommon('note_comment_subscribe'));

        // rows
        $table_name = $this->table_name;
        $manager->tbl->table = $manager->tbl->$table_name;

        $ids = $manager->getValuesString($rows, 'entry_id');
        $manager->setSqlParamsOrder('ORDER BY date_updated DESC');
        $entries = ($ids) ? $manager->getRowsByIds($ids) : array();
        $entries = $this->stripVars($entries);

        $this->msg['delete_msg'] = $this->msg['unsubscribe_msg'];
        $cc = &$this->getClientController();

        foreach($entries as $row) {            
            //$obj->set($row);
  
            $row['date_posted'] = $this->getFormatedDate($row['date_posted']);
            $row['date_updated'] = $this->getFormatedDate($row['date_updated']);
            $row['entry_link'] = $cc->getLink('entry', false, $row['id']);    
            
            $tpl->tplAssign($this->getViewListVars($row['id'], true));

            $tpl->tplParse(array_merge($obj->get(), $row), 'row');
        }


        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>