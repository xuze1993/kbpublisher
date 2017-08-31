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

$controller->loadClass('CustomField');
$controller->loadClass('CustomFieldModel');
$controller->loadClass('CustomFieldView_form');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setHtmlValues('html_template');
$rp->setCurlyBracesValues('html_template');

$obj = new CustomField;

if(isset($rq->entry_type)) {
    $obj->set('type_id', $rq->entry_type);
}

$manager =& $obj->setManager(new CustomFieldModel());
$priv->setCustomAction('apply', 'update');
$priv->setCustomAction('apply2', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);
$e_manager = new CommonCustomFieldModel();

$entry_type = substr($controller->sub_page, 3);
$type_id = array_search($entry_type, $manager->record_type);
if($type_id) {
    $obj->set('type_id', $type_id);
    
} else {
    die('Wrong page');
}


switch ($controller->action) {
case 'delete': // ------------------------------

    $entry_type = $manager->getEntryTypeById($rq->id);

    if(isset($rp->submit)) {
        $manager->deleteField($rq->id, $entry_type);
        $controller->go();
    }

    if($manager->isFieldInUse($rq->id, $entry_type)) {
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);

        $obj->set($data);
        $view = $controller->getView($obj, $manager, 'CustomFieldView_delete');

    } else {
        $manager->deleteField($rq->id, $entry_type);
        $controller->go();
    }

    break;


case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'update': // ------------------------------
case 'insert': // ------------------------------

    $form_view = 'CustomFieldView_form';

    if(isset($rp->submit)) {

        $is_error = $obj->validate($rp->vars, $e_manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->setCategory(@$rp->vars['category']);

            if (isset($rp->dv)) {
                $obj->set('default_value', implode(',', $rp->dv));
            }
            
            $form_view = $obj->getFormView($manager->record_type[$obj->get('type_id')], $controller);

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setCategory(@$rp->vars['category']);

            if (isset($rp->dv)) {
                $obj->set('default_value', implode(',', $rp->dv));
            }

            $id = $manager->save($obj, $controller->action);

            if ($controller->action == 'insert' && $obj->get('type_id') != 20) {
                if($manager->isEntryRecords($obj->get('type_id'))) {
                    $more = array('id' => $id);
                    $link = $controller->getLink('this', 'this', 'this', 'apply2', $more);
                    $controller->setCustomPageToReturn($link, false);
                }
            }

            $controller->go();
        }

    } elseif(isset($rq->input_id)) {
        $obj->set('input_id', $rq->input_id);
        
        $form_view = $obj->getFormView($manager->record_type[$obj->get('type_id')], $controller);

    } elseif ($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);
        $categories = $manager->getCategoryById($rq->id);

        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setCategory($categories);

        $entry_type = $manager->getEntryTypeById($rq->id);
        $form_view = $obj->getFormView($manager->record_type[$entry_type], $controller);

    } else {
        $class = 'CustomFieldView_form_' . $entry_type; 
        $controller->loadClass($class);
        $form_view = new $class;
        
        $view = $controller->getView($obj, $manager, 'CustomFieldView_form_field_type', $form_view);
        break;
    }

    $view = $controller->getView($obj, $manager, $form_view, $e_manager);

    break;


case 'apply': // ------------------------------
case 'apply2': // ------------------------------

    $data = $manager->getById($rq->id);
    $categories = $manager->getCategoryById($rq->id);

    if(isset($rp->submit)) {
        $rp->stripVars();
        
        if ($data['valid_regexp']) {
            if (!preg_match($data['valid_regexp'], $rp->value)) {
                $controller->go();
            }
        }

        $apply_value = '';
        if (isset($rp->dv)) {
            $apply_value = implode(',', $rp->dv);
        } elseif(isset($rp->value)) {
            $apply_value = $rp->value;
        }

        if ($apply_value !== '') {
            $entry_type = $manager->getEntryTypeById($rq->id);
            $manager->applyValue($apply_value, $rq->id, $entry_type, $categories);
        }

        $controller->go();
    }

    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->setCategory($categories);

    $view = $controller->getView($obj, $manager, 'CustomFieldView_apply');

    break;

default: // ------------------------------------
    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $class = 'CustomFieldView_form_' . $entry_type; 
    $controller->loadClass($class);
    $form_view = new $class;
    
    $view = $controller->getView($obj, $manager, 'CustomFieldView_list', $form_view);
}
?>