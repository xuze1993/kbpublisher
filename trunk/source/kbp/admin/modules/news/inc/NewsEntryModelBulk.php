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

require_once 'core/app/BulkModel.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModelBulk.php';


class NewsEntryModelBulk extends KBEntryModelBulk
{

    var $actions = array('tag', 'private', 'public', 'schedule', 'custom', 'status', 'delete');

    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }

}
?>