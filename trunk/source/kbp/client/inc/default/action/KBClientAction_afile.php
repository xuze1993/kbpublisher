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

// afile - for attached files
// file - for files in files area

require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryDownload_dir.php';


class KBClientAction_afile extends KBClientAction_common
{

    function execute($controller, $manager) {
        
        // before 5.0, to make it compattible with old versions
        // index.php?View=afile&CategoryID=2&EntryID=1 
        if(empty($_GET['AttachID'])) {
            $entry_id = (int) $controller->getRequestVar('category_id'); // here it is entry to what file attached
            $file_id = (int) $controller->getRequestVar('entry_id');
            $category_ids = $manager->getCategoryIdsByEntryId($entry_id);
            $category_ids = array_intersect($category_ids, array_keys($manager->categories)); // remove not allowed
            $category_id = current($category_ids);
        
        // after 5.0
        // index.php?View=afile&EntryID=10494&AttachID=83
        } else {
            $entry_id = (int) $this->entry_id; // entry to what file attached
            $file_id = (int) $_GET['AttachID'];
            $category_id =  $this->category_id;
        }
        
        $msg_key = $controller->getRequestVar('msg');
        
        // get article to check if we have access to it
        $row = $manager->getEntryById($entry_id, $category_id);

        // does not matter why no article, deleted, or inactive or private
        if(!$row) { 
            
            // new private policy, check if entry exists 
            // in attached files we do not have private files 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($entry_id, $category_id)) {
                    $controller->goAccessDenied('index');
                }
            }
            
            $controller->goStatusHeader('404');
        }
        
        
        $data = $manager->getAttachment($entry_id, $file_id);
        $file_dir = $manager->getSettings(1, 'file_dir');
                
        // not attached or not active
        if(!$data) {
            $controller->goStatusHeader('404');
        }
        
        if(!FileEntryDownload_dir::getFileDir($data, $file_dir)) {
            $link = $controller->getLink('entry', $this->category_id, $entry_id, 'file_notfound');
            $controller->goUrl($link);
        }
        
        $attachment = true; // download
        // if($data['filetype'] == 'application/pdf') { // open
		if(!empty($_GET['f'])) { // open
            $attachment = false;
        }
        
        FileEntryDownload_dir::sendFileDownload($data, $file_dir, $attachment);
        $manager->addDownload($file_id);
        UserActivityLog::add('file', 'view', $file_id);
        
        
        // if enabled output_compression then sometimes it does not sent download
        // try to comment it
        //@$controller->go('files', $this->category_id);
        exit();
    }
}
?>