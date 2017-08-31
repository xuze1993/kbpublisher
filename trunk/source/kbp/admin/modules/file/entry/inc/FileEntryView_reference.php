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


class FileEntryView_reference extends AppView
{
    
    var $template_notice = 'form_ref_notice.html';
    var $template_remove = 'form_ref_remove.html';
    
    
    function execute(&$obj, &$manager, $rtype) {
        
        if($rtype == 'ref_notice') {
            return $this->getReferencesNotice($obj, $manager);
        } else {
            return $this->getReferencesRemove($obj, $manager, $rtype);
        }
    }
    

    function getReferencesNotice(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template_notice);
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('note_references_file', 'error', false, $this->msg));    
        
        $file_id = $obj->get('id');
        
        $more = array('filter[q]'=>'attachment-attached:' . $file_id);        
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('review_link', $link);
        
        $more = array('ignore_reference' => 1);
        $link = $this->getActionLink('delete', $file_id, $more);
        $tpl->tplAssign('delete_link', $link);
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    } 
    
    
    function getReferencesRemove(&$obj, &$manager, $rtype) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template_remove);
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('note_remove_reference_file', 'error', false, $this->msg));    
        
        $file_id = $obj->get('id');
        
        $more = array('filter[q]'=>'attachment-inline:' . $file_id);
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('review_link', $link);
        
        $more = array();
        $action = ($rtype == 'remove') ? 'delete' : 'move_to_draft';
        $link = $this->getActionLink($action, $file_id, $more);
        $tpl->tplAssign('delete_link', $link);
        
        //$tpl->tplAssign('filename', $manager->getFileDir($obj->get()));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }    
    
    
    
    function getShowMsg2($manager) {
        @$key = $_GET['show_msg2'];
        if($key) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);            
        }
        
        // inline
        $file_id = $_GET['id'];
        
        $articles = $manager->getEntryToAttachment($file_id, '2,3');
        $filter = array('filter[q]'=>implode(',', $articles));
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $filter);
        $str = "javascript:OpenPopup('%s','r','r',2,'popup_review')";
        $vars['filter_link'] = sprintf($str, $link);
        
        $more = array();
        $link = $this->getActionLink('delete', $file_id, $more);
        $str = '%s'; //"location.href='%s'";
        $vars['delete_link'] = sprintf($str, $link);
        
        $vars['file_id'] = $file_id;
        
        $msg['title'] = $msgs['title_remove_references_file'];
        $msg['body'] = $msgs['note_remove_reference_file'];
        return BoxMsg::factory('error', $msg, $vars);
    }    
}
?>