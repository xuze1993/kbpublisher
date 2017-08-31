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

require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryView_preview.php';


class KBEntryHistoryView_preview extends KBEntryView_preview
{
    
    var $template = 'preview_history.html';
    
    
    function execute(&$obj, &$manager, $data = array()) {
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // dates
        $date = $this->getFormatedDate($obj->get('date_posted'), 'datetime');
        $tpl->tplAssign('formatted_date_posted', $date);
           
        $date = $this->getFormatedDate($obj->get('entry_date_updated'), 'datetime');
        $tpl->tplAssign('formatted_entry_date_updated', $date);

        // user
        $user = $manager->getUserById($obj->get('entry_updater_id'));
        if($user) {
            $str = '%s (%s)';
            $str = sprintf($str, PersonHelper::getShortName($user), $user['email']);
            $tpl->tplAssign('updated_by', $str);
        }
        
        $tpl->tplAssign('title', $data['title']);
        
        list($manager, $controller) = $this->getClientComponents();
        $tpl->tplAssign('body', $this->parseBody($manager, $controller, $data['body'], false));
        
        $client_path = $this->conf['client_path'];
        if($this->conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', $client_path);
        }
        $tpl->tplAssign('kb_path', $client_path);
        
        $popup_title = sprintf('%s[%d] %s #%d', $this->msg['history_msg'], $obj->get('entry_id'), 
                                                $this->msg['revision_msg'], $obj->get('revision_num'));
        $tpl->tplAssign('popup_title', $popup_title);

        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>