<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

class Validator
{

    var $msg = "Error(s) occur!";
    var $errors = array();
    var $values = array();
    var $is_error = false;
    var $display_all = true;

    var $js;
    var $use_jscript = false;



    function __construct(&$values, $display_all = true) {
        $this->setValues($values);
        $this->setDisplayMethod($display_all);

        $this->js = new ValidatorJscript();
    }


    function regex($msg, $rule, $val_key, $required = true) {
        if(!Validate::regex($rule, $this->values[$val_key], $required)) {
            $this->setError($msg, $val_key, $rule);
        }

        $this->js->regex($msg, $rule, $val_key, $required);
    }


    function between($msg, $val_key, $min, $max = false, $required = true) {
        $val = (float) $this->values[$val_key];
        if(!Validate::between($this->values[$val_key], $min, $max, $required)) {
            $this->setError($msg, $val_key, 'inRange');
        }

        $this->js->between($msg, $val_key, $min, $max, $required);
    }


    function length($msg, $val_key, $min, $max = false, $required = true) {
        $val = strlen($this->values[$val_key]);
        if(!Validate::between($val, $min, $max, $required)) {
            $this->setError($msg, $val_key, 'inRange');
        }

        $this->js->between($msg, $val_key, $min, $max, $required);
    }


    function compare($msg, $key_check_val, $with_val, $operator = '==') {
        if(!Validate::compare($this->values[$key_check_val], $this->values[$with_val], $operator)) {
            $this->setError($msg, $key_check_val, 'compare');
        }

        $this->js->compare($msg, $key_check_val, $with_val, $operator);
    }


    function required($msg, $val_key) {

        if(!is_array($val_key))  { $val_key = array($val_key); }

        $missed = array();
        foreach($val_key as $k => $v) {
            if(!Validate::required(@$this->values[$v])) {
                $missed[] = $v;
            }
        }

        if($missed) {
            $this->setError($msg, $missed, 'required');
        }

        $this->js->required($msg, $missed);
    }


    function writeable($msg, $val_key) {
        if(!Validate::writeable($this->values[$val_key])) {
            $this->setError($msg, $val_key, 'writeable');
        }
    }


    function setError($msg, $field, $rule = false, $type = 'key') {

        // not to display all errors
        if($this->display_all != true && $this->is_error) { return; }

        $types = array('key', 'custom', 'formatted', 'parsed');
        if(!in_array($type, $types)) {
            trigger_error("Validator::setError - Wrong error type: <b>" . $type . "</b>");
        }

        $this->is_error = true;
        $this->errors[$type][] = array('msg'  => $msg,
                                       'field'=> $field,
                                       'rule' => $rule
                                        );
    }


    function setDisplayMethod($method) {
        $this->display_all = $method;
    }


    function setValues(&$values) {
        $this->values =& $values;
    }


    function &getErrors() {
        return $this->errors;
    }


    function getJscript() {
        return $this->js->getScript();
    }


    function getHtml() {

        $ret[] = sprintf("<b>%s</b><ul>", $this->msg);
        foreach($this->errors as $type => $error) {
            foreach($error as $k => $v) {
                $ret[] = sprintf("<li>%s</li>", $v['msg']);
            }
        }
        $ret[] = "</ul>";

        return implode("\n", $ret);
    }


    function display() {
        echo $this->getHtml();
    }


    /*
    // probably in PHP 5 we can run it
    function __call($method, $args){

        //echo "<pre>"; print_r(get_class_methods($this)); echo "</pre>";
        //echo "<pre>"; print_r(get_object_vars($this)); echo "</pre>";

        echo "<pre>"; print_r($method); echo "</pre>";
        echo "<pre>"; print_r($args); echo "</pre>";


        //if(!Validate::regex($method, $this->values[$args[1]], $args[2])) {
        //    $this->setError($args[0], $args[1], $method);
        //}


        //$this->regex($args[0], $method, $args[1], $args[2]);
    }
    */
}





class Validate
{

    function __construct() {

    }

