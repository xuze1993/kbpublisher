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

require_once 'eleontev/Util/TimeUtil.php';
require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';
require_once APP_MODULE_DIR . 'news/inc/NewsEntryView_form.php';
require_once 'EmailParserEntryView_obj_article.php';


//class EmailParserEntryView_obj_news extends NewsEntryView_form
class EmailParserEntryView_obj_news extends AppView
{
    
    var $template = 'form_obj_news.html';
    
    
    function execute(&$obj, &$manager) {
        
        // tag
        $tags = $obj->getTag();
        if (!empty($tags)) {
            $ids = implode(',', $tags);
            $obj->setTag($manager->tag_manager->getTagByIds($ids));
        }
        
        $f = new NewsEntryView_form;
        $tpl = $f->_executeTpl($obj, $manager, $this->template_dir . $this->template);
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl->tplAssign('popup_title', $this->msg['news_option_msg']);
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');
            $tpl->tplAssign('action_num', $_GET['field_id']);
            
            $tpl->tplParse();
            return $tpl->tplPrint(1);
        }
        
        $tpl->tplAssign('msg', EmailParserEntryView_obj_article::getPlaceholderBlock());
        
        $author_id = $obj->get('author_id');
        if ($author_id) {
            $tpl->tplSetNeeded('/author');
            $author = $manager->getUser($author_id);
            
            $tpl->tplAssign('author_id', $author_id);
            $tpl->tplAssign('name', sprintf('%s %s', $author['first_name'], $author['last_name'])); 
        }
        
        $check_sender = @$obj->get('check_sender');
        $tpl->tplAssign('email_sender_checked', ($check_sender) ? 'checked' : '');
        
        // user link
        $more = array('filter[s]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array('item' => $this->controller->getMoreParam('item'));
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormNews'));
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormNews($values, $options = array()) {
        
        $values['date_posted'] = date('Ymd');
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        return $objResponse;
    }

}
?>