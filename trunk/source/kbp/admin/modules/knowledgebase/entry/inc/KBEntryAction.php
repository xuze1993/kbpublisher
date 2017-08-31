<?php

class KBEntryAction extends AppAction
{

    function lock($obj, $manager, $controller) {
        
        $controller->loadClass('KBEntryView_lock', 'knowledgebase/entry');
        $view = new KBEntryView_lock;
        
        if(isset($this->rp->submit)) {
            $manager->setEntryReleased($this->rq->id);
            $more = array('id'=>$this->rq->id);
            if(!empty($this->rq->referer)) {
                $more['referer'] = $this->rq->referer;
            }

            // on submit return to emode
            if(!empty($this->rq->referer) && $this->rq->referer == 'emode') {
                $view->entry_released = true;
                
            } else {
                $action = (isset($this->rq->back)) ? $this->rq->back : 'update';
                $controller->goPage('this', 'this', false, $action, $more);
            }
        }

        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        
        $view = $view->execute($obj, $manager);

        return $view;
    }
    
    
    function autosave($obj, $manager, $controller) {
        
        $controller->loadClass('KBEntryView_autosave', 'knowledgebase/entry');
        $view = new KBEntryView_autosave;
        
        if(isset($this->rp->submit)) {

            $manager->deleteAutosave($this->rq->id);
            $more = array('id'=>$this->rq->id);
            if(!empty($this->rq->referer)) {
                $more['referer'] = $this->rq->referer;
            }

            // on submit return to emode
            if(!empty($this->rq->referer) && $this->rq->referer == 'emode') {
                $view->autosave_skipped = true;
            
            } else {
                $controller->goPage('this', 'this', false, 'update', $more);
            }
        }

        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('date_updated', $data['date_updated']);
        
        $view = $view->execute($obj, $manager);
        
        return $view;
    }
    
    
    function draftRemove($obj, $manager, $controller, $priv) {
        
        $controller->loadClass('KBDraft', 'knowledgebase/draft');
        $controller->loadClass('KBDraftView_note_delete_entry', 'knowledgebase/draft');
        
        if ($controller->module == 'knowledgebase') {
            $controller->loadClass('KBDraftModel', 'knowledgebase/draft');    
            $emanager = new KBDraftModel();
            $priv->setPrivArea('kb_draft');
            
        } else {
            $controller->loadClass('FileDraftModel', 'file/draft');
            $emanager = new FileDraftModel();
            $priv->setPrivArea('file_draft');
        }
        
        $data = $emanager->getByEntryId($this->rq->id, $manager->entry_type);
        $this->rp->stripVarsValues($data);
        
        $draft_id = $data['id'];
        
        if(isset($this->rp->submit)) {
            $emanager->checkPriv($priv, 'delete', $draft_id, $controller, $manager);
            $emanager->delete($draft_id);
            
            $more = array('id' => $this->rq->id);
            $controller->goPage('this', 'this', false, 'delete', $more);
        }
        
        $priv->use_exit_screen = false;
        $allowed = $emanager->checkPriv($priv, 'delete', $draft_id, $controller, $manager);
        
        $eobj = new KBDraft;
        $eobj->set($data);
        $eobj->set('date_updated', $data['date_updated']);
        
        // ongoing approval
        $last_event = $emanager->getLastApprovalEvent($eobj->get('id'));        
        if ($emanager->isBeingApproved($last_event)) {
            $eobj->sent_to_approval = true;
        }

        $view = new KBDraftView_note_delete_entry;
        $view = $view->execute($eobj, $emanager, $allowed);
        
        return $view;
    }
    
    
    function convert($obj, $manager, $controller) {
        
        require_once 'eleontev/Webservice/FileToHtmlWebService.php';
    
        if(!empty($_FILES['file']['name'])) {
            
            $ws = new FileToHtmlWebService;
            //$ws->ssl = true;
            
            $reg = Registry::instance();
            $conf = $reg->getEntry('conf');
            $ws->api_url = $conf['web_service_url'];
            
            $status = $ws->isFileConvertible($_FILES['file']);
            
            $data = array();
            if (!empty($status['error'])) {
                $data['error'] = $status['error'];
    
            } else {
                $response = $ws->sendFile($_FILES['file']['tmp_name'], $_FILES['file']['name']);
                $result = $ws->parseResponse($response);
    
                if (!empty($result['error'])) {
                    $data['error'] = $result['error'];
    
                } else {
                    $data = array('content' => $result['content']);
                }
            }
            
            echo json_encode($data);
            exit;
        }
        
        $controller->loadClass('KBEntryView_convert', 'knowledgebase/entry');
        $view = new KBEntryView_convert;
        $view = $view->execute($obj, $manager);
        
        return $view;
    }


