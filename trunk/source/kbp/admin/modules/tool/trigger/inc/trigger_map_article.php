<?php

include 'trigger_map_common.php';


$items = array();

$items['article']  = array(
    'r' => 'article',
    'func' => 'checkArticleCondition'
);
    
$items[]  = '--';
$items['status']  = array(
    'r' => 'status',
    'func' => 'checkSimpleFieldCondition',
    'func_params' => array('field' => 'active')
);

$items['type']  = array(
    'r' => 'type',
    'func' => 'checkSimpleFieldCondition',
    'func_params' => array('field' => 'entry_type')
);

$items['tag']  = array(
    'r' => 'tag',
    'func' => 'checkTag'
);

$items[]  = '--';
$items['author']  = array(
    'r' => 'author',
    'func' => 'checkAuthorCondition');
    
$items['updater']  = array(
    'r' => 'updater',
    'func' => 'checkUpdaterCondition');
    

$rules['article'] = array(
    array('select' => array('option' => array('created', 'updated')))
);

$rules['status'] = array(
    array('select' => array('option' => array('is', 'is_not', 'changed', 'changed_from', 'changed_to', 'not_changed', 'not_changed_from', 'not_changed_to'), 'options' => 'onchange="toggleSelect(this)"')),
    array('select' => array('func'   => array('this', 'getStatusSelectRange'))),
);

$rules['type'] = array(
    array('select' => array('option' => array('is', 'is_not', 'changed', 'changed_from', 'changed_to', 'not_changed', 'not_changed_from', 'not_changed_to'), 'options' => 'onchange="toggleSelect(this)"')),
    array('select' => array('func'   => array('this', 'getTypeSelectRange'))),
);

$rules['tag'] = array(
    array('select' => array('option' => array('contain', 'not_contain'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
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
$actions['status'] = array(
    'r' => 'status',
    'func' => 'setSimpleField',
    'func_params' => array('field' => 'active')
);

$actions['type'] = array(
    'r' => 'type',
    'func' => 'setSimpleField',
    'func_params' => array('field' => 'type')
);

$actions[]  = '--';
$actions['email']  = array(
    'r' => 'email',
    'func' => 'sendEmail'
);


$action_rules['status'] = array(
    array('select' => array('func' => array('this', 'getStatusSelectRange'))),
);

$action_rules['type'] = array(
    array('select' => array('func' => array('this', 'getTypeSelectRange'))),
);

$action_rules['email'] = array(
    array('select'   => array('func' => array('this', 'getEmailRecipientSelectRange'), 'options' => 'onchange="checkForPopup(this)"')),
    array('email_subject' => array('value' => '')),
    array('email_body' => array('value' => ''))
);

?>