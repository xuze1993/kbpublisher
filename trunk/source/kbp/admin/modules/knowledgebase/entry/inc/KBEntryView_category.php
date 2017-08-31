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

require_once 'core/common/CommonCategoryView.php';


class KBEntryView_category extends AppView 
{
    
    var $template = 'form_category.html';
    
    var $module = 'knowledgebase';
    var $entry_page = 'kb_entry';
    var $category_page = 'kb_category';
    var $action = 'category';
    
    
    function execute(&$obj, &$manager, $options = false) {
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        if (!$options) {
            $options = array(
                'sortable' => true,
                'secondary_block' => true,
                'cancel_button' => true,
                'creation' => $this->priv->isPriv('insert', 'kb_category'),
                'status_icon' => true,
                'mode' => 'entry',
                'popup_title' => $this->msg['assign_category_msg'],
                'main_title' => $this->msg['publish_in_msg'],
                'select_id' => 'category',
                'handler_name' => 'selHandler'
            );
        }

        $categories = $manager->getCategoryRecordsUser();  // private removed
        
        // client - determining the mode from GET
        $mode = $this->controller->getMoreParam('mode');
        if ($mode) {
            
            // specific options
            if ($mode == 'emode_secondary') {
                $options['main_title'] = $this->msg['also_list_in_msg'];
                $options['secondary_block'] = false;
                $options['sortable'] = false;
            }
            
            $options['mode'] = $mode;
        }
        
        return $this->parseCategoryPopup($manager->cat_manager, $categories, $options);
    }
    
    
    function parseCategoryPopup($manager, $categories, $options = array()) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $delim = (!empty($options['delim'])) ? $options['delim'] : ' -> ';
        $full_categories = $manager->getSelectRangeFolow($categories, 0, $delim);
        
        if (!empty($options['msg'])) {
            $this->msg = array_merge($this->msg, $options['msg']);
        }
        
