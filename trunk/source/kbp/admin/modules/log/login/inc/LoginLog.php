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

class LoginLog extends AppObj
{
    
    var $properties = array('user_id'    => '',
                            'date_login'=> '',
                            'user_ip'    => '',
                            'output'    => '',
                            'login_type'=> '',
                            'exitcode'    => 1
                            );
    
    
}
?>