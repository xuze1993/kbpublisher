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

require_once 'KBCommentView_helper.php';
require_once 'KBCommentView_list.php';


class KBCommentView_list_entry extends KBCommentView_list
{
    
    var $tmpl = 'list_entry.html';

    
    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $entry_id = $obj->get('entry_id');    
        $client_controller = &$this->controller->getClientController();
        
        // update article link
        $more = array('id'=>$obj->get('entry_id'), 'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
        $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
        $tpl->tplAssign('entry_link_update', $link);
        
        // public article link
        $link = $client_controller->getLink('entry', false, $entry_id);
        $tpl->tplAssign('entry_link', $link);
        $tpl->tplAssign('entry_id', $entry_id);
    
        // delete all link
        $more = array('entry_id' => $entry_id);
        $tpl->tplAssign('delete_entry_link', $this->getActionLink('delete_entry', false, $more));        
        
        // back link
        $link = $this->controller->getLink('knowledgebase', 'kb_comment');
        $tpl->tplAssign('back_link', $link);        
    
    
        // BB CODE
        $parser = KBCommentView_helper::getBBCodeObj();
    
        // filter sql    
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);        
        $manager->setSqlParams(sprintf("AND e.id = %d", $entry_id));

        // header generate
        $button[] = 'insert';
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), $button));
        
        // sort generate
        $sort = &$this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records        
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        if($rows) {
            $tpl->tplAssign('title', $rows[0]['title']);
            $tpl->tplAssign('short_title', $this->getSubstringStrip($rows[0]['title'], 100));
        }
        
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('comment', nl2br($parser->qparse($obj->get('comment'))));                
            $tpl->tplAssign('username', $row['username']);        
            $tpl->tplAssign('r_email', $row['r_email']);            
            
            $formatted_date = $this->getFormatedDate($row['date_posted']);
            $tpl->tplAssign('formatted_date', $formatted_date);

            $interval_date = $this->getTimeInterval($row['date_posted'], true);
            $tpl->tplAssign('interval_date', $interval_date);            
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active')));
            
            $tpl->tplParse($obj->get(), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>