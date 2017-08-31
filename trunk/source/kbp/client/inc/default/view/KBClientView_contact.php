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


class KBClientView_contact extends KBClientView_common
{

    var $found_entries = array();

    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_contact_us_msg'];
        $this->nav_title = $this->meta_title;
        $this->category_nav_generate = false; // not to generate categories in navigation line
        
        $data = &$this->getForm($manager);
        
        return $data;
    }
    

    function &getForm(&$manager) {

        $tpl = new tplTemplatez($this->getTemplate('contact_form.html'));

        // subject
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->select_tag = false;
        //$select->setSelectWidth(200);
        $select->setSelectName('subject_id');
        
        $range = ListValueModel::getListSelectRange('feedback_subj', true);
        $select->setRange($range);
        
        if(isset($_POST['subject_id'])) {
            $subject = $_POST['subject_id'];
        } else {
            $subject = ListValueModel::getListDefaultEntry('feedback_subj');   
        }
        
        $tpl->tplAssign('subject_select', $select->select($subject));
        
        
        if(APP_DEMO_MODE) {
            $msg = BoxMsg::factory('hint');
            $msg->setMsgs('', 'To see an example of how "Quick Response" works, type "LDAP integration" in the field below.');
            $tpl->tplAssign('demo_responce_msg', $msg->get());
        }        
        
        $tpl->tplAssign('user_msg', $this->getErrors());
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink('index', $this->category_id));
        
        
        // attachments
        $num = $manager->getSetting('contact_attachment');
        for($i=1; $i<=$num; $i++) {
            $a['num'] = $i;
            $a['attachment_msg'] = $this->msg['attachment_msg'];
            $tpl->tplParse($a, 'attachment');
        }
        
        $allowed = $manager->getSetting('contact_attachment_ext');
        if($allowed && $num) {
            $tpl->tplAssign('allowed_extension', $allowed);
            $tpl->tplSetNeeded('/allowed_extension');
        }
        
        
        if($manager->getSetting('contact_quick_responce')) {
            //xajax
            $extra_js = array();    
            $str = '<script src="%s/OnKeyRequestBuffer.js" type="text/javascript"></script>';
            $extra_js[] = sprintf($str, $this->controller->kb_path . 'client/jscript/xajax_js');

            $str = '<script src="%s/spiner.js" type="text/javascript"></script>';
            $extra_js[] = sprintf($str, $this->controller->kb_path . 'client/jscript/xajax_js');

            $ajax = &$this->getAjax('search');
            $xajax = &$ajax->getAjax($manager);
            $xajax->extra_js = implode("\n", $extra_js);
            $xajax->registerFunction(array('requestBuffer', $ajax, 'ajaxGetQuickResponce'));
            
            $tpl->tplAssign('onkeyup_action', 'OnKeyRequestBuffer.modified(this.id);');
            
        } else {
            $tpl->tplAssign('onkeyup_action', 'return false;');
        }    
    
    
        // custom 
        $crows = $manager->cf_manager->getCustomField();
        $cvalues = $this->getFormData('custom');
        
        $options = array(
            'use_default' => (empty($_POST))
        );
        
        if($this->mobile_view) {
            $options['radio_delim'] = '';
            $options['ch_wrap'] = '<div class="checkbox checkbox-primary">%s</div>';
            $options['ch_group_wrap'] = '<div class="checkbox checkbox-primary">%s</div>';
            $options['radio_wrap'] = '<div class="radio radio-primary">%s</div>';
             
        } else {
            $options['style_select'] = 'width: 250px;';
            $options['style_text'] = 'width: 500px;';
            $options['style_textarea'] = 'width: 500px;';            
        }
        
        $inputs = CommonCustomFieldView::getCustomFields($crows, $cvalues, $manager->cf_manager, $options);
        $fd = $this->getFormData();
                       
        foreach($crows as $id => $field) {
            $field['id'] = $id;
            $field['input'] = $inputs[$id];
            
            $field['required_sign'] = '';
            if ($field['is_required']) {
                $field['required_sign'] = $fd['required_sign'];
            }
            
            if ($field['tooltip']) {
                $tpl->tplSetNeeded('custom_row/tooltip');
            }
            
            $field['tooltip'] = ($this->mobile_view) ? nl2br($field['tooltip']) : htmlentities(nl2br($field['tooltip']));
             
            $tpl->tplParse($field, 'custom_row');
        }
        
        
        if(!$manager->is_registered) {
            $tpl->tplSetNeeded('/not_registered');
        }
        
        if($this->useCaptcha($manager, 'contact')) {
            $tpl->tplSetNeeded('/captcha');
            $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
        }
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->convertRequiredMsg(array('captcha_msg')));
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        require_once 'core/app/AppAjax.php';
        require_once 'eleontev/Validator.php';
        
        $v = new Validator($values, false);
        
        // need to check if not registered
        if(!$manager->is_registered) {
            $v->required('required_msg', array('title', 'message', 'email'));
            $v->regex('email_msg', 'email', 'email');
        } else {
            $v->required('required_msg', array('title', 'message'));
        }
        
        // custom
        $fields = $manager->cf_manager->getCustomField();
        $error = $manager->cf_manager->validate($fields, $values);
        if($error) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
            return $v->getErrors();
        }        
        
        if($this->useCaptcha($manager, 'contact', true)) {
            $unset = !AppAjax::isAjaxRequest();
            if(!$this->isCaptchaValid($values['captcha'], false, $unset)) {
                $v->setError('captcha_text_msg', 'captcha', 'captcha');
            }
        }
        
        return $v->getErrors();
    }
}
?>