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

// file and ffile actions differ


class KBClientAction_download extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        // if not allowed
        if(!$manager->getSetting('module_file')) {
            $controller->go();
        }
        
        $view = &$controller->getView('download');
        return $view;
    }    
}
?>