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

class KBClientAction_trouble extends KBClientAction_common
{

    function &execute($controller, $manager) {

        if($manager->isUserViewed($this->entry_id) === false) {
            $manager->addView($this->entry_id);
            $manager->setUserViewed($this->entry_id);
        }
        
        $view = &$controller->getView('trouble');        
        return $view;
    }
}
?>