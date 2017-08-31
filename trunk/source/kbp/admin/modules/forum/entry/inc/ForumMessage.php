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

class ForumMessage extends AppObj
{

	var $properties = array('id'		 	=> NULL,
							'entry_id'      => 0,
                            'user_id' 		=> 0,
                            'updater_id'    => 0,
                            'date_posted' 	=> '',
                            'date_updated' 	=> '',
                            'message' 		=> '',
                            'message_index' => '',
                            'active' 		=> 1
							);

	var $hidden = array('id', 'entry_id', 'user_id', 'updater_id', 'date_posted', 'date_updated');


	function validate($values) {

		require_once 'eleontev/Validator.php';

		$required = array('message');

		$v = new Validator($values, false);

		// check for required first, return errors
		$v->required('required_msg', $required);
		if($v->getErrors()) {
			$this->errors =& $v->getErrors();
			return true;
		}
	}


	function _callBack($property, $val) {
		if($property == 'date_posted' && !$val) {
			$val = date('Y-m-d H:i:s');

		} if($property == 'date_updated' && !$val) {
			$val = date('Y-m-d H:i:s');

		} elseif($property == 'user_id' && !$val) {
            $val = AuthPriv::getUserId();

        } elseif($property == 'updater_id') {
			$val = AuthPriv::getUserId();
		}

		return $val;
	}

}
?>