    // return true if email correct
    static function email($email, $required = true){
        return Validate::regex('email', $email, $required);
    }

    static function getRegexValues() {
        $regex = array('email'         => '/^([a-zA-Z0-9_\-\.\']+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/',
                       'lettersonly'   => '/^[a-zA-Z]+$/',
                       'alphanumeric'  => '/^[a-zA-Z0-9]+$/',
                       'numeric'       => '/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/',
                       'nopunctuation' => '/^[^().\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$/',
                       'nonzero'       => '/^-?[1-9][0-9]*/',
                       'ip'            => '/^(25[0-5]|2[0-4][0-9]|1[0-9]{2}|0?[1-9][0-9]|0?0?[1-9])([.](25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{1,2})){3}$/'
                       );
        return $regex;
    }


    static function getRegex($rule) {
        $regex = Validate::getRegexValues();

        if(!isset($regex[$rule])) {
            trigger_error(ucfirst(__CLASS__) ."::regex  Unspecified rule: {$rule}");
            return false;
        } else {
            return $regex[$rule];
        }
    }


    // return true if ok
    static function regex($rule, $val, $required = true) {

        $val = trim($val);
        $regex = Validate::getRegex($rule);
        if(!$regex) { return false; }

        if(!$required && empty($val)) {
            return true;
        } else {
            return (bool) (preg_match($regex, $val));
        }
    }


    // return true if ok
     static function between($val, $min, $max = false, $required = true) {

        if(!$required && empty($val)) {
            return true;
        } else {
            if($max && $max) { return (bool) ($val >= $min && $val <= $max); }
            elseif($min)     { return (bool) ($val >= $min); }
            elseif($max)     { return (bool) ($val <= $max); }
            else             { return false; }
        }
    }


    // return true if ok
    static function required($value) {
        $value = (is_array($value)) ? $value : trim($value);
        return (bool) (!empty($value));
    }


    // return true if ok
    static function compare($check_val, $with_val, $operator = '==') {

        if ('==' != $operator && '!=' != $operator) {
            $compareFn = create_function('$a, $b', 'return floatval($a) ' . $operator . ' floatval($b);');
        } else {
            $compareFn = create_function('$a, $b', 'return $a ' . $operator . ' $b;');
        }

        return (bool) $compareFn($check_val, $with_val);
    }


    // return true if ok
    static function writeable($check_val) {
        $ret = true;

        if(!file_exists($check_val) || !is_writeable($check_val)) {
            $ret = false;
        }

        return $ret;
    }
}



class ValidatorJscript
{

    var $script_box;
    var $script = array();
    var $use_script_box = true;


    function __construct() {
        $this->script_box = GetJscript::getScriptBox();
    }


    function regex($msg, $rule, $val_key, $required = true) {

        @$reg = GetJscript::getRegex($rule);

        if($rule == 'email') {
            $this->email($msg, $val_key, $required);

        } elseif(!$reg) {
            return;

        } else {
            $html[] = GetJscript::getElement('f', $val_key);
            $html[] = 're = new RegExp("' . $reg . '");';
            $html[] = 'r = re.test(f.value);';
            $html[] = ($required) ? 'if(!r)' : 'if(f.value && !r)';
            $html[] = GetJscript::getErrorRoutine('f', $msg);

            $this->setScript($html);
        }
    }


    function required($msg, $val_key) {

        if(!is_array($val_key))  { $val_key = array($val_key); }

        $html = array();
        foreach($val_key as $k => $v) {
            $html[] = GetJscript::getElement('f', $v);
            $html[] = 'if(isBlank(f.value))';
            $html[] = GetJscript::getErrorRoutine('f', $msg);
        }

        $this->setScript($html);
    }


    function email($msg, $val_key, $required = true) {

        $html[] = GetJscript::getElement('f', $val_key);
        $html[] = ($required) ? 'if(!isValidEmailStrict(f.value))'
                              : 'if(f.value && !isValidEmailStrict(f.value))';
        $html[] = GetJscript::getErrorRoutine('f', $msg);

        $this->setScript($html);
    }


