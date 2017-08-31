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


class ReportStatView_feedback extends ReportStatView
{
    
    var $template = 'feedback.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('feedback_all', $this->getAll($manager));
        
        // feedback by status (answered - not answered)
        $tpl->tplAssign('feedback_by_status', $this->getFeedbackByStatus($manager));
        
        // feedback by status (placed - not placed)
        $tpl->tplAssign('feedback_by_status2', $this->getFeedbackByStatus2($manager));

        // article comment by status
        $tpl->tplAssign('article_comment_by_status', $this->getArticleCommentByStatus($manager));
        
        // rating comment by status
        $tpl->tplAssign('rating_comment_by_status', $this->getRatingCommentByStatus($manager));            

        
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFeedbackByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getFeedbackStatus();
        $title = $this->msg['feedback_by_answered_status'];
        $status[1]['title'] = $this->msg['yes_msg'];
        $status[0]['title'] = $this->msg['no_msg'];

        $data = $this->getStatusBlock($data, $title, 'feedback_status', $type, $send, $status);

        return $data;
    }


    function getFeedbackByStatus2($manager, $type = 'html', $send = false) {
        
        $data = $manager->getFeedbackStatus2();
        $title = $this->msg['feedback_by_placed_status'];        
        $status[1]['title'] = $this->msg['yes_msg']; 
        $status[0]['title'] = $this->msg['no_msg'];

        $data = $this->getStatusBlock($data, $title, 'feedback_status2', $type, $send, $status);

        return $data;
    }
    
    
    function getArticleCommentByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getArticleCommentStatus();
        $title = $this->msg['article_comment_by_status_msg'];
        $status = $this->getCommonStatusData();
        $data = $this->getStatusBlock($data, $title, 'article_comments', $type, $send, $status);

        return $data;
    }

    
    function getRatingCommentByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getRatingCommentStatus();
        $title = $this->msg['article_feedback_by_status_msg'];
        $status = $this->getEntryStatusData('rate_status'); 
        $data = $this->getStatusBlock($data, $title, 'rating_comments', $type, $send, $status);

        return $data;
    }
    
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_feedback_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $data[2]['title'] = $this->msg['all_article_comment_msg'];
        $data[2]['num'] = $manager->getCountAllArticleComment();

        $data[3]['title'] = $this->msg['all_article_feedback_msg'];
        $data[3]['num'] = $manager->getCountAllRatingComment();
                

        $title = $this->msg['all_msg'];  
        $data = $this->getCommonBlock($data, $title, 'all', $type, $send);

        return $data;
    }
    
    
    function executeExport($manager, $type, $mode) {

        switch($mode) {
        case 'all':
            $this->getAll($manager, $type, true);
             break;
    
        case 'feedback_status':                
            $this->getFeedbackByStatus($manager, $type, true);
            break;
       
        case 'feedback_status2':
            $this->getFeedbackByStatus2($manager, $type, true);
            break;
 
        case 'article_comments':
            $this->getArticleCommentByStatus($manager, $type, true);
            break;
        
        case 'rating_comments':
            $this->getRatingCommentByStatus($manager, $type, true);
            break;

        default:
        
        }
    }

}
?>