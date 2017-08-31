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

require_once 'core/common/CommonEntryView.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntry.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';


class ForumFeaturedEntryView_form extends AppView 
{
    
    var $template = 'form.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('title', $obj->getTitle());
        
        $date = (strtotime($obj->get('date_to'))) ? $this->getFormatedDate($obj->get('date_to')) : '';
       
        // datepicker
        $timestamp = time();
        if(strtotime($obj->get('date_to'))) {
            $set_date = true;
            
            if(isset($_POST['date_to'])) { // error
                $timestamp = strtotime($obj->get('date_posted'));
                
            } else { // update
                $timestamp = DatePicker::toUnixDate($obj->get('date_to'));
                $expired = (time() - $timestamp) > 0;
                
                if ($expired) {
                    $tpl->tplSetNeeded('/expired');
                    $tpl->tplAssign('date_formatted', $date);
                    
                    $set_date = false;
                }
            }
            
            if ($set_date) {
                $tpl->tplSetNeeded('/date_set');
            }
        }
       
        $tpl->tplAssign($this->setDatepickerVars($timestamp));
        $tpl->tplAssign('min_date', date('m/d/Y'));
        
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        if($this->controller->getMoreParam('popup')) {
            $param = array('popup' => 3);
            $tpl->tplAssign('cancel_link', $this->controller->getLink('knowledgebase', 'kb_entry', false, false, $param));
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>