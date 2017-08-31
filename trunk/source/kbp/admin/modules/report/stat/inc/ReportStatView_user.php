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


class ReportStatView_user extends ReportStatView
{
    
    var $template = 'user.html';

    
    function execute(&$obj, &$manager) {
                                                   
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('user_all', $this->getAll($manager));                         
        
        // user by status 
        $tpl->tplAssign('user_by_status', $this->getUserByStatus($manager));    
        
        // user by privilege
        $tpl->tplAssign('user_by_privilege', $this->getUserByPrivilege($manager));        

        // user by subscription
        $tpl->tplAssign('user_by_subscription', $this->getUserBySubscription($manager));    
        
        //most author
        $tpl->tplAssign('most_author', $this->getMostAuthor($manager));        
        
        //most file author        
        $tpl->tplAssign('most_file_author', $this->getMostFileAuthor($manager));        
    
        //most commenter
        $tpl->tplAssign('most_commenter', $this->getMostCommenter($manager));
        
        // feedback
        $tpl->tplAssign('most_feedback', $this->getMostFeedback($manager));
        
        // article feedback
        $tpl->tplAssign('most_article_feedback', $this->getMostArticleFeedback($manager));             
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    
    function getUserByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryStatus();
        $title = $this->msg['user_by_status_msg'];
        $status = $this->getEntryStatusData('user_status');
        $data = $this->getStatusBlock($data, $title, 'entry_status', $type, $send, $status);

        return $data;
    }
    

    function getUserByPrivilege($manager, $type = 'html', $send = false) {
        
        $data = $manager->getUserByPrivilege();
        $title = $this->msg['user_by_privilege_msg'];
        $status = $manager->getPrivileges();        
        $data = $this->getStatusBlock($data, $title, 'entry_privilege', $type, $send, $status);

        return $data;
    }


    function getUserBySubscription($manager, $type = 'html', $send = false) {

        $title = $this->msg['user_by_subscription_msg'];

        $status = array();
        $status[3]['title'] = $this->msg['sub_news_msg'];

        $status[1]['title'] = $this->msg['sub_article_msg'];
        $status[11]['title'] = $this->msg['sub_article_all_msg'];
        $status[111]['title'] = $this->msg['sub_article_cat_msg'];
        $status[31]['title'] = $this->msg['sub_comment_msg'];

        $status[2]['title'] = $this->msg['sub_file_msg'];
        $status[12]['title'] = $this->msg['sub_file_all_msg'];
        $status[112]['title'] = $this->msg['sub_file_cat_msg'];
        
        $data = $manager->getUsersBySubscribtionAll();
        $data2 = $manager->getUsersBySubscribtionConcrete();

        foreach(array_keys($data2) as $type) {
            if(isset($data2[1])) {
                $data[1] = $data2[1];
            }
            
            if(isset($data2[2])) {
                $data[2] = $data2[2];
            }
            
            if(isset($data2[11])) {
                $data[111] = $data2[11];
            }
                        
            if(isset($data2[12])) {
                $data[112] = $data2[12];
            }

            if(isset($data2[31])) {
                $data[31] = $data2[31];
            }
        }
        
        
        foreach($status as $type => $v) {
            $data_[$type]['title'] = $v['title'];
            $data_[$type]['num'] = (isset($data[$type])) ? $data[$type] : 0;
        }

        // $data = $this->getStatusBlock($data, $title, 'entry_subscription', $type, $send, $status);
        $data = $this->getCommonBlock($data_, $title, 'entry_subscription', $type, $send);

        return $data;
    }
    
    
    function getMostAuthor($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostAuthor($limit);
        foreach(array_keys($data) as $id) {
            $data[$id]['title'] = $data[$id]['first_name'] . ' ' . $data[$id]['last_name'];
        }

        $title = $this->msg['most_author_msg'];
        $data = $this->getEntryBlock($data, $title, 'most_author', $type, $send);  

        return $data;
    }


    function getMostFileAuthor($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostFileAuthor($limit);
        foreach(array_keys($data) as $id) {
            $data[$id]['title'] = $data[$id]['first_name'] . ' ' . $data[$id]['last_name'];
        }

        $title = $this->msg['most_fileauthor_msg'];
        $data = $this->getEntryBlock($data, $title, 'most_fileauthor', $type, $send);  

        return $data;
    }
    
    
    function getMostCommenter($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostCommenter($limit);
        foreach(array_keys($data) as $id) {
            $data[$id]['title'] = $data[$id]['first_name'] . ' ' . $data[$id]['last_name'];
        }

        $title = $this->msg['most_commenter_msg'];
        $data = $this->getEntryBlock($data, $title, 'most_commenter', $type, $send);  

        return $data;
    }


    function getMostFeedback($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostFeedback($limit);
        foreach(array_keys($data) as $id) {
            $data[$id]['title'] = $data[$id]['first_name'] . ' ' . $data[$id]['last_name'];
        }

        $title = $this->msg['most_feedback_msg'];
        $data = $this->getEntryBlock($data, $title, 'most_feedback', $type, $send);  

        return $data;
    }
    
    
    function getMostArticleFeedback($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostArticleFeedback($limit);
        foreach(array_keys($data) as $id) {
            $data[$id]['title'] = $data[$id]['first_name'] . ' ' . $data[$id]['last_name'];
        }

        $title = $this->msg['most_article_feedback_msg'];
        $data = $this->getEntryBlock($data, $title, 'most_article_feedback', $type, $send);  

        return $data;
    }
        
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_user_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $data[2]['title'] = $this->msg['all_privilege_msg'];
        $data[2]['num'] = $manager->getCountAllPrivilege();

        $data[3]['title'] = $this->msg['all_role_msg'];    
        $data[3]['num'] = $manager->getCountAllRole();
        
        $data[4]['title'] = $this->msg['all_company_msg'];    
        $data[4]['num'] = $manager->getCountAllCompany();     

        $title = $this->msg['all_msg'];  
        $data = $this->getCommonBlock($data, $title, 'all', $type, $send);

        return $data;
    }
    
    
    function executeExport($manager, $type, $mode, $limit = 10) {
 
        switch($mode) {
        case 'all':
            $this->getAll($manager, $type, true);
            break;
        
        case 'entry_status':
            $this->getUserByStatus($manager, $type, true);
            break;        
        
        case 'entry_privilege':                
            $this->getUserByPrivilege($manager, $type, true);
            break;

        case 'most_author':
            $this->getMostAuthor($manager, $type, true, $limit);     
            break;

        case 'most_fileauthor':
            $this->getMostFileAuthor($manager, $type, true, $limit);
            break;

        case 'most_commenter':
            $this->getMostCommenter($manager, $type, true, $limit);
            break; 

        case 'most_feedback':
            $this->getMostFeedback($manager, $type, true, $limit);
            break;
            
        case 'most_article_feedback':
            $this->getMostArticleFeedback($manager, $type, true, $limit);
            break;
            
        default:
            
        }
    }

}
?>