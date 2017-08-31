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

require_once APP_CLIENT_DIR . 'client/inc/DocumentParser.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientLoader.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientController.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientBaseModel.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientModel.php';
require_once APP_CLIENT_DIR . 'client/inc/KBClientView.php';


class NewsEntryView_preview extends AppView
{
    
    var $template = 'preview.html';
    
    function execute(&$obj, &$manager) {
        
        $this->template_dir = APP_MODULE_DIR . 'news/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);                
        
        if($this->controller->getMoreParam('popup') == 1) {

            $tpl->tplSetNeeded('/ajax_preview');

            //xajax
            $ajax = &$this->getAjax($obj, $manager);
            $xajax = &$ajax->getAjax();
            
            $more = array('popup' => 1);
            $link = $this->controller->getAjaxLink('this', 'this', 'this', 'preview', $more);
            $xajax->setRequestURI($link);
            
            $xajax->registerFunction(array('parseBody', $this, 'ajaxParseBody'));
            
            
        // saved or autosaved
        } elseif($this->controller->getMoreParam('id') || $this->controller->getMoreParam('dkey')) {
            
            $tpl->tplAssign('title', $obj->get('title'));
            $tpl->tplAssign('body', $this->parseBody($obj->get('body'), $obj->getCustom()));
            
            // detail button
            $detail_button = ($this->controller->getMoreParam('detail_btn'));
            if($detail_button) {
                $tpl->tplSetNeeded('/detail');
                
                $more = array('id' => $obj->get('id'));
                $link = $this->getLink('this', 'this', false, 'detail', $more);
                $tpl->tplAssign('detail_link', $link);
            }
        }
        
        
        $client_path = $this->conf['client_path'];
        if($this->conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', $client_path);
        }
        $tpl->tplAssign('kb_path', $client_path);
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
    function ajaxParseBody($body, $custom) {
    
        $body = $this->parseBody($body, $custom);
    
        $objResponse = new xajaxResponse();
        
        // $objResponse->alert(print_r($custom, 1));
        $objResponse->assign('news_body', 'innerHTML' , $body);
        $objResponse->call('initUserTooltip');
    
        return $objResponse;    
    }    
    

    function parseBody($body, $custom) {
        
        DocumentParser::parseCurlyBraces($body);

        if($custom) {
            
            $setting = KBClientModel::getSettings(2);
            $setting['auth_check_ip'] = false;
            $setting['view_style'] = 'default';
            $setting['view_format'] = 'default';

            $controller = new KBClientController();
            $controller->setDirVars($setting);
            $controller->setModRewrite(false);            
            
            $reg = &Registry::instance();
            $reg->setEntry('controller', $controller);
            $view = new KBClientView();
            
            $data = array();
            foreach($custom as $field_id => $v) {
                $d = (is_array($v)) ? implode(',', $v) : $v;
                if($d != '' && $d != 'null') {
                    $data[$field_id] = $d;
                }
            }
            
            if($data) {
                $ids = implode(',', array_keys($data));

                $cf_manager = new CommonCustomFieldModel();
                $fields = $cf_manager->getCustomFieldByIds($ids);    
                foreach(array_keys($fields) as $field_id) {
                    $fields[$field_id]['data'] = $data[$field_id];
                }
            
                $custom_data = $view->getCustomData($fields);
                $custom_tmpl_top = $view->parseCustomData($custom_data[1], 1);
                $custom_tmpl_bottom = $view->parseCustomData($custom_data[2], 2);
                $body = $custom_tmpl_top . $body . $custom_tmpl_bottom;            
            }            
        }
        
        return $body;
    }

}
?>