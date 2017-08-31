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

$controller->loadClass('KBEntry', 'knowledgebase/entry');
$controller->loadClass('KBEntryModel', 'knowledgebase/entry');
$controller->loadClass('KBCategory', 'knowledgebase/category');

$controller->loadClass('KBEntryImport');
$controller->loadClass('KBEntryImportModel');

require_once 'eleontev/Dir/Uploader.php';


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new KBEntryImport;
$eobj = new KBEntry;

//$manager2 = new KBEntryModel(); 
//$manager->checkPriv($priv, $controller->action, @$rq->id);
$priv->check();

$manager = new KBEntryImportModel( new KBEntryModel() );

// update menu, maybe not good idea 
// admin/index.php?module=import&page=kb_entry"
if($imported_cat_id = $manager->isCategory()) {
    $search = 'admin/index.php?module=import&page=kb_entry';
    $replace = 'admin/index.php?module=import&page=kb_entry&filter[c]=' . $imported_cat_id;
    $menu = str_replace($search, $replace, $menu);
}


if(isset($rp->submit)) {
    
    if(APP_DEMO_MODE) { 
        $controller->go('not_allowed_demo', true);
    }
    
    
    $is_error = $obj->validate($rp->vars, $manager);
    $rp->vars['file_2'] = str_replace('\\', '/', stripslashes($rp->vars['file_2']));        
    
    if(!$is_error) {
        if($_FILES['file_1']['name']) {
            $upload = $manager->upload();
        } else {
            $upload['filename'] = $rp->vars['file_2'];
        }

        if(!empty($upload['error_msg'])) {
            $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
            $is_error = true;
        }
    }
    
    
    if($is_error) {
        $rp->stripVars(true);
        $obj->set($rp->vars);
    
    } else {
    
        $rp->stripVars();
        $obj->set($rp->vars);        
        
        
        $category_id = $manager->isCategory();
        if(!$category_id) {
            $category_id = $manager->createCategory();
        }
        
        // $eobj->set($rp->vars);
        // $eobj->set('category_id', $category_id);
        
        // echo '<pre>', print_r($eobj->get(), 1), '</pre>';
        // exit;
        
        $data['category_id'] = $category_id;
        // $obj->set('author_id'] = $priv->getUserId();
        
        $ret = $manager->import($rp->vars['generated'], $data, $upload['filename'], $rp->vars);
        $f = ($conf['db_driver'] === 'mysql') ? 'mysql_info' : 'mysqli_info';
        $import_result = $f($manager->model->db->_connectionID);
        
        if($_FILES['file_1']['name']) {
            unlink($upload['filename']);
        }
        
        // if error in import (returned by mysql)    
        if($ret !== true) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            
            $_POST['optionally_enclosed'] = stripslashes($_POST['optionally_enclosed']);
            $_POST['lines_terminated'] = stripslashes($_POST['lines_terminated']);
        
            $obj->errors['formatted'][]['msg'] = $ret;
        
        } else {
            
            $manager->setBodyIndexTask();
            // $manager->setMetaKeywordsTask(); // not implemented, user can't add keywords to import
            
            // $manager->setEntryCategory($category_id);
            $manager->setEnryToCategory($category_id);
            
            // $manager->setEntryAuthor($priv->getUserId());
            // $manager->setEntryUpdater($priv->getUserId());
            // $manager->setEntryDatePosted();
            $manager->setEntryHits($category_id);
            
            
            $msg2 = AppMsg::getAfterActionMsg('import_result');
            $msg2['body'] = $import_result;
            $_SESSION['msg_']['import_result'] = $msg2;
            
            $controller->go('success', false, 'import_result-success');
        }
    }
}

$view = $controller->getView($obj, $manager, 'KBEntryImportView_form');

?>