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

class SettingParserModel extends SettingParserModelCommon
{        

    function getPrivSelectRange() {
        require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
        $m = new UserModel;
        return $m->getPrivSelectRange(false);
    }
    
    
    function getRoleSelectRange() {
        require_once APP_MODULE_DIR . 'user/role/inc/RoleModel.php';
        $m = new RoleModel;
        return $m->getSelectRange();
    }
}
?>