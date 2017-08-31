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

require_once 'eleontev/CalendarUtil.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';
require_once APP_MODULE_DIR . 'report/user/inc/ReportEntryUserView_list.php';
require_once APP_MODULE_DIR . 'report/user/inc/ReportEntryUserModel.php';


class UserView_activity extends AppView 
{
    
    var $template = 'list_activity.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $view = new ReportEntryUserView_list;
        $manager2 = new ReportEntryUserModel;
        $view->left_filter = false;
        $view->user_id = $obj->get('id');

        $tpl = $view->_executeTpl($obj, $manager2, $this->template);
        
        $menu_block = UserView_common::getEntryMenu($obj, $manager, $this);
        $tpl->tplAssign('menu_block', $menu_block);
        
        if (empty($tpl->vars['hint'])) {
             $tpl->tplAssign('header2', 
			 	$this->commonHeaderList('', $this->getFilter($obj, $manager2, $view), false, false));
        }
        
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFilter($obj, $manager2, $view) {
		
        $tpl = $view->_executeFilterTpl($manager2, 'form_filter_activity.html');
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($obj->get());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>