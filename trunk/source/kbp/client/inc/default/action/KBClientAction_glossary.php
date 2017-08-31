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

class KBClientAction_glossary extends KBClientAction_common
{

    function &execute($controller, $manager) {

        if(!$manager->getSetting('module_glossary')) {
            $controller->go();
        }
        
        //require_once $dir . 'KBClientView_glossary.php';
        //$view = new KBClientView_glossary($controller);
        $view = &$controller->getView();
        
        return $view;
    }
}
?>