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


class SetupAction
{
    
    function setVars($controller, $manager) {
        
        $this->rq = new RequestData($_GET);
        $this->rp = new RequestData($_POST);
        
        $this->entry_id    = &$controller->entry_id;
        $this->msg_id      = &$controller->msg_id;
    }
}
?>