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


class ReportStatView_file extends ReportStatView
{
    
    var $template = 'file_entry.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('entry_all', $this->getAll($manager));                         
        
        // entry by status 
        $tpl->tplAssign('entry_by_status', $this->getEntryByStatus($manager));    
        
        // category by status
        $tpl->tplAssign('category_by_status', $this->getCategoryByStatus($manager));
        
        //most downloaded
        $tpl->tplAssign('most_downloaded', $this->getMostDownloaded($manager));
        
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
        $title = $this->msg['file_by_private_msg'];
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
        $title = $this->msg['file_by_status_msg'];
        $status = $this->getEntryStatusData('file_status');
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


    function getMostDownloaded($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostViewed($limit);
        $title = $this->msg['most_downloaded_msg'];    
        $data = $this->getEntryBlock($data, $title, 'most_downloaded', $type, $send);

        return $data;
    }
    
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_file_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $data[2]['title'] = $this->msg['all_category_msg'];
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

        case 'entry_priv':
            $this->getEntryByPrivate($manager, $type, true);
            break;

        case 'category_priv':
            $this->getCategoryByPrivate($manager, $type, true);
            break;

        case 'most_downloaded':
            $this->getMostDownloaded($manager, $type, true, $limit);
            break;

        default:
        
        }
        

    }

}
?>