    function attachment($obj, $manager, $controller) {
        
        if(!empty($_FILES['file']['name'])) {

            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntry.php';
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel_dir.php';
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryDownload_dir.php';

            $data = array();

            $f_obj = new FileEntry;
            $f_obj->set('date_posted', null);
            $f_obj->set('author_id', null);
            $f_obj->set('updater_id', null);
            $f_obj->setSortValues(array(1 => 'sort_end'));

            $f_manager = new FileEntryModel_dir;
            $setting = SettingModel::getQuick(1);
            $setting = $f_manager->setFileSetting($setting);

            // category
            $category_id = $manager->getAttachmentCategory();
            if(!$category_id) {
                require_once APP_MODULE_DIR . 'file/category/inc/FileCategory.php';
                $fc_obj = new FileCategory;
                $category_id = $manager->createAttachmentCategory($fc_obj, $f_manager->cat_manager);
            }
            $f_obj->setCategory(array($category_id));


            $upload = $f_manager->upload(true, $_FILES['file']);

            if(!empty($upload['error_msg'])) {
                //$data['error'] = ...

            } else {
                $content = $f_manager->getFileContent($upload['good'][1]['to_read']);
                if($content) {
                    $f_obj->set('filename', addslashes($upload['good'][1]['name']));
                    $f_obj->set('directory', $upload['good'][1]['directory']);
                    $f_obj->set('filesize', $upload['good'][1]['size']);
                    $f_obj->set('filetype', addslashes($upload['good'][1]['type']));
                    $f_obj->set('md5hash', md5_file($upload['good'][1]['to_read']));
                    $f_obj->set('filename_index', addslashes($f_manager->getFilenameIndex($upload['good'][1]['name'])));
                    $f_obj->set('filename_disk', addslashes($upload['good'][1]['name_disk']));

                    if($f_manager->setting['file_extract']) {

                        require_once APP_EXTRA_MODULE_DIR . 'file_extractors/FileTextExctractor.php';

                        $ext = $upload['good'][1]['extension'];

                        $extractor = new FileTextExctractor($ext, $setting['extract_tool']);
                        //$extractor->setDecode('windows-1251', 'UTF-8'); // example
                        $extractor->setTool($setting['extract_tool']);
                        $extractor->setExtractDir($setting['extract_save_dir']);

                        $f_obj->set('filetext', addslashes($extractor->getText($upload['good'][1]['to_read'])));
                    }

                    $entry_id = $f_manager->save($f_obj, 'insert', true);
                    $data = array('id' => $entry_id, 'name' => $f_obj->get('filename'));

                } else {
                    //$data['error'] = ...
                }

            }

            echo json_encode($data);
            exit;
        }
        
        
        $controller->loadClass('KBEntryView_attachment', 'knowledgebase/entry');
        $view = new KBEntryView_attachment;
        $view = $view->execute($obj, $manager);
        
        return $view;
    }
    
    
    function createDraftFromEntry($obj, $manager, $controller, $entry_id) {
        
        if($draft_id = $manager->isEntryDrafted($entry_id)) {
            $rlink = $controller->getCommonLink();
            $referer = WebUtil::serialize_url($rlink);
            $more = array('id' => $entry_id, 'referer' => $referer);

            $controller->goPage('this', 'this', false, 'draft_remove', $more);
        }
        
        if ($controller->module == 'knowledgebase') {
            $controller->loadClass('KBDraft', 'knowledgebase/draft');
            $controller->loadClass('KBDraftModel', 'knowledgebase/draft');
            $draft_obj = new KBDraft;
            $draft_manager = new KBDraftModel;
            
        } else {
            $controller->loadClass('FileDraft', 'file/draft');
            $controller->loadClass('FileDraftModel', 'file/draft');
            $draft_obj = new FileDraft;
            $draft_manager = new FileDraftModel;
        }

        $data = $manager->getById($entry_id);
        $this->rp->stripVarsValues($data, 'addslashes');

        $obj->collect($entry_id, $data, $manager, 'save');
        $obj->set('id', null);
        $obj->set('author_id', null);
        $obj->set('date_posted', null);
        $obj->set('date_updated', null);

        $draft_obj->populate($data, $obj, $manager);
        $draft_obj->set('entry_id', 0);
        $draft_id = $draft_manager->save($draft_obj);
        
        return $draft_id;
    }

}

?>