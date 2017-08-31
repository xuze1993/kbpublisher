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

$controller->loadClass('TriggerEntry');
$controller->loadClass('TriggerEntryModel');
$controller->loadClass('TriggerModel');
$controller->loadClass('TriggerParser');
$controller->loadClass('TriggerParserAction');
$controller->loadClass('TriggerParserCondition');


// initialize objects
$rq = new RequestData($_GET);
$rp = new RequestData($_POST);
// $rp->setSkipKeys('cond'); // to skip not strip html
// $rp->setSkipKeys('action');


$obj = new TriggerEntry;
$manager = new TriggerEntryModel();

if(isset($manager->trigger_types[$controller->page]) && 
   isset($manager->entry_types[$controller->sub_page])) {
       
    $manager->trigger_type = $manager->trigger_types[$controller->page];
    $manager->entry_type = $manager->entry_types[$controller->sub_page];

    $obj->set('trigger_type', $manager->trigger_type);
    $obj->set('entry_type', $manager->entry_type);
    
} else {
    die('Wrong page');
}


$priv->setCustomAction('clone', 'insert');
$priv->setCustomAction('default', 'insert');
$priv->setCustomAction('dump', 'insert');
$priv->setCustomAction('category', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'category': // ------------------------------
    $model_name = ($manager->entry_type == 1) ? 'KB' : 'File';
    $emanager = TriggerModel::instance($model_name . 'EntryModel');
    
    $controller->loadClass('KBEntryView_category', 'knowledgebase/entry');
    $view = new KBEntryView_category;
    
    $options = array(
        'limit' => 1,
        'sortable' => false,
        'creation' => false,
        'secondary_block' => false,
        'status_icon' => true,
        'cancel_button' => true,
        'mode' => 'trigger',
        'select_id' => $_GET['field_id'],
        'popup_title' => $msg['assign_category_msg'],
        'main_title' => $msg['category_msg']
    );
    
    $view = $view->execute($obj, $emanager, $options);
    break;
    
    
case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error =& $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('trigger_key_temp', $obj->get('trigger_key')); // to copy predefined 
        
        } else {
            
            $rp->stripVars();
            $obj->set($rp->vars);
            
            if ($obj->get('trigger_type') != 3) {
                $cond = RequestDataUtil::stripslashes($rp->cond);
                $obj->set('cond', addslashes(TriggerParser::pack($cond)));
            }

            $action = RequestDataUtil::stripslashes($rp->action);
            $obj->set('action', addslashes(TriggerParser::pack($action)));
            
            if(in_array($controller->action, array('insert', 'clone'))) {
                $max_sort_order = $manager->getMaxSortOrder();
                $obj->set('sort_order', $max_sort_order + 1);
            }
            
            $manager->saveAddUpdate($obj);
            
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $data['cond'] = TriggerParser::unpack($data['cond']);
        $data['action'] = TriggerParser::unpack($data['action']);
        // echo '<pre>', print_r($data, 1), '</pre>';
        
        $rp->stripVarsValues($data);
        $obj->set($data, false, $controller->action);
        $obj->set('trigger_key_temp', $data['trigger_key']); // to copy predefined 
    }

    $view = $controller->getView($obj, $manager, 'TriggerEntryView_form');

    break;
    
    
case 'default': // ------------------------------

    $manager->deleteTriggerByEntryType();
    
    $key = $manager->getDefaultSqlSettingKey();
    $default_sql = SettingModel::getQuick(20, $key);
    if ($default_sql) {
        $manager->runDefaultSql($obj->get('trigger_type'), $default_sql);
    }
    
    $controller->go();
    
    break;


case 'dump': // ------------------------------

    $sql = $manager->getDefaultSql();

    if (!empty($sql[2])) { // automations
        $sql = RequestDataUtil::stripVars($sql[2], array(), 'addslashes');
        $key = $manager->getDefaultSqlSettingKey();
        
        $sm = new SettingModel();
        $setting_id = $sm->getSettingIdByKey($key);
        $sm->updateDefaultValue($setting_id, $sql);
    }

    $controller->go();

    break;
    

default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, 'TriggerEntryView_list');
}
?>