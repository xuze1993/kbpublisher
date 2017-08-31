<?php

include 'trigger_map_common.php';


$items = array();

$items['status']  = array(
    'r' => 'status',
    'sql' => 'e.active [%s] %d');

$items['category']  = array(
    'r' => 'category',
    'sql' => 'e_to_cat.category_id [%s] %s');
    
$items[]  = '--';

$items['author']  = array(
    'r' => 'author',
    'sql' => 'e.author_id [%s] %s');
    
$items['updater']  = array(
    'r' => 'author',
    'sql' => 'e.updater_id [%s] %s');
    
$items[]  = '--';
$items['date_posted'] = array(
    'r'=>'sel_text2',
    // 'sql' => 'DATE_ADD(e.date_posted, INTERVAL %d [%s]) [%s] NOW()',
    'sql' => 'DATE_SUB(NOW(), INTERVAL %d [%s]) [%s] e.date_posted',
    'order' => '1,2,0'); // arguments order, optional
    
$items['date_updated'] = array(
    'r'=>'sel_text2',
    'sql' => 'DATE_SUB(NOW(), INTERVAL %d [%s]) [%s] e.date_updated',
    'order' => '1,2,0');

   
$rules['status'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('func'   => array('this', 'getStatusSelectRange'))),
    );

$rules['author'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('func'   => array('this', 'getAuthorSelectRange'), 'options' => 'onchange="checkForPopup(this)"')),
    );

$rules['updater'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('select' => array('func'   => array('this', 'getAuthorSelectRange'), 'options' => 'onchange="checkForPopup(this)"')),
    );

$rules['sel_text2'] = array(
    array('select' => array('option' => array('less', 'more'))),
    array('text'   => array('value'  => '3', 'style'  => 'width: 50px;')),
    array('select' => array('option' => array('period_old_days'),'style'  => 'width: auto;'))
    );

// ====================================== //
    
    
$actions = array();
$actions['status'] = array(
    'r' => 'status',
    'func' => 'setStatus',
    'func_params' => array('field' => 'active'));
    
// $actions['type'] = array(
//     'r' => 'type',
//     'func' => 'setType',
//     'func_params' => array('field' => 'entry_type'));
    
$actions[]  = '--';
$actions['email']  = array(
    'r' => 'email',
    'func' => 'emailUser');

$actions['email_user_grouped']  = array(
    'r' => 'email_user_grouped',
    'func' => 'emailUserGrouped');

$actions['email_group']  = array(
    'r' => 'email_group',
    'func' => 'emailGroup');


// for action
$action_rules['status'] = array(
    array('select' => array('func'   => array('this', 'getStatusSelectRange'))),
);

$action_rules['type'] = array(
    array('select' => array('func'   => array('this', 'getTypeSelectRange'))),
);

// get select range from parser::getCategorySelectRange    
// can contain args array to be passed to getCategorySelectRange as arguments
$action_rules['email'] = array(
    array('select'   => array('func' => array('this', 'getEmailRecipientSelectRange'), 'options' => 'onchange="checkForPopup(this)"')),
    array('email_subject' => array('value' => '')),
    array('email_body' => array('value' => ''))
);
    
$action_rules['email_group'] = array(
    array('select'   => array('func' => array('this', 'getGroupEmailRecipientSelectRange'))),
    array('email_subject' => array('value' => '')),
    array('email_body' => array('value' => '', 'style' => 'width: 95%; height: 200px; margin: 10px 0;'))
);
    
$action_rules['email_user_grouped'] = array(
    array('select'   => array('func' => array('this', 'getEmailRecipientSelectRange'), 'options' => 'onchange="checkForPopup(this)"')),
    array('email_subject' => array('value' => '')),
    array('email_body' => array('value' => ''))
);

?>