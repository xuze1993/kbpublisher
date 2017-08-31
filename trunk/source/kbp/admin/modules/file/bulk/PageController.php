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


require_once 'eleontev/Dir/MyDir.php';
require_once 'eleontev/Dir/mime_content_type.php';
require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Util/TimeUtil.php';


$controller->loadClass('FileCategoryModel', 'file/category');
$controller->loadClass('RoleModel', 'user/role');
$controller->loadClass('ListValueModel', 'setting/list');

$controller->loadClass('FileEntry', 'file/entry');
$controller->loadClass('FileEntryModel', 'file/entry');
$controller->loadClass('FileEntryModel_dir', 'file/entry');
$controller->loadClass('FileEntryView_common', 'file/entry');
//$controller->loadClass('FileEntryModel_db');

$controller->loadClass('FileDraft', 'file/draft');
$controller->loadClass('FileDraftModel', 'file/draft');
$controller->loadClass('FileDraftAction', 'file/draft');

$controller->loadClass('BulkFileEntry');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('schedule', 'schedule_on'));
$controller->rp = &$rp;


$obj = new BulkFileEntry;
$manager =& $obj->setManager( new FileEntryModel_dir );

$draft_manager = new FileDraftModel;
$draft_action = new FileDraftAction($rq, $rp);

// settings
$setting = SettingModel::getQuick(1);
$setting = $manager->setFileSetting($setting);

// $manager->checkPriv($priv, 'insert'); // this from FileEntryModel
$priv->check('insert');


if ($controller->action == 'category') {
    $controller->loadClass('FileEntryView_category', 'file/entry');
    $view = new FileEntryView_category;
    $view = $view->execute($obj, $manager);

} else {
    $files = array();
    
    if(isset($rp->submit) || isset($rp->submit_draft) || isset($rp->submit_approve)) {
        $is_error =$obj->validate2($rp->vars, $manager);
    
        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);
    
        } else {
    
            @set_time_limit(0);
            ignore_user_abort();
    
            $rp->stripVars();
            $obj->populate($rp->vars, $manager);
    
            foreach($rp->files as $k => $file) {
    
                $upload = $manager->getFileData($file);
                $content = $manager->getFileContent($upload['to_read']);
                
                if($content) {
    
                    $file_id = $manager->saveFileData($content);
                    
                    // add file 
                    if (isset($rp->submit)) {
                        
                        $obj->populateFile($upload, $manager);
                        
                        $manager->save($obj, 'insert', true);
                        $module = 'file_entry';
                        
                    // add as draft
                    } else {
                        
                        $obj->populateFile($upload, $manager, false);
                        
                        $draft_obj = new FileDraft;
                        
                        // make FileEntry object
                        $eobj = new FileEntry;
                        $vars = get_object_vars($obj);
                        foreach($vars as $name => $value) {
                            $eobj->$name = $value;
                        }
                        
                        $draft_obj->populate($rp->vars, $eobj, $manager);                        
                        
                        $draft_id = $draft_manager->save($draft_obj);
                        if ($draft_id) {
                            $draft_obj->set('id', $draft_id);
                        }
                            
                        if (isset($rp->submit_approve)) {
                            $workflow = $draft_manager->getAppliedWorkflow();
                            $draft_action->submitForApproval($obj, $manager, $draft_obj, $draft_manager, $controller, $workflow);
                        }
                        
                        $module = 'file_draft';
                    }
                    
                }
            }
    
            $return = $controller->getLink('file', $module);
            $controller->setCustomPageToReturn($return, false);
            $controller->go();
        }
    
    } else {
    
        $obj->setAuthor($manager->getUser(AuthPriv::getUserId()));
    
        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);
    }
    
    
    $data = array('draft_manager' => $draft_manager);
    $view = $controller->getView($obj, $manager, 'BulkFileEntryView_form', $data);
}

?>