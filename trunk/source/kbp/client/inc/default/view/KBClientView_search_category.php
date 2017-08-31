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

require_once 'core/base/BaseObj.php';
require_once 'core/base/Controller.php';
require_once 'core/app/AppObj.php';
require_once 'core/app/AppController.php';
require_once 'core/app/AppView.php';
require_once APP_MODULE_DIR . 'knowledgebase/category/inc/KBCategoryModel.php';


class KBClientView_search_category extends KBClientView_common
{
    
    function execute($manager) {
        
        if ($this->type == 'article') {
            require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryView_category.php';
            $view = new KBEntryView_category;
            
        } else {
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryView_category.php';
            $view = new FileEntryView_category;
            
            $manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'files');
        }
        
        $categories = &$manager->categories;
        
        $options = array(
            'sortable' => false,
            'secondary_block' => false,
            'cancel_button' => false,
            'creation' => false,
            'status_icon' => false,
            'mode' => 'search',
            'popup_title' => $this->msg['search_category_msg'],
            //'main_title' => $this->msg['category_msg']
        );
        
        $manager = new KBCategoryModel;
        
        $view->controller = new AppController();
        $view->controller->setWorkingDir();
        
        return $view->parseCategoryPopup($manager, $categories, $options);
    }
    
}
?>