    function between($msg, $val_key, $min, $max = false, $required = true) {

        $html[] = GetJscript::getElement('f', $val_key);

        if($required) {
            $str = sprintf("if(!isBetween(f.value, '%s', '%s'))", $min, $max);
        } else {
            $str = sprintf("if(f.value && !isBetween(f.value, '%s', '%s'))", $min, $max);
        }

        $html[] = $str;
        $html[] = GetJscript::getErrorRoutine('f', $msg);

        $this->setScript($html);
    }


    function compare($msg, $key_check_val, $with_val, $operator = '==') {

         $html[] = GetJscript::getElement('f', $key_check_val);
         $html[] = GetJscript::getElement('f1', $with_val);

        if ('==' != $operator && '!=' != $operator) {

            $html[] = 'checked_value = parseFloat(f.value);';
            $html[] = 'checker_value = parseFloat(f1.value);';

        } else {

            $html[] = 'checked_value = f.value;';
            $html[] = 'checker_value = f1.value;';
        }

        $html[] = sprintf('r = (checked_value %s checker_value);', $operator);
        $html[] = 'if(r == false)';
        $html[] = GetJscript::getErrorRoutine('f1', $msg);

        $this->setScript($html);
    }


    function setScript($array) {
        $this->script[] = (is_array($array)) ? implode("\n", $array) : $array;
    }


    function getScript() {

        $script = implode("\n", $this->script);

        if($this->use_script_box) {
            $script = str_replace('{script}', $script, $this->script_box);
        }

        return $script;
    }
}


class GetJscript
{

    static function getScriptBox() {
        $html =
        '<script language="javascript" type="text/javascript">
        <!--
            function __construct() {

                {script}

                return true;
            }
        //-->
        </script>';

        return $html;
    }


    static function getElement($var, $val) {
        $html[] = sprintf("%s = document.getElementById('%s');", $var, $val);
        $html[] = sprintf("if(!%s) { %s = form.%s };", $var, $var, $val);
        return implode("\n", $html);
    }


    static function getErrorRoutine($field, $msg) {
        $html = "
        {
            alert('%s');
            %s.focus();
            %s.select();
            return false;
        }";

        return sprintf($html, $msg, $field, $field);
    }


    static function getRegex($rule) {
        $regex = array('email'         => '^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$',
                       'lettersonly'   => '^[a-zA-Z]+$',
                       'alphanumeric'  => '^[a-zA-Z0-9]+$',

                       // does not work
                       //'numeric'       => '(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)',

                       // does not work - invaliid quantifier {
                       //'nopunctuation' => '^[^().\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$', // does not work

                       'nonzero'       => '^-?[1-9][0-9]*'
                       );

        return $regex[$rule];
    }


    static function getHighlightFunction() {
        $str =
        "function () {

        }

        ";
    }
}



//$js = new ValidatorJscript();
//$js->required('321321', 'first_name');
//echo "<pre>"; print_r($js->getScript()); echo "</pre>";
//echo "<pre>"; print_r($js); echo "</pre>";



// EXAMPLE

/*
$vars['first_name'] = 1;
$vars['last_name'] = 0;
$vars['email'] = 'info@ezactive';
$vars['password_1'] = 'wqewr';
$vars['password_2'] = 'dadssf';


$v = new Validator($vars);
$v->required('Required_msg', 'first_name');
$v->required('required2Array', array('first_name', 'last_name'));
//$v->regex('Email_msg', 'email', 'email');

//$v->compare('Compare_msg', 'password_1', 'password_2');
//$v->length('between_msg', 'password_1', 6);

$v->setError('custom_msg', 'password_1', 'custom');
$v->setError('custom_msg', 'password_2', 'custom', 'custom');
$v->setError('custom_msg', 'password_2', 'custom', 'formatted');


$v->display();
echo "<pre>"; print_r($v->getErrors()); echo "</pre>";
//echo "<pre>"; print_r($v->getScript()); echo "</pre>";
*/
?>