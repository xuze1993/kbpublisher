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

class SubscriptionView_list_news extends SubscriptionView_list
{
    var $tmpl = 'news_list.html';
    var $table_name = 'news';
    var $entry_type = 3;
    var $admin_view = true;
    
    
    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');

        $bp = $this->getPageByPage($manager);
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $is_subsc = (empty($rows)) ? false : true;

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // header generate
        $title = $this->getTitle($manager);
        $tpl->tplAssign($this->getHeader($manager, $title, false));

        $status_msg = ($is_subsc) ? $this->msg['unsubscribe_msg'] : $this->msg['subscribe_msg'];
        $tpl->tplAssign('subsc_status_msg', $status_msg);
        
        $status = (int) $is_subsc;
        $tpl->tplAssign('status', $status); 

        
        if($this->admin_view) {
            $link = $this->getActionLink('insert', null);                
        } else {   
            $link = $_SERVER['PHP_SELF'] . '?View=member_subsc&action=insert&type=3';                
        }
        
        $tpl->tplAssign('action_link', $link);
        $tpl->tplAssign($this->msg);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>