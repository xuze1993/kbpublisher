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

class KBClientPageRenderer
{

    //var $site_path;
    var $title_delim = ' - ';
    var $vars = array();


    function __construct(&$view, &$manager) {
        $this->setCommonVars($view, $manager);
    }


    function setCommonVars(&$view, &$manager) {
        $this->view = &$view;
        $this->manager = &$manager;
        $this->vars['base_href'] = $this->view->controller->kb_path;
        $this->vars['client_href'] = $this->view->controller->client_path;
    }


    function getTemplateVars() {

        $data = array();
        $data['kbp_user_id'] = (int) $this->manager->user_id;
        $data['kbp_user_priv_id'] = (int) $this->manager->user_priv_id;
        $data['kbp_user_role_id'] = ($r = $this->manager->user_role_id) ? implode(',', $r) : 0;

        $data['kbp_view'] = $this->view->view_id;
        $data['kbp_article_id'] = 0;
        $data['kbp_file_id'] = 0;
        $data['kbp_news_id'] = 0;

        $class = get_class($this->manager);
        if($this->view->entry_id) {
            if(strpos($class, 'NewsModel') !== false) {
                $data['kbp_news_id'] = $this->view->entry_id;
            } elseif(strpos($class, 'FileModel') !== false) {
                $data['kbp_file_id'] = $this->view->entry_id;
            } else {
                $data['kbp_article_id'] = $this->view->entry_id;
            }
        }

        // echo '<pre>', print_r($data, 1), '</pre>';
        return $data;
    }


    function render() {

        // printing has different renderer
        if($this->view->page_print) {
            return $this->renderPrint();
        }
        
        if($this->view->page_popup) {
            return $this->renderPopup();
        }

        $page_to_load = $this->manager->setting['page_to_load'];
        $page_to_load_mobile = $this->manager->setting['page_to_load_mobile'];

        if(!$this->view->mobile_view && ($page_to_load == strtolower('html'))) {
            $tpl = new tplTemplatezString($this->getHtmlTemplate($this->manager->setting));

        } elseif ($this->view->mobile_view && ($page_to_load_mobile == strtolower('html'))) {
            $tpl = new tplTemplatezString($this->getHtmlTemplateMobile($this->manager->setting));

        } else {
            $tpl = new tplTemplatez($this->getTemplate($page_to_load));
        }

        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $data['content'] = &$tpl->tplParseString($this->view->getPageIn($this->manager), $this->vars);
        $data['content'] .= "\n" . $this->view->runAjax(); // we should to check it ...?

        // FIX: remove fieldcontain from forms to have title above input
        // if($this->view->mobile_view) {
        //     $data['content'] = str_replace('data-role="fieldcontain"', '', $data['content']);
        // }

        $data['meta_title'] = $this->getMetaTitle();
        $data['meta_keywords'] = $this->getMetaKeywords();
        $data['meta_description'] = $this->getMetaDescription();
        $data['meta_charset'] = $this->getMetaCharset();
        $data['meta_content_lang'] = $this->getMetaContentLang();
        $data['meta_robots'] = $this->getMetaRobots($this->view->view_id, $this->view->meta_robots);

        //echo "<pre>"; print_r($data['meta_title']); echo "</pre>";
        //echo "<pre>"; print_r($data['meta_keywords']); echo "</pre>";
        //echo "<pre>"; print_r($data['meta_description']); echo "</pre>";

        $str = '<link rel="stylesheet" type="text/css" href="%s" />';
        foreach($this->view->style_css as $v) {
            $css[] = sprintf($str, $v);
        }

        $data['style_css_links'] = implode("\n\t", $css) . "\n";
        $data['rss_head_links'] = $this->view->getRssHeadLinks($this->manager);

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        $tpl->tplAssign('ok_msg', RequestDataUtil::stripVars($this->view->msg['ok_msg']));
        $tpl->tplAssign('cancel_msg', RequestDataUtil::stripVars($this->view->msg['cancel_msg']));
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $debug_enabled = (empty($conf['debug_info'])) ? 0 : 1;
        $tpl->tplAssign('debug', $debug_enabled);

        // msg in growl
        @$msg_key = $_SESSION['success_msg_'];
        if($msg_key) {
            $msg = AppMsg::getMsg('after_action_msg.ini', 'public', $msg_key);
            @$tpl->tplAssign('growl_title', $msg['title']);
            @$tpl->tplAssign('growl_body', $msg['body']);
            $tpl->tplAssign('growl_show', 1);

            $_SESSION['success_msg_'] = false;
        }
        

        // trying to get adodb debug  
        // $debug = ob_get_contents();
        // if(strlen($debug)) {
        //     ob_clean();
        //     echo sprintf('<div id="debug">%s</div>', $debug);
        // }

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }


