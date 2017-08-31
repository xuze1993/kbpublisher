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

require_once 'core/common/CommonCustomFieldModel.php';

$controller->loadClass('CustomFieldRangeGroup');
$controller->loadClass('CustomFieldRangeGroupModel');
$controller->loadClass('CustomFieldRangeValue');
$controller->loadClass('CustomFieldRangeValueModel');
$controller->loadClass('CustomFieldModel', 'tool/custom_field');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

if(!isset($rq->range_id)) {
    
    $obj = new CustomFieldRangeGroup;
    $manager =& $obj->setManager(new CustomFieldRangeGroupModel());

    $list_view = 'CustomFieldRangeGroupView_list';
    $form_view = 'CustomFieldRangeGroupView_form';

} else {
    
    $obj = new CustomFieldRangeValue;
    $manager =& $obj->setManager(new CustomFieldRangeValueModel());
    
    $manager2 = new CustomFieldRangeGroupModel();
    $group_info = $manager2->getById($rq->range_id);
    
    $obj->group_title = $group_info['title'];
    $obj->set('range_id', $rq->range_id);
    
    $controller->setMoreParams('range_id');
    $controller->setCommonLink();

    $manager->setSqlParams('AND range_id = ' . $rq->range_id);
    
    $list_view = 'CustomFieldRangeValueView_list';
    $form_view = 'CustomFieldRangeValueView_form';
}

$priv->setCustomAction('insert_group', 'insert');
$priv->setCustomAction('update_group', 'update');
$priv->setCustomAction('delete_group', 'delete');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete_group': // ------------------------------
    
    if($manager->isRangeInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    
case 'delete': // ------------------------------

    if($manager->isRangeValueInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    $manager->delete($rq->id);  
    $link = $controller->getLink('this', 'this', 'this', false, array('range_id'=>$rq->range_id));
    
    $controller->setCustomPageToReturn($link, false);
    $controller->go();
    break;    

    
case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
        
            $id = $manager->save($obj, $controller->action);
            
            if (!isset($rq->range_id)) {
                $more = array('range_id' => $id);
                
                if (isset($rq->popup)) {
                    $more['popup'] = 1;
                }
                
                $link = $controller->getLink('this', 'this', 'this', 'insert', $more);
                $controller->setCustomPageToReturn($link, false);
                $controller->go();;

            } else {
                $controller->go();
            }
        }
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data);
        
    } else {
        
        // for entry in range
        if(isset($rq->range_id)) {
            $sort_order = $manager->getMaxSortOrder($obj->get('range_id'));
            $obj->set('sort_order', $sort_order + 1);            
        }
    }
    
    $view = $controller->getView($obj, $manager, $form_view);

    break;
    
    
case 'update_group': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $manager->save($obj);
            $controller->go();
        }
    }
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, $form_view);

    break;
    

default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, $list_view);  
}
?>