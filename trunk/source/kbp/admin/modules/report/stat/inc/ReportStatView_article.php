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


class ReportStatView_article extends ReportStatView
{
    
    var $template = 'entry.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('entry_all', $this->getAll($manager));                         
        
        // entry by status 
        $tpl->tplAssign('entry_by_status', $this->getEntryByStatus($manager));    
        
        // category by status
        $tpl->tplAssign('category_by_status', $this->getCategoryByStatus($manager));
            
        // comment by status
        //$tpl->tplAssign('comment_by_status', $this->getCommentByStatus($manager));        
        
        //most viewed
        $tpl->tplAssign('most_viewed', $this->getMostViewed($manager));        
        
        //most commented        
        $tpl->tplAssign('most_commented', $this->getMostCommented($manager));        
    
        //most useful
        $tpl->tplAssign('most_useful', $this->getMostUseful($manager));        
                
        //most useless
        $tpl->tplAssign('most_useless', $this->getMostUseless($manager));

        // private entry
        $tpl->tplAssign('entry_by_private', $this->getEntryByPrivate($manager));
        
        // private entry
        $tpl->tplAssign('category_by_private', $this->getCategoryByPrivate($manager));
        
        
        $tpl->tplAssign($this->msg);
            
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryByPrivate($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryPrivate();
        $title = $this->msg['article_by_private_msg'];
        $status = $this->getPrivateStatusData();
        $data = $this->getStatusBlock($data, $title, 'entry_priv', $type, $send, $status);

        return $data;
    }
    
    
    function getCategoryByPrivate($manager, $type = 'html', $send = false) {
        
        $data = $manager->getCategoryPrivate();
        $title = $this->msg['category_by_private_msg'];
        $status = $this->getPrivateStatusData();
        $data = $this->getStatusBlock($data, $title, 'category_priv', $type, $send, $status);

        return $data;
    }
    
    
    function getEntryByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryStatus();
        $title = $this->msg['article_by_status_msg'];
        $status = $this->getEntryStatusData('article_status');
        $data = $this->getStatusBlock($data, $title, 'entry_status', $type, $send, $status);

        return $data;
    }
    
    
    function getCategoryByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getCategoryStatus();
        $title = $this->msg['category_by_status_msg'];
        $status = $this->getCommonStatusData();
        $data = $this->getStatusBlock($data, $title, 'category_status', $type, $send, $status);

        return $data;
    }
    
    
    function getCommentByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getCommentStatus();
        $title = $this->msg['comment_by_status_msg'];
        $status = $this->getCommonStatusData();
        $data = $this->getStatusBlock($data, $title, 'comments', $type, $send, $status);

        return $data;
    }


    function getMostViewed($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostViewed($limit);
        $title = $this->msg['most_viewed_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_viewed', $type, $send);

        return $data;
    }
    
    
    function getMostCommented($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostCommented($limit);
        $title = $this->msg['most_commented_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_commented', $type, $send);

        return $data;
    }    
    
    
    function getMostUseful($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostUseful($limit);
        $title = $this->msg['most_useful_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_useful', $type, $send);

        return $data;
    }    
    

    function getMostUseless($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostUseless($limit);
        $title = $this->msg['most_useless_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_useless', $type, $send);

        return $data;
    }
    
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_article_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $data[2]['title'] = $this->msg['all_category_msg'];
        $data[2]['num'] = $manager->getCountAllCategory();

        //$data[3]['title'] = $this->msg['all_comment_msg'];
        //$data[3]['num'] = $manager->getCountAllComment();
        
        $data[4]['title'] = $this->msg['all_glossary_msg'];    
        $data[4]['num'] = $manager->getCountAllGlossary();

        //$data[5]['title'] = $this->msg['all_author_msg'];
        //$data[5]['num'] = $manager->getCountAllAuthor();
                

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

        case 'entry_priv':
            $this->getEntryByPrivate($manager, $type, true);
            break;

        case 'category_priv':
            $this->getCategoryByPrivate($manager, $type, true);
            break;    
 
        case 'comments':
            $this->getCommentByStatus($manager, $type, true);
            break;
 
        case 'most_viewed':                      
            $this->getMostViewed($manager, $type, true, $limit);
            break;
 
        case 'most_commented': 
            $this->getMostCommented($manager, $type, true, $limit);
            break;
 
        case 'most_useful':
            $this->getMostUseful($manager, $type, true, $limit);
            break;
            
        case 'most_useless':
            $this->getMostUseless($manager, $type, true, $limit);
            break;

        default:

        }
    }

}
?>