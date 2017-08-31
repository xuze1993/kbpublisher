<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

$controller->loadClass('Priv');
$controller->loadClass('PrivModel');
$controller->loadClass('PrivStatusModel');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Priv;

$manager =& $obj->setManager(new PrivModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    if(APP_DEMO_MODE) { 
        $controller->go('not_allowed_demo', true);
    }    
    
    if($manager->isPrivInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    if(!$manager->isPrivEditable($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }    
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    if(!$manager->isPrivEditable($rq->id)) {
        $controller->go('', true);
    }        
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    $editable = true;
    if(isset($rp->submit)) {
        
        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true); 
        }
        
        $is_error = $obj->validate($rp->vars);
                
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->setPriv($rp->vars['priv']);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setPriv($rp->vars['priv']);
            
            // set status active for not editable (admin)
            if(!$manager->isPrivEditable($rq->id)) {
                $obj->set('active', 1);
            }
            
            $manager->save($obj);
            
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data, false, $controller->action);
        
        if($manager->isPrivEditable($rq->id)) {
            $editable = true;
            $obj->setPriv($manager->getPrivRules($rq->id));
        } else {
            $editable = false;
            $obj->set('sort_order', 1);
        }
    }
    
    $form = ($editable) ? 'PrivView_form_rule' : 'PrivView_form';
    $view = $controller->getView($obj, $manager, $form, $editable);
    
    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, 'PrivView_list');
}
?>