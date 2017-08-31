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


class TriggerEntry extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'entry_type'     => 1, // articles
                            'trigger_type'   => 0,
                            'user_id'        => 0,
                            'trigger_key'    => '',
                            'title'          => '',
                            'options'        => '',
                            'cond_match'     => 2,
                            'cond'           => '',
                            'action'         => '',
                            'schedule'       => '',
                            'active'         => 1
                            );
    
    
    var $hidden = array('id', 'entry_type', 'trigger_type', 'user_id', 'trigger_key');
    var $reset_on_clone = array('id', 'title', 'user_id', 'trigger_key', 'active');
    
    var $condition = array();
    var $action = array();
    

    function _callBack($property, $val) {
        if($property == 'user_id' && !$val) {
            $val = AuthPriv::getUserId();
        
        } elseif($property == 'active' && $val === NULL) { // for reset_on_clone
            $val = 1;
        }
        
        return $val;
    }    
    
    
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        $required = array('title', 'cond', 'action');
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        
        // automations
        if ($values['trigger_type'] == 2) {
            
            $dates = $this->validateDates($values);
            if (!is_array($dates)) {
                $v->setError('wrong_day_number_msg', 'more_condition_' . $dates);
                
            } else {
                $error_field = $this->validateDateRange($values['cond_match'], $dates);
                if ($error_field !== true) {
                    
                    foreach ($values['cond'] as $num => $v1) {
                        if ($v1['item'] == $error_field) {
                            $date_field_ids[] = 'more_condition_' . $num;
                        }
                    }
                    
                    $v->setError('wrong_date_range_msg', $date_field_ids, 'date');
                    
                } else {
                    $errors = $this->validateRequiredValues($values);
                    
                    if (!empty($errors)) {
                        list($error_key, $num) = $errors;
                        $v->setError($error_key, 'more_condition_' . $num);
                    
                    } else {
                        $nullified_by_date = $this->isNullifiedByDate($values['cond_match'], $dates);
                        if (!$nullified_by_date) {
                            $nullified = $this->isNulified($values);
                            if (!$nullified) {
                                $v->setError('nullify_automation_msg', 'cond');
                            }
                        }
                    }
                }
            }
        }
        
        if($v->getErrors()) {
			$this->errors =& $v->getErrors();
			return true;
		}
    }
    
    
    function isNulified($values) {
        
        $nulified = false;
        $nullifying_actions = array('status');
        
        // collect submited cond/action
        $cond = array();
        $action = array();
        foreach ($values['cond'] as $v2) {
            if(in_array($v2['item'], $nullifying_actions)) {
                $sign[$v2['item']] = ($v2['rule'][0]);
                $cond[$v2['item']] = $v2['rule'][1];
            }
        }        
        
        foreach ($values['action'] as $v2) {
            if(in_array($v2['item'], $nullifying_actions)) {
                $action[$v2['item']] = $v2['rule'][0];
            }
        }
        
        // collect what to compare
        $cond_ = array();
        $action_ = array();
        foreach($nullifying_actions as $v2) {
            if(isset($cond[$v2]) && isset($action[$v2])) {
                $cond_[$v2] = $cond[$v2];
                $action_[$v2] = $action[$v2];
            }
        }
         
        // compare
        if($cond_ && $action_) {
            $nulified =  $this->validateNulified($cond_, $action_, $sign[$v2]);
        }
        
        return $nulified;
    }
    
    
    function validateNulified($a, $b, $sign) {
        
        if($sign == 'is') {
            return ($a != $b);
        } else {
            return ($a == $b);
        }
    }
    
    
    function validateDates($values) {
        $dates = array();
        foreach ($values['cond'] as $k => $v2) {
            
            if (in_array($v2['item'], array('date_posted', 'date_updated'))) {
                $rule = $v2['rule'];
                $range_value = $rule[1];
                
                if (!ctype_digit($range_value) || $range_value == '0') {
                    return $k;
                }
                
                if (!isset($dates[$v2['item']][$rule[0]])) {
                    $dates[$v2['item']][$rule[0]] = array();
                }
                
                $dates[$v2['item']][$rule[0]][] = $rule[1];
            }
        }
        
        return $dates;
    }
    
    
    function validateRequiredValues($values) {
        foreach ($values['cond'] as $k => $v2) {
            if ($v2['item'] == 'category' && !$v2['rule'][1]) {
                return array('wrong_category_msg', $k);
            }
            
            if ($v2['item'] == 'status' && $v2['rule'][1] == 'none') {
                return array('wrong_status_msg', $k);
            }
        }
    }
    
    
    function validateDateRange($cond_match, $dates) {
        if ($cond_match == 2) {
            $range = $this->getDateRange($dates);
            foreach ($range as $date_field => $diff) {
                if ($diff <= 0) {
                    return $date_field;
                }
            }
        }
        
        
        return true;
    }
    
    
    function isNullifiedByDate($cond_match, $dates) {
        if ($cond_match == 2) {
            $range = $this->getDateRange($dates);
            foreach ($range as $date_field => $diff) {
                if ($diff == 1) {
                    return true;
                }
            }
            
        } else {
            foreach ($dates as $date_field => $v) {
                if (!empty($v['more'])) {
                    return false;
                }
                
                if (max($v['less']) > 1) {
                    return false;
                } 
            }
            
            return true;
        }
    }
    
    
    function getDateRange($dates) {
        $range = array();
        foreach ($dates as $date_field => $v) {
            if (!empty($v['more'])) {
                $more = max($v['more']);
                
            } else {
                $more = 0;
            }
            
            if (!empty($v['less'])) {
                $less = min($v['less']);
                
            } else {
                $less = $more + 2;
            }
            
            $range[$date_field] = $less - $more;
        }
        
        return $range;
    }
}
?>