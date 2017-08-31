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

$controller->loadClass('WorkflowEntry');
$controller->loadClass('WorkflowEntryModel');
$controller->loadClass('WorkflowModel');
$controller->loadClass('WorkflowParser');
$controller->loadClass('WorkflowParserAction');
$controller->loadClass('WorkflowParserCondition');


// initialize objects
$rq = new RequestData($_GET);
$rp = new RequestData($_POST);
// $rp->setSkipKeys('cond'); // to skip not strip html
// $rp->setSkipKeys('action');

$obj = new WorkflowEntry;
$manager = new WorkflowEntryModel();

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
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------

    if($manager->isWorkflowInUse($rq->id)) {
        $controller->go('noneditable_workflow', true);
    }
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        /*if ($controller->action == 'update') {
            if($manager->isWorkflowInUse($rq->id)) {
                $controller->go('noneditable_workflow', true);
            }
        }*/
        
        $is_error =& $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('trigger_key_temp', $obj->get('trigger_key')); // to copy predefined 
        
        } else {
            
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $cond = RequestDataUtil::stripslashes($rp->cond);
            $obj->set('cond', addslashes(WorkflowParser::pack($cond)));
            
            $action = array_combine(range(1, count($rp->action)), array_values($rp->action));
            $action = RequestDataUtil::stripslashes($action);
            $obj->set('action', addslashes(WorkflowParser::pack($action)));
            
            if(in_array($controller->action, array('insert', 'clone'))) {
                $max_sort_order = $manager->getMaxSortOrder();
                $obj->set('sort_order', $max_sort_order + 1);
            }
            
            $manager->saveAddUpdate($obj);
            
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $data['cond'] = WorkflowParser::unpack($data['cond']);
        $data['action'] = WorkflowParser::unpack($data['action']);
        // echo '<pre>', print_r($data, 1), '</pre>';
        
        $rp->stripVarsValues($data);
        $obj->set($data, false, $controller->action);
        $obj->set('trigger_key_temp', $data['trigger_key']); // to copy predefined 
    }

    $view = $controller->getView($obj, $manager, 'WorkflowEntryView_form');

    break;
    
    
case 'default': // ------------------------------

    if($manager->isWorkflowInUse()) {
        $controller->go('noneditable_workflow', true);
    }
    
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

    if (!empty($sql[4])) { // workflows
        $sql = RequestDataUtil::stripVars($sql[4], array(), 'addslashes');
        $key = $manager->getDefaultSqlSettingKey();
        
        $sm = new SettingModel();
        $setting_id = $sm->getSettingIdByKey($key);
        $sm->updateDefaultValue($setting_id, $sql);
        // $sm->setSettings(array($setting_id => $sql));
    }

    $controller->go();

    break;
    

default: // ------------------------------------

    if(isset($rp->submit)) { // sort order
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, 'WorkflowEntryView_list');
}
?>