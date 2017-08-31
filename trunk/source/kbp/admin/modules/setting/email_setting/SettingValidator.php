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

require_once 'eleontev/Validator.php';


class SettingValidator
{
     
    function validate($values) {
        
        $required = array('from_email', 'noreply_email', 'admin_email');
        
        $v = new Validator($values, true);
        
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        // from/support email
        $from_email = explode(',', $values['from_email']);
        foreach($from_email as $email) {
            $email = trim($email);
            if(!$ret = Validate::email($email)) {
                $v->setError('email_msg', 'from_email', 'email');
                break;
            }
        }
        
        // admin email
        if($values['admin_email']) {
            $from_email = explode(',', $values['admin_email']);
            foreach($from_email as $email) {
                $email = trim($email);
                if(!$ret = Validate::email($email)) {
                    $v->setError('email_msg', 'admin_email', 'email');
                    break;
                }
            }        
        }
        
        // noreplay email
        $email = trim($email);
        if(!$ret = Validate::email(trim($values['noreply_email']))) {
            $v->setError('email_msg', 'noreply_email', 'email');
        }
        
        
        if($v->getErrors()) {
            return $v->getErrors();
        }        
        
		// execute only without ajax
        if(!AppController::isAjaxCall()) {
			$error = $this->testEmail($values);
        	if($error) {
            	$v->setError($error, 'test_email', 'test_email', 'custom');
        	}
        }
		
        return $v->getErrors();
    }
    
    
    // return false is ok string with error otherwise
    function testEmail($values) {
        
        $values['smtp_auth'] = (isset($values['smtp_auth'])) ? $values['smtp_auth'] : 0;
        
        require_once 'core/app/AppMailSender.php';
        $mail = new AppMailSender($values);
        return $mail->testMail();
    }
}
?>