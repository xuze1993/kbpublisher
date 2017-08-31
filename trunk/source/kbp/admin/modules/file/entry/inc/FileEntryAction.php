<?php

class FileEntryAction extends AppAction
{

    function sendFile($obj, $manager, $controller, $attachment) {
        
        $data = $manager->getById($this->rq->id);
        $file_dir = $manager->getSetting('file_dir');
        
        if(!FileEntryDownload_dir::getFileDir($data, $file_dir)) { // missing
            $controller->removeMoreParams('show_msg2');
            $this->rp->stripVarsValues($data);
            $obj->set($data);
            
            $obj->is_missing = true;
        
            $view = $controller->getView($obj, $manager, 'FileEntryView_delete');
            return $view;
            
        } else {
            $manager->sendFileDownload($data, $attachment);
            exit;
        }
    }


    function fileText($obj, $manager, $controller) {
        
        if(isset($this->rp->submit)) {

            $this->rp->stripVars();
            $manager->updateFileText($this->rp->filetext, $this->rq->id);

            $return = $controller->getCurrentLink();
            $controller->setCustomPageToReturn($return, false);
            $controller->go();

        } else {

            $data = $manager->getById($this->rq->id);
            $this->rp->stripVarsValues($data);
            $obj->set($data);

            $obj->text = $manager->getFileText($this->rq->id);
            $this->rp->stripVarsValues($obj->text);
        }
        
        
        $controller->loadClass('FileEntryView_text', 'file/entry');
        $view = new FileEntryView_text;    
        $view = $view->execute($obj, $manager);

        return $view;
    }

}

?>