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

require_once 'core/common/CommonEntryModel.php';
require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraftModel.php';
require_once APP_MODULE_DIR . 'tool/workflow/inc/WorkflowEntryModel.php';


class FileDraftModel extends KBDraftModel
{

    var $tbl_pref_custom = '';

    var $role_write_rule = 'file_draft_to_role_write';
    var $role_write_id = 104;

    var $entry_type = 8;
    var $from_entry_type = 2; // file


    function moveDraftFile($filename, $old_dir, $new_dir) {

        require_once 'eleontev/Dir/Uploader.php';

        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();

        $upload->setUploadedDir($new_dir);
        $upload->setRenameValues('date');

        $f = $upload->move($filename, $old_dir);

        return $f;
    }


    function copyFileToDrafts($filename, $file_dir, $draft_dir) {

        require_once 'eleontev/Dir/Uploader.php';

        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();

        $upload->setUploadedDir($draft_dir);
        $upload->setRenameValues('date');

        $f = $upload->copy($filename, $file_dir);

        return $f;
    }


    // DELETE // -----------------------

    function deleteFileData($record_id) {
        
        $file_dir = SettingModel::getQuick(1, 'file_dir');
        
        $this->setSqlParams(sprintf('AND d.id IN (%s)', $record_id));
        $rows = $this->getRecords();
        
        foreach($rows as $row) {
            $eobj = unserialize($row['entry_obj']);
            if (strpos($eobj->get('directory'), $file_dir) !== false) { // not remote
                // $file = $eobj->get('directory') . $eobj->get('filename');
                $file = FileEntryDownload_dir::getFileDir($eobj->get(), $file_dir);
                
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

}
?>