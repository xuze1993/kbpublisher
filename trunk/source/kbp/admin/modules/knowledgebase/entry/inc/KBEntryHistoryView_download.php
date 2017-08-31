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


class KBEntryHistoryView_download extends AppView
{
    
    var $template = 'form_download.html';
    
    function execute(&$obj, &$manager, $data) {

        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // form vars
        $vars = $this->setCommonFormVars($obj);
        $more = array('action' => 'history', 'id' => $obj->get('id'));
        $vars['cancel_link'] = $vars['cancel_link'] . '&' . http_build_query($more);    
        
        // bredcrumb
        $top_menu_msg = AppMsg::getMenuMsgs('top');
        $link = $this->controller->getCommonLink();
        
        $nav = array();
        $nav[1] = array('link' => $link, 'item'=>$top_menu_msg['knowledgebase']);
        $nav[2]['item'] = sprintf('%s [%s]', $this->msg['history_msg'], $obj->get('id'));
        $nav[2]['link'] = $vars['cancel_link'];
        $nav[3]['item'] = $this->msg['download_msg'];
        
        $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));

        // dates
        // live version
        $formatted_date = $this->getFormatedDate($obj->get('date_updated'), 'datetime');
        $tpl->tplAssign('formatted_date_posted', $formatted_date);
            
        // selected version
        $formatted_date = $this->getFormatedDate($data['date_posted'], 'datetime');
        $tpl->tplAssign('formatted_date_revision', $formatted_date);
        
        
        $tpl->tplAssign('revision_num', $data['revision_num']); 
        
        
        // links
        $more = array('id' => $obj->get('id'), 'html' => 1);
        $link = $this->getLink('knowledgebase', 'kb_entry', false, 'file', $more);
        $tpl->tplAssign('live_download_link', $link);
        
        
        $more = array('id' => $obj->get('id'), 'vnum' => $data['revision_num'], 'html' => 1);
        $link = $this->getLink('knowledgebase', 'kb_entry', false, 'file', $more);
        $tpl->tplAssign('revision_download_link', $link);
        
        $tpl->tplAssign('cancel_link', $vars['cancel_link']);
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>