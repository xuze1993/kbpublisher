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

require_once 'core/common/CommonEntryView.php';


class TrashEntryView_incomplete extends AppView 
{
    
    var $template = 'form_incomplete.html';
 

    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->template);
          
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg['title'] = $msgs['title_entry_incomplete'];
        $msg['body'] = $msgs['note_entry_incomplete'];
        $tpl->tplAssign('msg', BoxMsg::factory('error', $msg));
        
        $entry_type = $manager->getEntryTypeSelectRange();
        $tpl->tplAssign('type', $entry_type[$obj->get('entry_type')]);
        
        $tpl->tplAssign('formatted_date', $this->getFormatedDate($obj->get('date_deleted'), 'datetime'));
        
        $b_options = array(
            'no_button' => true, 
            'default_button' => false, 
            'hide_private' => true
            );
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock(false, $manager, false,'knowledgebase', 'kb_entry', $b_options));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>