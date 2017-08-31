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


class KBClientView_trouble extends KBClientView_common
{
    
    var $step_id = 0;
    
    
    function &execute(&$manager) {

        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);
        
        // does not matter why no article, deleted, or inactive or private
        if(!$row) { 
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $this->controller->goAccessDenied('trouble');
                }
            }
            
            $this->controller->goStatusHeader('404');
        }
        
        $steps_num = $manager->getStepsNum($this->entry_id);
        if (!$steps_num) {
            $msg = $this->getActionMsg('success', 'no_trouble_steps');
            return $msg;
        }

        $title = $row['title'];
        $this->home_link = true;
        $this->meta_title = $this->getSubstring($title, 100);
        
        
        $this->nav_title = false;
        if($manager->getSetting('show_title_nav')) {
            $this->nav_title = $this->getSubstring($title, 70, '...');
        }
        
        $data = &$this->getEntry($row, $manager);

        return $data;        
    }
    
    
    function &getEntry($row, $manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('trouble_entry.html'));
        
        $row['print_link'] = $this->getLink('print-trouble', false, $this->entry_id);
        $row['step_block'] = $this->getStepBlock($manager);

        DocumentParser::parseCurlyBraces($row['body']);
        
        if (!empty($row['body'])) {
            $tpl->tplSetNeeded('/description');
        }
        
        //xajax
        $ajax = &$this->getAjax('trouble');
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('getNextStep', $ajax, 'ajaxGetNextStep'));
        $xajax->registerFunction(array('generatePrevSteps', $ajax, 'ajaxGeneratePrevSteps'));  
        $xajax->registerFunction(array('getNextComments', $ajax, 'ajaxGetNextComments'));  
        $xajax->registerFunction(array('getRawMessage', $ajax, 'ajaxGetRawMessage')); 
        $xajax->registerFunction(array('postComment', $ajax, 'ajaxPostComment'));  
        $xajax->registerFunction(array('updateComment', $ajax, 'ajaxUpdateComment')); 
        $xajax->registerFunction(array('deleteComment', $ajax, 'ajaxDeleteComment')); 
        
        $tpl->tplAssign('rating_block', $this->getRatingBlock($manager, $row));
                
        $tpl->tplAssign('entry_id', $this->entry_id);

        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    
    
    function getStepBlock($manager, $entry_id = false, $parent_id = 0, $prev_button = false) {
    
        $tpl = new tplTemplatez($this->getTemplate('trouble_step.html'));

        $entry_id = ($entry_id) ? $entry_id : $this->entry_id;
        $rows = $manager->getSteps($entry_id, $parent_id);

        // buttons
        if ($parent_id != 0 || $prev_button) {
            $tpl->tplSetNeeded('/prev_button'); 
        }
        
        foreach($rows as $id => $row) {
            $row['id'] = $id;
            $tpl->tplParse($row, 'row');
        }
                
        if (!empty($rows) || !empty($related)) {
            $tpl->tplSetNeeded('/next_button');
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);    
    }
        
    
    function getStepCommentBlock($manager, $entry_id = false, $parent_id = 0) {
    
        $this->controller->loadClass('comment'); 
        $this->controller->loadClass('trouble_comment');

            
        $tpl = new tplTemplatez($this->getTemplate('trouble_step_comment.html'));
            
        $limit = $manager->getSetting('num_comments_per_page');
        $num_comment = $manager->getCommentListCount($parent_id);
        $bp = $this->pageByPage($limit, $num_comment);
                
        $by_page = $bp->_getBpValuesNext('standart');
        $by_page = implode($bp->get_delim, $by_page);

        $tpl->tplAssign('bp', $by_page);
        
        if($bp->num_pages > 1) {
            $tpl->tplSetNeeded('/by_page_bottom');
        }


        $comment = new KBClientView_trouble_comment();
        $comment_list = $comment->getList($manager, $parent_id, $bp->limit, $bp->offset);
        $tpl->tplAssign('comment_list', $comment_list);
        
        if ($comment_list) {
            $tpl->tplSetNeeded('/comment_title');
        }
            
        $tpl->tplAssign('step_id', $parent_id); 
            
        $tpl->tplParse($this->msg);
        $data = $tpl->tplPrint(1); 
            
            
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
            
        if($this->isCommentable($manager)) {
            if($data) {
                $data .= $comment->getForm($manager, $row, $parent_id, 'entry');
            }
        }
        
        return $data;
    }
    
}
?>