        $tmpl = $this->template;
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $tmpl);        
        
        $tpl->tplAssign('popup_title', $options['popup_title']);
        $tpl->tplAssign('main_title', @$options['main_title']);
        
        $tpl->tplAssign('mode', $options['mode']);
        $tpl->tplAssign('select_id', @$options['select_id']);
        $tpl->tplAssign('handler_name', @$options['handler_name']);
        
        $tpl->tplAssign('creation_allowed', ($options['creation']) ? 'true' : 'false');
        if ($options['creation']) {
            $tpl->tplSetNeeded('/add_link');
        }
        
        if ($options['creation']) {
            $more = array('popup' => 1);
            $referer = WebUtil::serialize_url($this->getLink($this->module, $this->entry_page, false, $this->action, $more));
            $tpl->tplAssign('referer', $referer);
        }
        
        if (!empty($_GET['category_id'])) { // new
            $tpl->tplSetNeeded('/set_new_category');
            $category_id = $_GET['category_id'];

            $tpl->tplAssign('new_category', $category_id);
            $tpl->tplAssign('new_category_name', $full_categories[$category_id]);
        }
        
        $js_hash = array();
        $str = '{label: "%s", value: "%s", disabled: %s, parent_id: %s}';
        
        $parents_hash = array();
        $parents_str = '%s: [%s]';
        
        foreach(array_keys($full_categories) as $k) {
            $disabled = 'false';
            
            if (!empty($options['non_active_state']) && !$categories[$k]['active']) { // need to do smth with inactive
                if ($options['non_active_state'] == 'disabled') {
                    $disabled = 'true';
                    
                } elseif ($options['non_active_state'] == 'hidden') {
                    continue;
                }
            }
            
            $js_hash[] = sprintf($str, addslashes($full_categories[$k]), $k, $disabled, $categories[$k]['parent_id']);
            
            $parents = TreeHelperUtil::getParentsById($categories, $k);
            unset($parents[$k]);
            
            $parents_hash[] = sprintf($parents_str, $k, implode(',', $parents));
        }
        
        $js_hash = implode(",\n", $js_hash);
        if (!empty($options['all'])) {
            $js_hash = sprintf("{value: 0, label: \"%s\"}, \n%s", $this->msg['all_categories2_msg'], $js_hash);
        }
        
        $tpl->tplAssign('categories', $js_hash);
        
        // parents
        if (!empty($options['parent'])) {
            $tpl->tplSetNeeded('/parent');
            
            $parents_hash = implode(",\n", $parents_hash);
            $tpl->tplAssign('parent_categories', $parents_hash); 
        }
        
        
        // limit
        $category_limit = 0;
        if (!empty($options['limit'])) {
            $category_limit = $options['limit'];
        }
        $tpl->tplAssign('category_limit', $category_limit);
        
        
        // sortable
        if ($options['sortable']) {
            $tpl->tplSetNeededGlobal('sortable');
        }
        
        // icons
        $status_icon = ($options['status_icon']) ? 1 : 0;
        $tpl->tplAssign('status_icon', $status_icon);
        
        // non active
        $non_active_categories = array();
        foreach ($categories as $category) {
            if (isset($category['active']) && !$category['active']) {
                $non_active_categories[] = $category['id'];
            }
        }
        
        $js_hash = implode(',', $non_active_categories);
        $tpl->tplAssign('non_active_categories', $js_hash);

        
        if ($options['secondary_block']) {
            $tpl->tplSetNeededGlobal('secondary_block');
        }
        
        if ($this->controller) {
            $client_controller = &$this->controller->getClientController();
            $link = $client_controller->getLink('entry_add', 'category');
            $tpl->tplAssign('category_link', $this->controller->_replaceArgSeparator($link));
            
            $link = $this->controller->getFullLink($this->module, $this->category_page, false, 'insert');
            $tpl->tplAssign('popup_link', $this->controller->_replaceArgSeparator($link));
        }
        
        
        if ($options['cancel_button']) {
            $tpl->tplSetNeeded('/cancel_button');
            
            $button_title = $this->msg['ok_msg'];
            
        } else {
            $button_title = $this->msg['done_msg'];
        }
        
        $tpl->tplAssign('button_title', $button_title);
        
        //xajax
        if ($options['status_icon']) {
            $obj = false;
            $ajax = &$this->getAjax($obj, $manager);
            $xajax = &$ajax->getAjax();
            $this->categories = $categories;
            
            $more = array('field_id' => $this->controller->getMoreParam('field_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
            $xajax->registerFunction(array('getCategoryPrivateInfo', $this, 'ajaxGetCategoryPrivateInfo'));
        }
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplAssign('base_href', APP_CLIENT_PATH);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxGetCategoryPrivateInfo($category_id, $category_title) {
        $objResponse = new xajaxResponse();
        
        $html = PrivateEntry::getCategoryPrivateInfo($category_id, $category_title, $this->manager);
        $icon_color = ($html) ? 'red' : 'green';
        
        $title = sprintf('<b>%s</b>', $this->msg['public_msg']);
        if ($this->categories[$category_id]['private']) {
            $caption_msg = CommonCategoryView::getListPrivatesMsg($this->categories[$category_id]['private'], $this->msg);
            
            $roles_range = $this->manager->getRoleRangeFolow();
            $roles = $this->manager->getRoleById($category_id, 'id_list');
            
            $roles_msg = CommonCategoryView::getListRolesMsg($roles, $category_id, $roles_range, $this->msg);
            
            $title = sprintf('<b>%s</b>%s', $caption_msg, $roles_msg);
        }
        
        $img = sprintf('<img src="images/icons/lock2_%s.svg" width="12" height="12" class="_tooltip category3_img" title="%s" />', $icon_color, $title);
        $target = sprintf('$("input[value=\'%s\']")', $category_id);
        
        $script = sprintf('$(\'%s\').insertBefore(%s)', $img, $target);
        $objResponse->script($script);
        
        $objResponse->script("$('._tooltip').tooltipster({
            contentAsHTML: true,
            theme: 'tooltipster-kbp',
            interactive: true,
        });");
        
        return $objResponse;
    }
}
?>