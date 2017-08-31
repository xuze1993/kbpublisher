<?php

require_once APP_MODULE_DIR . "knowledgebase/draft/inc/KBDraftAction.php";


class FileDraftAction extends KBDraftAction
{
    
    // $file_dir required, make like this to avoid Warning: Declaration of FileDraftAction::publish($
    function publish($eobj, $emanager, $manager, $controller, $priv, $options = array()) {
		
        // check priv
        $priv_action = ($eobj->get('id')) ? 'update' : 'insert';
        $action = $priv_action;
        if(!$priv->isPriv($priv_action, 'file_entry')) {
            echo AuthPriv::errorMsg();
            exit;
        }
        
        // if simple publish, not assignee
        if(!empty($options['check_private'])) {
            $has_private = $manager->isCategoryNotInUserRole($eobj->getCategory());
            if($has_private) {
                echo AuthPriv::errorMsg();
                exit;
            }
        }

        $file_dir = (!empty($options['file_dir'])) ? $options['file_dir'] : false;

        // setting default status
        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $eobj->get('active');
        $eobj->set('active', $status);

        // updating a saved draft having an entry_id, deleting the old file
        if ($action == 'update') {

            // not to update hits, it could be bigger
            $eobj->unsetProperties('downloads');
            
            if (!empty($this->rq->entry_id)) {
                $entry_id = $this->rq->entry_id;
                
            } else {
                $draft_data = $manager->getById($this->rq->id);
                $entry_id = $draft_data['entry_id'];
            }
            
            $file_data = $emanager->getById($entry_id);
            
            if ($controller->action != 'insert') {
                $filename = ($file_data['filename_disk']) ? $file_data['filename_disk'] : $file_data['filename'];
                $old_file = $file_data['directory'] . $filename;
                @unlink($old_file);
                
                $filename = ($eobj->get('filename_disk')) ? $eobj->get('filename_disk') : $eobj->get('filename');
                $movement_params = $manager->moveDraftFile($filename, $eobj->get('directory'), $file_dir);
                if ($movement_params['status']) {
                    $eobj->set('directory', $file_dir);
    
                    if ($movement_params['new_filename']) {
                        $eobj->set('filename', $movement_params['new_filename']);
                    }
                }
            }
            
            // order
            $sort_values = $emanager->getSortOrderByEntryId($eobj->get('id'));
            $eobj->setSortValues($sort_values);

        } else {
            $eobj->set('id', null);
            $eobj->set('date_posted', null);
        }

        if($emanager->setting['file_extract']) {

            require_once APP_EXTRA_MODULE_DIR . 'file_extractors/FileTextExctractor.php';

            $extension = strtolower(substr($eobj->get('filename'), strrpos($eobj->get('filename'), '.') + 1));

            $extractor = new FileTextExctractor($extension, $emanager->setting['extract_tool']);
            $extractor->setTool($emanager->setting['extract_tool']);
            $extractor->setExtractDir($emanager->setting['extract_save_dir']);

            $file = $eobj->get('directory') . $eobj->get('filename');
            $eobj->set('filetext', addslashes($extractor->getText($file)));
        }

        $entry_id = $emanager->save($eobj, $action);

        if (!empty($this->rq->id)) {
            $manager->deleteDraftEntry($this->rq->id);
        }

        return $entry_id;
    }
    

    function sendFile($manager, $emanager, $controller, $attachment) {

        $data = $manager->getById($this->rq->id);
        $eobj = unserialize($data['entry_obj']);

        if(!FileEntryDownload_dir::getFileDir($eobj->get(), $file_dir)) { // missing
            $eobj->is_missing = true;
            $eobj->set('id', $this->rq->id);

            $controller->loadClass('FileEntryView_delete', 'file/entry');
            $view = new FileEntryView_delete;

            $view = $view->execute($eobj, $emanager);

        } else {
            $emanager->sendFileDownload($eobj->get(), $attachment);
            exit;
        }
    }


    function upload($eobj, $emanager, $obj, $manager, $controller, $draft_dir) {

        // when updating and upload file with the same name we need replace old file
        // if new name we need delete old file from disk
		$rename_file = true;
		$old_file = FileEntryDownload_dir::getFileDir($eobj->get(), $draft_dir);
        if($controller->action == 'update') {
            $fname = ($obj->get('filename_disk')) ? $eobj->get('filename_disk') : $eobj->get('filename');
            if($_FILES['file_1']['name'] == $fname) {
                $rename_file = false;
            }
        }

        $upload = $emanager->upload($rename_file, false, $draft_dir);


		if(!empty($upload['error_msg'])) {
			$this->rp->stripVars('stripslashes_display');
			$eobj->populate($this->rp->vars, $emanager, true);
            $eobj->error = $obj->error = Uploader::errorBox($upload['error_msg']);

		} else {

            $eobj->populateFile($upload['good'][1], $emanager, false);

            // when updating need to delete old file, new uploaded and it has other name
            if($controller->action == 'update' && $rename_file) {
                unlink($old_file);
            }

            $obj->populate($this->rp->vars, $eobj, $emanager);
		}
    }


    // $eobj, $emanager - enty,
    function submitForApproval($eobj, $emanager, $obj, $manager, $controller, $workflow, $step_comment = false) {

        if(!$step_comment && isset($this->rp->step_comment)) {
            $step_comment = $this->rp->step_comment;
        }

        $assignees = $manager->getAssignees($obj, $eobj, $emanager, $workflow, 1); // 1st in the chain
        $step_id = $manager->moveToStep($obj->get('id'), $workflow['id'], 1, '', $step_comment, 1);
        $manager->saveAssignees($obj->get('id'), $step_id, $assignees);

        $step_comment = RequestDataUtil::stripslashes($step_comment);
        $sent = $manager->sendDraftReview($obj, $controller, $step_comment, $assignees);
    }

}

?>