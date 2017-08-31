<?php

include 'trigger_map_common.php';


$items = array();

$items['email']  = array(
    'r' => 'email'
);

$items['auto_email']  = array(
    'r' => 'auto_email'
);

$items[]  = '--';

$items['from']  = array(
    'r' => 'from'
);

$items['to']  = array(
    'r' => 'to'
);

$items['cc']  = array(
    'r' => 'cc'
);

$items['subject']  = array(
    'r' => 'subject'
);

$items[]  = '--';

$items['body']  = array(
    'r' => 'body'
);


$rules['email'] = array(
);

$rules['auto_email'] = array(
);

$rules['from'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
);

$rules['to'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
);

$rules['cc'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
);

$rules['subject'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
);

$rules['body'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
);

// ====================================== //
    
    
$actions = array();

$actions['create_draft'] = array(
    'r' => 'create_draft',
    'func' => 'createDraft');
    
$actions['create_article'] = array(
    'r' => 'create_article',
    'func' => 'createArticle');
    
$actions['create_news'] = array(
    'r' => 'create_news',
    'func' => 'createNews');

$actions[]  = '--';

$actions['stop'] = array(
    'r' => 'stop');
    

// for action
$action_rules['create_draft'] = array(
    array('button' => array()),
    array('checkbox' => array('label_text' => 'send_approval'))
);

$action_rules['create_article'] = array(
    array('button' => array())
);

$action_rules['create_news'] = array(
    array('button' => array())
);

$action_rules['stop'] = array(
);

?>