    function renderPrint($is_base_href = false) {

        $tpl = new tplTemplatez($this->view->getTemplate('page_print.html'));
        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $data['content'] = &$tpl->tplParseString($this->view->execute($this->manager), $this->vars);
        $data['meta_title'] = $this->view->meta_title;
        $data['meta_charset'] = $this->getMetaCharset();
        $data['meta_content_lang'] = $this->getMetaContentLang();
        $data['meta_robots'] = 'none';

        if($is_base_href) {
            $tpl->tplSetNeeded('/base_href');
            $tpl->tplSetNeeded('/anchor_js');
            $tpl->tplAssign('http_host', $this->getHttpHost());
        }

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }


    function renderPopup() {
        
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'page_popup_client.html');
        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $data['content'] = &$tpl->tplParseString($this->view->execute($this->manager), $this->vars);
        $data['meta_title'] = $this->view->meta_title;
        $data['meta_charset'] = $this->getMetaCharset();

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }

    // will required for attached article to email to correctly find images path
    function getHttpHost() {
        $http = (!empty($this->view->conf['ssl_client'])) ? 'https://' : 'http://';
        $path = (!empty($_SERVER['HTTP_HOST'])) ? $http . $_SERVER['HTTP_HOST'] : '';
        return $path;
    }


    function assign($var, $value) {
        $this->vars[$var] = $value;
    }


    function getHtmlTemplate($setting) {

        $page_to_load = $setting['page_to_load_tmpl'];
        $tmpl = explode('--delim--', $page_to_load);
        $header = $tmpl[0];
        $footer = (isset($tmpl[1])) ? $tmpl[1] : '';
        $head_code = (isset($tmpl[2])) ? $tmpl[2] : '';

        $setting_to_css = array(
            'header_background' => '#header_div {background-color: %s;}',
            'menu_background' => '.menuBlock {background-color: %s;}',
            'footer_background' => '.footer_info {background-color: %s;}',
            'menu_item_background' => '.menuItem {background-color: %s;}',
            'menu_item_background_hover' => '.menuItem:hover, .menuItem > a:hover {background-color: %s !important;}',
            'menu_item_background_selected' => '.menuItemSelected {background-color: %s;}',
            'header_color' => 'a.header {color: %s;}',
            'menu_item_color' => '.menuItem a {color: %s !important;}',
            'menu_item_color_selected' => '.menuItemSelected a {color: %s !important;}',
            'menu_item_bordercolor' => '.menuItem {border-color: %s;}',
            'menu_item_bordercolor_selected' => '.menuItemSelected {border-color: %s;}',
            'login_color' => 'div.login, a.login {color: %s;} div.login svg { fill: %s; }',
            'left_menu_width' => '#menu_content, #sidebar {width: %spx;} div.leftBlock #searchq {width: %spx;}'
            );

        $css = array();
        foreach ($setting_to_css as $k => $v) {
            if (!empty($setting[$k])) {
                if ($k == 'left_menu_width') {
                    $css[] = sprintf($v, $setting[$k], $setting[$k] - 85);
				} elseif ($k == 'login_color') {
                    $css[] = sprintf($v, $setting[$k], $setting[$k]);
                } else {
                    $css[] = sprintf($v, $setting[$k]);
                }

            } else {
                if ($k == 'menu_item_background_hover' && (!empty($setting['menu_item_background']))) {
                    $css[] = sprintf($v, $setting['menu_item_background']);
                }
            }
        }
        $css = '<style type="text/css" media="screen">' . implode("\n", $css) . '</style>';
        $head_code .= $css;

        $page_tmpl = $this->view->getTemplate($this->view->page_template);
        $page = explode('{content}', file_get_contents($page_tmpl));

        $page[0] = str_replace('{custom_template_head}', $head_code, $page[0]);

        $view_format = $this->view->controller->setting['view_format'];
        if ($view_format == 'fixed') {
            $header_background = (empty($setting['header_background'])) ? '#4584C1' : $setting['header_background'];
            $header = sprintf('<div id="custom_header" style="background-color: %s;">%s</div>', $header_background, $header);
            $footer = sprintf('<div id="custom_footer" style="background-color: #cccccc;">%s</div>', $footer);
        }

        $page = sprintf("%s\n\n%s\n\n{content}\n\n%s\n\n%s", $page[0], $header, $footer, $page[1]);

        return $page;
    }


    function getHtmlTemplateMobile($setting) {

        $page_to_load = $setting['page_to_load_tmpl_mobile'];
        $tmpl = explode('--delim--', $page_to_load);
        $footer = (isset($tmpl[1])) ? $tmpl[1] : '';
        $head_code = (isset($tmpl[2])) ? $tmpl[2] : '';

        $setting_to_css = array(
            'background_mobile' => '#header, .bottom {background: %s !important;}',
            'color_mobile' => '#header_title, .fa-search, .fa-align-justify, .fa-user, #rss_block, div.copyright a {color: %s !important;}',
            'menu_background_mobile' => '#bootstrap_section_block .list-group-item, #bootstrap_login_block .list-group-item {background: %s !important;}',
            'menu_color_mobile' => '#bootstrap_section_block .list-group-item, #bootstrap_login_block .list-group-item  {color: %s !important}',
            );

        $css = array();
        foreach ($setting_to_css as $k => $v) {
            if (!empty($setting[$k])) {
                $css[] = sprintf($v, $setting[$k]);
            }
        }
        $css = '<style type="text/css" media="screen">' . implode("\n", $css) . '</style>';

        $page_tmpl = $this->view->getTemplate($this->view->page_template);
        $page = explode('{content}', file_get_contents($page_tmpl));
        $page[0] = str_replace('{custom_template_head}', $head_code, $page[0]);
        $page = sprintf("%s\n\n%s\n\n%s\n\n{content}\n\n%s\n\n%s", $page[0], $css, $tmpl[0], $footer, $page[1]);

        return $page;
    }


    function getTemplate($page_to_load) {
        if(strpos($page_to_load, '[file]') !== false) {
            $page = trim(str_replace('[file]', '', $page_to_load));
        } else {
            $page = $this->view->getTemplate($this->view->page_template);
        }

        return $page;
    }


    // set title of page
    function getMetaTitle() {

        $data = array();
        if($this->manager->getSetting('site_title')) {
            $data[] = $this->manager->getSetting('site_title');

            if($this->view->view_id == 'files') {
                $data[] = $this->view->msg['file_title_msg'];
            }
        }

        if($this->view->meta_title) {
            $data[] = $this->view->meta_title;
        }

        if($this->view->view_id == 'entry') {
            $data = array_reverse($data);
        }

        if($this->view->view_id == 'news' && $this->view->entry_id) {
            $data = array_reverse($data);
        }

        return implode($this->title_delim, $data);
    }


    function getMetaKeywords() {
        return str_replace(array("\r\n", "\n", "\r"), ' ', $this->view->meta_keywords);
    }


    function getMetaDescription() {
        return str_replace(array("\r\n", "\n", "\r"), ' ', $this->view->meta_description);
    }


    function getMetaRobots($view, $view_meta_robots) {

        // array with view keys for what we use
        //<meta name="robots" content="all">
        $all_arr = array('index', 'entry', 'glossary', 'rssfeed', 'files', 'comment', 'news');
        $meta_robots = 'NONE';

        if(in_array($view, $all_arr)) {
            $meta_robots = 'ALL';
        }

        if(!$view_meta_robots) {
            $meta_robots = 'NONE';
        }

        return $meta_robots;
    }


    function getMetaCharset() {
        return $this->view->conf['lang']['meta_charset'];
    }


    function getMetaContentLang() {
        return $this->view->conf['lang']['meta_content'];
    }


    function getCache() {

        require_once 'eleontev/Util/ResultCache.php';

        $cache = new ResultCache();
        //$cache->refresh = true;
        $cache->exp_time = 14*24*60; // week    // time in minute after that cache will expiered, 0 = never expiered
        $cache->cache_dir = APP_CACHE_DIR;
        $cache->setUserFuncVars(array($this, 'render'));

        $roles = '';
        if($roles = AuthPriv::getRoleId()) {
            sort($roles);
            $roles = implode(',', $roles);
        }

        $cache_id = '.ht' . md5($_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING'] . AuthPriv::getPrivId() . $roles);
        return $cache->getData($cache_id);
    }


    function display() {

        //timestart("cache");
        //echo $this->getCache();
        //timestop("cache");

        //timestart("no cache");
        echo $this->render();
        //timestop("no cache");
    }
}
?>