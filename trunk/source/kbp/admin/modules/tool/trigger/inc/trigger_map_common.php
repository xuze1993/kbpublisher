<?php

$rules = array();

$rules['sel_text'] = array(
    array('select' => array('option' => array('less', 'more'))),
    array('text'   => array('value'  => '7', 'style'  => 'width: 50px;')),
    array('msg'    => array('value'  => 'period_old_days'))
    );
                        
$rules['datetime'] = array(
    array('select' => array('option' => array('less', 'more'), 'value'  => 'more')),
    array('custom' => array('func'   => array('this', 'getDateTimeSelect'), 'value'  => ''))
    );

$rules['search'] = array(
    array('select' => array('option' => array('contain', 'not_contain', 'start_with', 'end_with', 'equal'))),
    array('text'   => array('value'  => '', 'style'  => 'width: 250px;'))
    );

$rules['id'] = array(
    array('msg'   => array('value' => 'equal')),
    array('text'  => array('value' => '', 'style' => 'width: 50px;'))
    );

$rules['text'] = array(
    array('msg'    => array('value' => '')),
    array('text'   => array('value' => '', 'style' => 'width: 300px;'))
    );

$rules['textarea'] = array(
    array('msg'     => array('value' => '')),
    array('textarea'=> array('value' => '', 'style' => 'width: 100%; height: 100px;'))
    );

$rules['category'] = array(
    array('select' => array('option' => array('is', 'is_not'))),
    array('text_popup' => array('value' => '', 'options'  => 'readonly', 'validate_func' => array('this', 'validateCategory'))),
    array('checkbox' => array('label_text' => 'all_child', 'label_title' => 'all_child_categories')),
    );

?>