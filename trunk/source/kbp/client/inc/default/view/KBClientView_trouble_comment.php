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

require_once APP_MODULE_DIR . 'knowledgebase/comment/inc/KBCommentView_helper.php';


class KBClientView_trouble_comment extends KBClientView_common
{
    
    function getForm($manager, $data, $parent_id, $entry_page = false) {
        
        $tpl = new tplTemplatez($this->template_dir . 'trouble_comment_form.html');
        
        $action = ($this->msg_id == 'comment_update') ? 'comment_update' : 'comment_post';
        $more = ($this->msg_id == 'comment_update') ? array('id'=> (int) $_GET['id']) : array();
        $link = $this->getLink('trouble', $this->category_id, $this->entry_id, $action, $more);
        $tpl->tplAssign('action_link', $link);        
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        if(!$manager->is_registered) {
            $tpl->tplSetNeeded('/not_registered');
        }
        
        if($action == 'comment_post') {
            if($this->useCaptcha($manager, 'comment')) {
                $tpl->tplSetNeeded('/captcha');
                $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
            }
        }
        
        if($entry_page == 'entry') {
            $tpl->tplAssign('toggle_form', 1);
            $tpl->tplAssign('display_form', 'none');
        
        } elseif($entry_page == 'comment') {
            $tpl->tplAssign('toggle_form', 0);
            $tpl->tplAssign('display_form', 'block');
        
        } else {
            $tpl->tplAssign('toggle_form', 0);
            $tpl->tplAssign('display_form', 'block');
            
            $entry_id = $this->controller->getEntryLinkParams($data['id'], $data['title'], $data['url_title']);
            $tpl->tplAssign('cancel_link', $this->getLink('entry', $this->category_id, $entry_id));
            $tpl->tplSetNeeded('/cancel_btn');    
        }

        // not allowed for not registered    
        if(!$manager->is_registered && $manager->getSetting('allow_comments') == 2) {
            $tpl->tplAssign('login_link', $this->getLink('comment', $this->category_id, $this->entry_id, 'post'));
            $tpl->tplAssign('display_form', 'none');
            $tpl->tplSetNeeded('/add_login');
        } else {
            $tpl->tplSetNeeded('/add_js');
        }

        // bbcode help
        $msg['body'] = AppMsg::getMsgMutliIni('text_msg.ini', 'public', 'bbcode_help');
        $msg['body'] = str_replace(array('((', '))'), array('<b>[', ']</b>'), $msg['body']);
        $msg = BoxMsg::factory('hint', $msg);
        $tpl->tplAssign('bbcode_help_block', $msg);
        
        
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign('comment', ''); 
        $tpl->tplAssign('comment_title', $this->msg['add_comment_msg']);
        $tpl->tplAssign('step_id', $parent_id); 
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getList(&$manager, $step_id, $limit = false, $offset = false) {
        
        $rows = $this->stripVars($manager->getCommentList($step_id, $limit, $offset));
        if(!$rows) {
            return;
        }
                        
        
        $tpl = new tplTemplatez($this->template_dir . 'trouble_step_comment_list.html');
        
        // actions
        $action_allowed = AuthPriv::getPrivAllowed('trouble_comment');
        $action_allowed = array_intersect(array('update', 'delete'), $action_allowed);
        foreach ($action_allowed as $v) {
            $tpl->tplSetNeededGlobal($v);
        }
        
        
          $r = $this->_getReplacerObj();
        $comments_author_str = $manager->getSetting('comments_author_format');  

        foreach($rows as $k => $row) {
            
            // registered user
            if($row['user_id']) {
                @$row['short_first_name'] = _substr($row['first_name'], 0, 1);    
                @$row['short_last_name'] = _substr($row['last_name'], 0, 1);
                @$row['short_middle_name'] = _substr($row['middle_name'], 0, 1); 
                $row['comment_name'] = $r->parse($comments_author_str, $row);
            
            // not registered, empty comment_name
            } elseif(!$row['comment_name']) {
                $row['comment_name'] = $this->msg['anonymous_user_msg'];
            }
                                
            if($action_allowed) {
                
                $more = array('id'=>$row['id']);
                $link = $this->getLink('trouble', $this->category_id, $this->entry_id, 'comment_update', $more);
                $row['update_link'] = $link;
                
                $link = $this->getLink('trouble', $this->category_id, $this->entry_id, 'comment_delete', $more);
                $row['delete_link'] = $link;
                
                $tpl->tplSetNeeded('row/action');
            }
        
            $parser = KBCommentView_helper::getBBCodeObj();
            $row['comment'] = nl2br($parser->qparse($row['comment']));

            $row['formated_date'] = $this->getFormatedDate($row['ts'], 'datetime');
            $row['interval_date'] = $this->getTimeInterval($row['ts'], true);
            $row['anchor'] = 'c' . $row['id'];
            
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }

        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getCommentBlock($manager, $obj) {
    
        $tpl = new tplTemplatez($this->template_dir . 'trouble_step_comment_list.html');
                
          $r = $this->_getReplacerObj();
        $comments_author_str = $manager->getSetting('comments_author_format');
        
        
        // registered user
        if($obj->get('user_id')) {
            $user = $manager->getUserInfo($obj->get('user_id'));

            @$row['short_first_name'] = _substr($user['first_name'], 0, 1);    
            @$row['short_last_name'] = _substr($user['last_name'], 0, 1);
            @$row['short_middle_name'] = _substr($user['middle_name'], 0, 1); 
            $row['comment_name'] = $r->parse($comments_author_str, $user);
            
        // not registered, empty comment_name
        } elseif(!$obj->get('name')) {
            $row['comment_name'] = $this->msg['anonymous_user_msg'];
        } else {
            $row['comment_name'] = $obj->get('name');
        }
        
        $parser = KBCommentView_helper::getBBCodeObj();
        $obj->set('comment', nl2br($parser->qparse($obj->get('comment'))));
        
        $row['formated_date'] = $this->getFormatedDate(time(), 'datetime');
        $row['interval_date'] = $this->getTimeInterval(time(), true);

        
        // actions
        $action_allowed = AuthPriv::getPrivAllowed('trouble_comment');
        $action_allowed = array_intersect(array('update', 'delete'), $action_allowed);
        if ($action_allowed) {
            $tpl->tplSetNeeded('row/action');
        }
        foreach ($action_allowed as $v) {
            $tpl->tplSetNeededGlobal($v);
        }
                                                       
        $tpl->tplParse(array_merge($obj->get(), $row, $this->msg), 'row'); 
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        
        $v = new Validator($values, false);
        $v->required('required_msg', array('comment'));
        
        if(isset($values['email'])) {
            $v->regex('email_msg', 'email', 'email', false);
        }
        
        $action = ($this->msg_id == 'comment_update') ? 'comment_update' : 'comment_post';
        if($action == 'post') {        
            if($this->useCaptcha($manager, 'comment', true)) {
                if(!$this->isCaptchaValid($values['captcha'])) {
                    $v->setError('captcha_text_msg', 'captcha', 'captcha');
                }
            }
        }

        return $v->getErrors();
    }


    function _getReplacerObj() {
        $r = new Replacer();
        $r->s_var_tag = '[';
        $r->e_var_tag = ']';
        $r->strip_var_sign = '--';
        
        return $r;
    }
    
}
?>