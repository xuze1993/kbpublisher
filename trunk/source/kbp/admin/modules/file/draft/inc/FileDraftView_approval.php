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

include_once APP_MODULE_DIR . '/knowledgebase/draft/inc/KBDraftView_common.php';
include_once APP_MODULE_DIR . '/knowledgebase/draft/inc/KBDraftView_approval.php';


class FileDraftView_approval extends KBDraftView_approval
{
    
    var $template = 'form_approval.html';
    
    
    function getMenuBlock($obj, $manager, $view, $emanager) {
        return FileDraftView_common::getEntryMenu($obj, $manager, $view, $emanager);
    }
    
    
    function getApprovalHistory($obj) {
        $view = new KBDraftView_approval_history;
        $draft_manager = new FileDraftModel;
        $data = $view->execute($obj, $draft_manager);
        return $data;
    }
}
?>