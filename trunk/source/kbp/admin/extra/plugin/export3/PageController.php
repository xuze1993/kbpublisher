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

require_once APP_CLIENT_DIR . 'client/inc/KBClientBaseModel.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientModel.php';
     
$controller->loadClass('Export');
$controller->loadClass('ExportModel');
$controller->loadClass('KBExport'); 

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Export;

$manager =& $obj->setManager(new ExportModel());
$priv->setCustomAction('file', 'select');
$priv->setCustomAction('category', 'select');
$priv->setCustomAction('generate', 'update');  
$manager->checkPriv($priv, $controller->action, @$rq->id);


$setting = KBClientModel::getSettings(array(2, 100, 140, 150));


switch ($controller->action) {
case 'category': // ------------------------------
    $controller->loadClass('KBEntryView_category', 'knowledgebase/entry');
    $view = new KBEntryView_category;
    $view = $view->execute($obj, $manager->emanager);
    break;
    
     
case 'update':
case 'insert': // ------------------------------
    
    if(isset($rp->submit_save) || isset($rp->submit_generate)) {

        $is_error = $obj->validate($rp->vars, $manager);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            
            if (!empty($rp->vars['category'])) {
                $obj->setCategory(@$rp->vars['category']);
            }
            
            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }
            
            $obj->setUserMode(@$rp->vars['user']);
            
            $columns = (empty($rp->vars['generated'])) ? array() : $rp->vars['generated'];
            $obj->setColumns($columns);
            
            $export_option = (isset($rp->params)) ? $rp->params : array();
            $export_option['do'] = @$rp->do;
        
        } else {
                     
            $export_option = (isset($rp->params)) ? $rp->params : array();
            $export_option['do'] = $rp->do;
            
            $export_option['user_id'] = ($rp->user == 1) ? 0 : 1;           
            $export_option['category_id'] = ($rp->category[0] != 'all') ? $rp->category[0] : 0;   

            $export_option['user_mode'] = (!empty($rp->user)) ? $rp->user : 0;
            $export_option['priv_id'] = ($rp->user == 0) ? 1 : 0;
            $export_option['role_ids'] = (isset($rp->role) && $rp->user == 2) ? $rp->role : array();
            
            $export_option['columns'] = $rp->vars['generated'];
            $export_option_ser = addslashes(serialize($export_option));
            
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $obj->set('export_option', $export_option_ser);
            $obj->set('user_id', AuthPriv::getUserId());
            
            $export_id = $manager->save($obj);
            if($controller->action == 'update') {
                $export_id = $obj->get('id');
            }
            
            $obj->set('id', $export_id);
            
            // generate
            if(isset($rp->submit_generate)) {
                
                $ret = $manager->generate($obj, $export_option, $setting);
                if(!$ret) {
                    $manager->deleteExportData($export_id);
                    $controller->go('success', true, 'no_export_data');
                }
                
                $more = array('id' => $obj->get('id'));
                $controller->goPage('this', 'this', false, 'detail', $more);
            }
            
            $controller->go();
        }
        
    } elseif($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);       
        $export_option = unserialize($data['export_option']);
        
        $obj->setCategory(array($export_option['category_id']));
        $obj->setUserMode($export_option['user_mode']);
        $obj->setColumns($export_option['columns']);
        
        if(!empty($export_option['role_ids'])) {
            $obj->setRole($export_option['role_ids']);
        }

        $rp->stripVarsValues($data);
        $obj->set($data);
    }

    
    $export_data = (isset($export_option)) ? $export_option : array();
    $view = $controller->getView($obj, $manager, 'ExportView_form', $export_data);
    
    break;
    
    
case 'generate': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $obj->set($data);
    
    $export_option = unserialize($data['export_option']);    
    $export_option['do'] = array($manager->export_types[$rq->type] => 'on');
    
    $ret = $manager->generate($obj, $export_option, $setting);
    
    $more = array('id' => $rq->id);
    if(!$ret) {
        $more['show_msg'] = 'no_export_data';
    }    

    $controller->goPage('this', 'this', false, 'detail', $more);

    break;
    
    
case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $obj->set($data);
    $view = $controller->getView($obj, $manager, 'ExportView_detail');

    break;
    
    
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    
        
case 'status': // ------------------------------     
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'file': // --------------------------------
    
    $data = $manager->getById($rq->id); 
    
    $file_data = &$manager->getFileData($rq->id, $rq->type);
    $file_data['filename'] = $data['title'];

    $manager->sendFileDownload($file_data, $rq->type, 1);
    exit;

    break;        

    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'ExportView_list');
}    
?>