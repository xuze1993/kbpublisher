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

// file and afile actions differ
// file goes after download action
// afile for files inserted as attachments or for inline files
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryDownload_dir.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class KBClientAction_file extends KBClientAction_common
{

    function execute($controller, $manager) {
        
        // if not allowed
        if(!$manager->getSetting('module_file')) {
            $controller->go();
        }
        
        $data = $manager->getEntryById($this->entry_id, $this->category_id);
        
        // does not matter why no file, deleted, or inactive or private
        if(!$data) {
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $controller->goAccessDenied('files');
                }
            }
            
            $controller->goStatusHeader('404');
        }
        
        
        $file_dir = $manager->getSetting('file_dir');
        
        if(!FileEntryDownload_dir::getFileDir($data, $file_dir)) {
            $controller->go('files', false, false, 'file_notfound');
        }
        
        $attachment = true; // download
        // if($data['filetype'] == 'application/pdf') { // open
		if(!empty($_GET['f'])) { // open
            $attachment = false;
        }
        
        //unset($data['filetext']);
        FileEntryDownload_dir::sendFileDownload($data, $file_dir, $attachment);
        $manager->addDownload($this->entry_id);
        UserActivityLog::add('file', 'view', $this->entry_id);
        
        // if enabled output_compression then sometimes it does not sent download
        // try to comment it
        //@$controller->go('files', $this->category_id);
        exit();
    }
}
?>