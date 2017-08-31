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


class ReportStatView_forum extends ReportStatView
{
    
    var $template = 'forum_topic.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('entry_all', $this->getAll($manager));                         
        
        // entry by status 
        $tpl->tplAssign('entry_by_status', $this->getEntryByStatus($manager));    
        
        // category by status
        $tpl->tplAssign('category_by_status', $this->getCategoryByStatus($manager));
        
        // most viewed
        $tpl->tplAssign('most_viewed', $this->getMostViewed($manager));
        
        // private category
        $tpl->tplAssign('category_by_private', $this->getCategoryByPrivate($manager));
        
        // most commented
        $tpl->tplAssign('most_commented', $this->getMostCommented($manager));  

        
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getCategoryByPrivate($manager, $type = 'html', $send = false) {
        
        $data = $manager->getCategoryPrivate();
        $title = $this->msg['forum_by_private_msg'];
        $status = $this->getPrivateStatusData();
        $data = $this->getStatusBlock($data, $title, 'category_priv', $type, $send, $status);

        return $data;
    }

    
    function getEntryByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryStatus();
        $title = $this->msg['topic_by_status_msg'];
        $status = $this->getEntryStatusData('forum_status');
        $data = $this->getStatusBlock($data, $title, 'entry_status', $type, $send, $status);

        return $data;
    }
    
    
    function getCategoryByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getCategoryStatus();
        $title = $this->msg['forum_by_status_msg'];
        $status = $this->getCommonStatusData();
        $data = $this->getStatusBlock($data, $title, 'category_status', $type, $send, $status);

        return $data;
    }


    function getMostViewed($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostViewed($limit);
        $title = $this->msg['most_viewed_topic_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_viewed_topic', $type, $send);

        return $data;
    }
    
    
    function getMostCommented($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostCommented($limit);
        $title = $this->msg['most_commented_topic_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_commented_topic', $type, $send);

        return $data;
    }
    
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_topic_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $data[2]['title'] = $this->msg['all_forum_msg'];
        $data[2]['num'] = $manager->getCountAllCategory();

        //$data[3]['title'] = $this->msg['all_author_msg'];
        //$data[3]['num'] = $manager->getCountAllAuthor();
                

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
           $this->getEntryByStatus($manager, $type, true);
           break;

        case 'category_status':
            $this->getCategoryByStatus($manager, $type, true);
            break;

        case 'category_priv':
            $this->getCategoryByPrivate($manager, $type, true);
            break;

        case 'most_viewed_topic':
            $this->getMostViewed($manager, $type, true, $limit);
            break;
            
        case 'most_commented_topic': 
            $this->getMostCommented($manager, $type, true, $limit);
            break;
            
        default:
        
        }
        

    }

}
?>