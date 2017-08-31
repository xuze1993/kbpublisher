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

class KBClientAction_popular extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        $controller->loadClass('index');
        $view = &$controller->getView('dynamic');
        $view->dynamic_type = 'popular';        
        return $view;
    }
}
?>