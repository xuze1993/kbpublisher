<?php

$items = array();

$items['draft']  = array(
    'r' => 'draft',
    'func' => 'checkArticleCondition'
);
$items['draft_source']  = array(
    'r' => 'draft_source',
    'func' => 'checkArticleCondition' // need update 
);
    
$items[]  = '--';
$items['privilege_level']  = array(
    'r' => 'privilege_level',
    'func' => 'checkUpdaterCondition');

// $items[]  = '--';
$items['author']  = array(
    'r' => 'author',
    'func' => 'checkAuthorCondition');
    
// $items['updater']  = array(
//     'r' => 'updater',
//     'func' => 'checkUpdaterCondition');
    

$rules['draft'] = array(
    array('select' => array('option' => array('is'))),
    array('select' => array('option' => array('published')))
);

$rules['draft_source'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('option' => array('web', 'api', 'dir_rule', 'email')))
);

$rules['privilege_level'] = array(
    array('select' => array('option' => array('less', 'more', 'equal'))),
    array('select' => array('func'   => array('this', 'getPrivilegeSelectRange'), 'value'  => 4))
);

$rules['author'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('func'   => array('this', 'getAuthorSelectRange'), 'options' => 'onchange="checkForPopup(this)"'))
);

$rules['updater'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('func'   => array('this', 'getUpdaterSelectRange'), 'options' => 'onchange="checkForPopup(this)"'))
);


$actions = array();
$actions['assign'] = array(
    'r' => 'assign',
    'func' => 'setSimpleField',
    'func_params' => array('field' => 'type')
);

$action_rules['assign'] = array(
    array('select' => array('func' => array('this', 'getApproverSelectRange'), 'options' => 'onchange="checkForPopup(this)"'))
);

?>