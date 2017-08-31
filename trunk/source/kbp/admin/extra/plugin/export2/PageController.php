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
$controller->loadClass('KBExport2'); 

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Export;

$manager =& $obj->setManager(new ExportModel());
$priv->setCustomAction('file', 'select');
$priv->setCustomAction('template', 'select');
$priv->setCustomAction('preview', 'select');
$priv->setCustomAction('category', 'select');
$priv->setCustomAction('generate', 'update');  
$manager->checkPriv($priv, $controller->action, @$rq->id);


// htmldoc
$setting = KBClientModel::getSettings(array(2, 100, 140, 150));
$htmldoc = BaseModel::isPluginExport2($setting);
$htmldoc_demo = ($htmldoc === 'demo');

$setting['htmldoc_demo'] = $htmldoc_demo;

if(!$htmldoc) {
    $controller->action = 'unavailable';
    
} elseif($htmldoc === 'demo') {
    if(BaseModel::isCloud()) {
        $module_info_msg = AppMsg::pluginBox('export_plugin_demo_note_cloud');
    } else {
        $module_info_msg = AppMsg::pluginBox('export_plugin_demo_note');
    }
}


switch ($controller->action) {
case 'unavailable':    
    
    $view = $controller->getView($obj, $manager, 'ExportView_msg');
    break;
    
    
case 'category': // ------------------------------
    $controller->loadClass('KBEntryView_category', 'knowledgebase/entry');
    $view = new KBEntryView_category;
	
    $msg = AppMsg::getMsgs('common_msg.ini', 'knowledgebase');
    $msg2 = AppMsg::getMsgs('common_msg.ini', 'export');
    
    $msg = array_merge($msg, $msg2);
    
	$options = array(
		'all' => true,
		'limit' => 1,
		'sortable' => false,
		'non_active_state' => 'disabled',
		'creation' => false,
		'secondary_block' => false,
		'status_icon' => true,
        'cancel_button' => true,
		'mode' => 'entry',
		'select_id' => 'category',
		'handler_name' => 'selHandler',
        'popup_title' => $msg['assign_category_msg'],
        'main_title' => $msg['assigned_category_msg'],
		'msg' => array(
		    'non_active_category_msg' => $msg['export_non_active_category_msg']
        )
    );
    
    $categories = $manager->emanager->getCategoryRecordsUser();  // private removed
    
    $view = $view->parseCategoryPopup($manager->cat_manager, $categories, $options);
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
            
            if (in_array('pdf', array_keys($export_option['do']))) {
                $keys = array('cover', 'header', 'footer');
                foreach ($keys as $key) {
                    if (!empty($export_option['pdf'][$key])) {
                        $export_option['pdf'][$key] = $export_option['pdf'][$key . '_tmpl'];                        
                    }
                    
                    unset($export_option['pdf'][$key . '_tmpl']);
                }
            }
            
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
            if(isset($rp->submit_generate) && $htmldoc) {
                
                $ret = $manager->generate($obj, $export_option, $setting);
                if($ret == 'no_cats') {
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
    if($ret == 'no_cats') {
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


case 'template':    
    
    $view = $controller->getView($obj, $manager, 'ExportView_template');
    break;


case 'preview':
    
    $controller->loadClass('SettingViewPluginExportTest_popup', 'setting/setting');
    $view = new SettingViewPluginExportTest_popup;
    
    $options = array(
        'check_setting' => false,
        'config' => array(
            'tool_path' => SettingModel::getQuick(140, 'plugin_wkhtmltopdf_path'),
            'print_entry_info' => !(empty($rp->params['print_info']))
        )
    );
    
	$keys = array('cover', 'header', 'footer');
    
    foreach ($keys as $key) {
        if (!empty($rp->params['pdf'][$key . '_tmpl_tmp'])) {
            $options['config']['settings'][$key] = $rp->params['pdf'][$key .  '_tmpl_tmp'];
                                    
        } elseif (!empty($rp->params['pdf'][$key])) {
            $options['config']['settings'][$key] = $rp->params['pdf'][$key . '_tmpl'];                        
        }
    }
    
    $view = $view->execute($obj, $manager, $options);
    break;    
    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'ExportView_list');
}    
?>