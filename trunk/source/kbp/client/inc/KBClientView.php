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

class KBClientView extends BaseView
{

    var $working_dir;
    var $template_dir;
    var $template_view_dir;
    var $template;
    var $errors = array();
    var $form_data = array();

    var $msg = array();
    var $controller;
    var $css = array();

    var $meta_title;
    var $meta_keywords;
    var $meta_description;
    var $meta_robots = true;

    var $category_key;
    var $category_id;
    var $entry_key;
    var $entry_id;
    var $view_key;

    var $home_link = false;             // use home link n navigation as link
    var $category_nav_generate = true;     // generate or not categories in navigation line
    var $settings = array();

    var $nav_delim = ' / ';
    var $parse_category_select = true;    // to generate select with categories, no need in left view
    var $display_categories = true;        // display form with categories and other staff with categories
                                        // later we count categories and set var in KBClientView::getTop

    var $top_parent_id = 0;
    var $extra_breadcrumbs = array();
    var $page_template = 'page.html';
    var $page_print = false; // whether to use page_print.html
    var $page_popup = false; // whether to use page_popup_client.html

    // to manipulate, share, mix templates
    var $own_format = 'all';
    var $default_format = 'default';
    var $view_template = array();        // ???
    var $no_header_templates = array();  // no headers templates


    var $files_views = array('files', 'file', 'download');
    var $news_views = array('news');
    var $trouble_views = array('troubles', 'trouble');
    var $forum_views = array('forums', 'topic');

    var $parse_form = true;
    var $nav_title = false;
    var $ajax_call = false; // whether ajax was called or not
    var $ajax_post_action = false; // name of the js function to call when the ajax response is completed

    var $encoding;
    var $date_convert;

    var $action_msg_format = 'hint';
    var $mobile_view = false;

    // var $edit_mode = false;


    function __construct() {

        $reg = &Registry::instance();
        $this->conf = &$reg->getEntry('conf');
        $this->controller = &$reg->getEntry('controller');
        $this->encoding = $this->conf['lang']['meta_charset'];
        $this->date_convert = $this->getDateConvertFrom($this->conf['lang']);

        $this->setUrlVars();

        $this->setTemplateDir($this->controller->getSetting('view_format'),
                             $this->controller->getSetting('view_template'));

        $this->setCommonStyleCss($this->controller->getSetting('view_format'));
        $this->setStyleCss($this->controller->getSetting('view_format'),
                           $this->controller->getSetting('view_template'),
                           $this->controller->getSetting('view_style'),
                           $this->controller->getSetting('view_header'));

        $this->setMsg();
        //$this->setSettings();

        if(isset($this->conf['lang']['week_start'])) {
            $this->week_start = $this->conf['lang']['week_start'];
            $reg->setEntry('week_start', $this->week_start);
        }
    }


    function setCustomSettings() {

    }


    function setSetting($key, $value) {
        $this->controller->setting[$key] = $value;
    }


    function setUrlVars() {

        //$this->category_key = &$this->controller->category_key;
        $this->category_id = $this->controller->category_id;

        //$this->entry_key = &$this->controller->entry_key;
        $this->entry_id = $this->controller->entry_id;

        //$this->view_key = &$this->controller->view_key;
        $this->view_id = $this->controller->view_id;

        //$this->msg_key = &$this->controller->msg_key;
        $this->msg_id = $this->controller->msg_id;
    }


    function setTemplateDir($format, $skin) {

        if($this->own_format == 'none') {
            $format = $this->default_format;
        } elseif($this->own_format != 'all') {
            $format = (in_array($this->view_id, $this->own_format)) ? $format : $this->default_format;
        }

        $this->template_dir = $this->getTemplateDir($format, $skin);
    }


    function getTemplateDir($format, $skin) {
        return $this->controller->skin_dir . 'view_' . $format . '/' . $skin . '/';
    }


    function getTemplate($template, $template_dir = false) {

        $format = $this->controller->getSetting('view_format');
        $skin = $this->controller->getSetting('view_template');
        $template_dir = ($template_dir) ? $template_dir : $this->template_dir;

        if(in_array($template, $this->view_template)) {
            $template_dir = $this->getTemplateDir($format, $skin);
        }

        // no header
        if(!$this->controller->getSetting('view_header')) {
            if(isset($this->no_header_templates[$template])) {
                $template = $this->no_header_templates[$template];
            }
        }

        return $template_dir . $template;
    }


    function setStyleCss($format, $skin, $style, $header) {

        $css = array();
        $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/default.css';

        if(!$header) {
            $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/' . $style . '_no_header.css';
        }

        if($style != 'default') {
            $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/' . $style . '.css';
        }

        if (!empty($this->conf['lang']['rtl'])) {
            $css[] = $this->controller->skin_path . 'rtl.css';
        }

        $this->style_css = &$css;
    }


    function setCommonStyleCss($format) {
        $this->css['common_css'] = $this->controller->skin_path . 'common.css';
        $this->css['common_view_css'] = $this->controller->skin_path . 'view_' . $format . '/common_view.css';
        $this->css['common_ie_css'] = $this->controller->skin_path . 'common_ie.css';
        $this->css['common_table_css'] = $this->controller->skin_path . 'common_table.css';
        $this->css['print_css'] = $this->controller->skin_path . 'print.css';
    }


    function getMsgFile($file, $module = true) {
        require_once 'core/app/AppMsg.php';
        return ($module) ? AppMsg::getModuleMsgFile($module, $file)
                         : AppMsg::getCommonMsgFile($file);
    }


    function setMsg() {
        $file = $this->getMsgFile('client_msg.ini', 'public');
        $this->msg = AppMsg::parseMsgs($file, false, false);
    }


    function addMsg($file, $module = false) {

        // always parse two files for user
        if($file == 'user_msg.ini') {
            $module = 'public';
        }

        $this->msg = array_merge($this->msg, AppMsg::getMsgs($file, $module));
    }


    function getFormatedDate($timestamp, $format = false) {

        if($format === false || $format === 'date') {
            $format = $this->conf['lang']['date_format'];
        } elseif($format === 'datetime') {
            $format = $this->conf['lang']['date_format'] . ' ' . $this->conf['lang']['time_format'];
        }

        return $this->_getFormatedDate($timestamp, $format);
    }


    function getActionMsg($format = 'success', $msg_id = false, $strip = false, $replacements = array()) {

        $msg_id = ($msg_id) ? $msg_id : $this->msg_id;

        $this->controller->getView('success_go');
        if($f = KBClientView_success_go::getMsgId($msg_id, true)) {
            $format = $f;
        }

        if($msg_id) {
            require_once 'eleontev/HTML/BoxMsg.php';

            $file = $this->getMsgFile('after_action_msg.ini', 'public');
            $msgs = AppMsg::parseMsgs($file, $msg_id, true);

            if($msgs) {
                if($strip) { $msgs = parent::stripVars($msgs, array(), $strip); }
                return BoxMsg::factory($format, $msgs, $replacements);
            }
        }
    }


    // true if commentable
    function isCommentable($manager, $var = true) {
        return (bool) ($manager->getSetting('allow_comments') && $var);
    }


    // true if ratingable
    function isRatingable($manager, $var = true) {
        return (bool) ($manager->getSetting('allow_rating') && $var);
    }


    function getCaptchaSrc() {
        return $this->controller->kb_path . 'captcha.php';
    }


    function useCaptcha($manager, $section, $count_attempt = false) {

        $ret = false;
        $captcha_val = $section . '_captcha';
        $captcha_set = $manager->getSetting($captcha_val);

        if($captcha_set == 'yes') {
            $ret = true;
        } elseif($captcha_set == 'yes_no_reg' && !$manager->is_registered) {
            $ret = true;
        }

        if($ret == true) {
            require_once 'eleontev/Util/CaptchaImage.php';
            if(!CaptchaImage::isRequredLib()) {
                $ret = false;
            }
        }

        // show captcha only after some bad attempts
        // if($ret && !empty($_SESSION)) {
        //     $ret = $this->isCaptchaAttemptExceed($count_attempt, $this->view_id, 3);
        // }

        return $ret;
    }


    static function isCaptchaValid($captcha, $reset_attempt = false, $unset = true) {

        if(!$captcha || !isset($_SESSION['kb_captcha_'])) {
            return false;
        }

        $ret = true;
        $ip = WebUtil::getIP();
        $ip = ($ip == 'UNKNOWN') ? mt_rand(5, 15) : $ip;

        $s_captcha = strtolower($_SESSION['kb_captcha_']);
        $u_captcha = strtolower($captcha);

        if($s_captcha != $u_captcha || $_SESSION['kb_captchaip_'] != $ip) {
            $ret = false;
        }

        if($ret && $unset) {
            if(isset($_SESSION['kb_captcha_'])) {
                unset($_SESSION['kb_captcha_']);
            }

            if(isset($_SESSION['kb_captchaip_'])) {
                unset($_SESSION['kb_captchaip_']);
            }
        }

        // if($reset_attempt) {
        //     $this->resetCaptchaAttempt($ret, $this->view_id);
        // }

        return $ret;
    }


    function resetCaptchaAttempt($reset_attempt = true, $suffix = false) {
        $suffix = ($suffix === false) ? $this->view_id : $suffix;
        $sname = sprintf('kb_captcha_%s_', $suffix);
        if($reset_attempt && isset($_SESSION[$sname])) {
            $_SESSION[$sname] = 0;
            unset($_SESSION[$sname]);
        }
    }


    function isCaptchaAttemptExceed($count_attempt, $suffix, $num_tries = 3) {

        // $suffix = ($suffix === false) ? $this->view_id : $suffix;
        $sname = sprintf('kb_captcha_%s_', $suffix);
        $ret = (!empty($_SESSION[$sname]) && $_SESSION[$sname] >= $num_tries);
        if($count_attempt) {
            $_SESSION[$sname] = (empty($_SESSION[$sname])) ? 1 : $_SESSION[$sname] += 1;
        }

        return $ret;
    }


    function isSubscriptionAllowed($block, $manager) {
        $subsc_allowed = $manager->getSetting($block);
        if(AuthPriv::isRemote()) {
            $subsc_allowed = false;
        }

        // for users with priv only, false if logged and no priv
        if($subsc_allowed == 3 && $manager->is_registered && !AuthPriv::getPrivId()) {
            $subsc_allowed = false;
        }

        return $subsc_allowed;
    }


    function getPageByPageObj($class, $limit = false, $hidden = false, $action_page = false) {

        $msg = array(
            $this->msg['page_msg'],
            $this->msg['record_msg'],
            $this->msg['record_from_msg'],
            $this->msg['prev_msg'],
            $this->msg['next_msg']
        );

        $bp = PageByPage::factory('page', $limit, $hidden, $action_page);
        $bp->setMsg($msg);

        return $bp;
    }


    function pageByPage($limit, $sql, $action_page = false, $db_obj = false, $hidden = false) {

        if(!$action_page) {
            $action_page = $this->getLink($this->view_id, $this->category_id, $this->entry_id);
        }

        // then sql will be executed and we need db obj
        if(!$db_obj && !is_numeric($sql)) {
            $reg = &Registry::instance();
            $db_obj = &$reg->getEntry('db');
        }

        $bp = $this->getPageByPageObj('page', $limit, $hidden, $action_page);
        $bp->countAll($sql, $db_obj);

        return $bp;
    }


    function pageByPageBottom($bp) {
        return PageByPage::factory('page', $bp);
    }


    function stripVars($values, $html = array('body'), $to_display = 'display') {
    // function stripVars(&$values, $html = array('body'), $to_display = 'display') {
        return parent::stripVars($values, $html, $to_display);
    }


    // if user logged in we know his roles so we show private without
    // roles and hide with not his roles
    // does not depaends on setting hide/show private entry
    function isPrivateEntryLocked($registered, $private) {
        return ($private && !$registered) ? true : false;
    }


    // no summary if private
    function getSummaryLimit($manager, $private, $limit = false) {
        $limit = ($limit === false) ? $manager->getSetting('preview_article_limit') : $limit;
        // return ($private && !$manager->is_registered) ? 0 : $limit;
        return ($this->isPrivateEntryLocked($manager->is_registered, $private)) ? 0 : $limit;
    }


    function _getItemImg($registered, $var, $category = false, $ext = false, $path = false, $icon_path = false) {

		$tag = '';
        $icon_path = ($icon_path === false) ? $this->controller->client_path . 'images/icons/' : $icon_path;

		// faq, faq2, book, related, attachments, etc
        if($category === 'list') {
			$file = sprintf("%sbullet.svg", $icon_path);
            $tag = '<img src="%s" alt="b" style="vertical-align: middle;" />';
            $tag = sprintf($tag, $file);

			if($this->isPrivateEntryLocked($registered, $var)) {
				$file = sprintf("%sbullet_red.svg", $icon_path);
	            $tag = '<img src="%s"  alt="private" title="%s" style="vertical-align: middle;" />';
	            $tag = sprintf($tag, $file, $this->msg['login_to_view_msg']);
			}

		// private
        } elseif($this->isPrivateEntryLocked($registered, $var)) {
			$margin = ($category === true) ? 0 : 3;
            $file = sprintf("%slock1.svg", $icon_path);
            $tag = '<img src="%s" alt="private" width="15" height="15" title="%s" style="margin-right: %dpx; vertical-align: middle;" />';
            $tag = sprintf($tag, $file, $this->msg['login_to_view_msg'], $margin);

        // } elseif($category === 'file') {
        //     $ext = strtolower($ext);
        //     $_file = sprintf('%sclient/images/filetypes2/%s.svg', $this->controller->kb_dir, $ext);
        //     $file = sprintf('%simages/filetypes2/%s.svg', $this->controller->client_path, $ext);
        //     $tag = sprintf('<img src="%s" alt="file" width="16" style="vertical-align: middle;" />', $file);
        //     if(!file_exists($_file)) {
        //         $file = sprintf('%simages/filetypes2/%s.svg', $this->controller->client_path, 'file');
        //         $tag = sprintf('<img src="%s" alt="file" width="16" style="vertical-align: middle;" />', $file);
        //     }

        } elseif(in_array($category, array('news', 'file', 'article'), true)) {
			$file = sprintf('%s%s.svg', $icon_path, $category);
			$tag = sprintf('<img src="%s" alt="item" width="14" height="14" style="vertical-align: middle;" />', $file);

	    } elseif($category === 'rss') {
	       	$file = sprintf('%srss_feed_color.svg', $icon_path);
	       	$tag = sprintf('<img src="%s" alt="rss" width="14" height="14" />', $file);

		// category
        } elseif($category === true || $category === 'up') {
			$file = ($category === true) ? '%sfolder2.svg' : '%sfolder_up.svg';
            $file = sprintf($file, $icon_path);
            $tag = '<img src="%s" alt="folder" width="14" height="14" style="vertical-align: middle;" />';
            $tag = sprintf($tag, $file);

		} elseif($category)  {
			$file = sprintf('%s%s.svg', $icon_path, $category);
			$tag = sprintf('<img src="%s" alt="item" width="14" height="14" style="vertical-align: middle;" />', $file);
		}


        if($path && $file) {
			$tag = $file;
		}

        return $tag;
    }


    function getItemImgIcon($icon, $title = false) {

        $file = sprintf("%simages/icons/%s.svg", $this->controller->client_path, $icon);
        $tag = '<img src="%s" alt="%s" title="%s" width="14" height="14" style="vertical-align: middle;" />';
        $tag = sprintf($tag, $file, $file, $title);

        return $tag;
    }


    function _getRating($var) {
        if($var) {
            $tag = '<img src="%simages/rating/rate_star/rate_%s.gif" alt="rating" />';
            $rate = sprintf($tag, $this->controller->client_path, round($var));
        } else {
            $tag = '<img src="%simages/rating/rate_star/stars_norate_5_grey.gif" alt="rating" title="%s"/>';
            $rate = sprintf($tag, $this->controller->client_path, $this->msg['not_rated_msg']);
        }

        return $rate;
    }


    function getEntryPrefix($id, $type, $types, $manager) {

        $entry_id = '';
        $pattern = $manager->getSetting('entry_prefix_pattern');
        $padding = $manager->getSetting('entry_id_padding');

        if($pattern) {

            $type = ($type) ? $types[$type] : '';
            $pattern = explode('|', $pattern);

            if($padding) {
                $id = (is_numeric($padding)) ? sprintf("%'0".$padding."s", $id) : sprintf($padding, $id);
            }

            $replace = array('{$entry_id}' => $id, '{$entry_type}' => $type);

            if(isset($pattern[1])) {
                $entry_id = ($type) ? strtr(trim($pattern[1]), $replace) : strtr(trim($pattern[0]), $replace);
            } else {
                $entry_id = strtr(trim($pattern[0]), $replace);
            }

            $entry_id .= ' ';
        }

        return $entry_id;
    }


    function getRowClass() {
        static $i = 1;
        return ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors
    }


    function setCrowlMsg($msg_key) {
        $this->controller->setCrowlMsg($msg_key);
    }


    function getLink($view = false, $category_id = false, $entry_id = false, $msg_key = false,
                        $more = array(), $more_rewrite = false) {

        return $this->controller->getLink($view, $category_id, $entry_id, $msg_key,
                                            $more, $more_rewrite);
    }


    function getMoreLink($view_id) {
        $str = '<a href="%s"><b>%s...</b></a>';

        if ($this->category_id) {
            $link = $this->getLink($view_id, $this->category_id);

        } else {
            $link = $this->getLink($view_id);
        }

        return sprintf($str, $link, $this->msg['more_msg']);
    }


    function getRssHeadLinks($manager) {

        $rss = false;
        $rss_setting = $manager->getSetting('rss_generate');
        if($rss_setting == 'none') {
            return $rss;
        }

        $rss_head = '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />';
        $rss_file = $this->controller->kb_path . 'rss.php';
        $rss_title = $manager->getSetting('rss_title');

        // all articles and  news
        $rss = array();
        $rss[] = sprintf($rss_head, $rss_title, $rss_file);
        if($manager->getSetting('module_news')) {
            $title = sprintf('%s (%s)', $rss_title, $this->msg['news_title_msg']);
            $rss[] = sprintf($rss_head, $title, $rss_file. '?t=n');
        }

        $rss = implode("\n\t", $rss);
        return $rss;
    }


    function &getPageIn(&$manager) {

        $content = $this->execute($manager);

        $tpl = new tplTemplatez($this->getTemplate('page_in.html'));

        // need to print msg if any
        $tpl->tplAssign('msg', $this->getActionMsg($this->action_msg_format));

        //header
        $tpl->tplAssign('header_title', $manager->getSetting('header_title'));
        $tpl->tplAssign('header_title_link', $this->getLink());

        if($manager->getSetting('view_header')) {
            $tpl->tplSetNeeded('/header');

            $setting_key = ($this->mobile_view) ? 'header_logo_mobile' : 'header_logo';
            $logo = $manager->getSetting($setting_key);
            if ($logo) {
                $tpl->tplSetNeeded('/logo');
                $tpl->tplAssign('image_data', $logo);
            }

        } else {
            $tpl->tplSetNeeded('/no_header');
        }

        $this->parseCustomPageIn($tpl, $manager);

        $tpl->tplAssign('navigation', $this->_getFormatedNavigation($manager, $this->nav_title));

        $manage_menu = &$this->getManageMenuData($manager);
        $tpl->tplAssign('login_block_tmpl', $this->getLoginBlock($manager, $manage_menu));


        $view_format = $manager->getSetting('view_format');

        // top menu
        $kb_menu = &$this->getTopMenuData($manager);
		if($view_format == 'left') {
			$tpl->tplAssign('menu_top_tmpl', $this->getTopMenuBlock($kb_menu));
		} elseif ($kb_menu) {
            $tpl->tplAssign('menu_top_tmpl', $this->getTopMenuBlock($kb_menu));
            if($view_format == 'fixed') {
                $tpl->tplSetNeeded('/menu_top');  // hide space on the left in fixed view
            }
		}

        // modified 7 jun, 2016 to always parse form in default view and parse search in fixed
        if($view_format == 'left') {
            $tpl->tplAssign('search_block', $this->getSearchBlock($manager));
            if($this->parse_form) {
                $this->parseForm($tpl, $manager);
                $tpl->tplAssign('menu_content_tmpl', $this->getLeftMenu($manager));

                $menu_display = 'block';
                $menu_link_display = 'none';
                if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 0) {
                    $menu_display = 'none';
                    $menu_link_display = 'inline';
                }

                $tpl->tplAssign('menu_display', $menu_display);
                $tpl->tplAssign('menu_link_display', $menu_link_display);
            }

        } elseif($view_format == 'fixed') {

            $tpl->tplAssign('not_registered_padding', ($manager->is_registered) ? 0 : '5px');

			$tpl->tplAssign('search_block', $this->getSearchBlock($manager));
            if($this->parse_form) {
                $this->parseForm($tpl, $manager);
                $tpl->tplAssign('menu_content_tmpl', $this->getLeftMenu($manager));
            }

            $sidebar_action_title = $this->msg['hide_sidebar_msg'];
            $next_action_title = $this->msg['show_sidebar_msg'];
            if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 1) {
                $sidebar_action_title = $this->msg['show_sidebar_msg'];
                $next_action_title = $this->msg['hide_sidebar_msg'];
            }

            $tpl->tplAssign('action_title', $sidebar_action_title);
            $tpl->tplAssign('next_action_title', $next_action_title);

        } else {
            $this->parseForm($tpl, $manager);
            $tpl->tplAssign('search_block', $this->getSearchBlock($manager));
        }
        // -----------------

        if ($this->mobile_view) {
            $content .= $this->runAjax();
        }

        $tpl->tplAssign('content', $content);
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign($this->msg);

        if(isset($_GET['q'])) {
            $tpl->tplAssign('q', $this->stripVars(trim($_GET['q']), array(), 'asdasdasda'));
        }


        $a = array();
        // $a['footer_info'] = nl2br($manager->getSetting('footer_info'));

        if($manager->getSetting('rss_generate') != 'none') {
            $a['rss_link'] = $this->getLink('rssfeed');
            $tpl->tplSetNeeded('/rss_block');
        }

        if(!$manager->getSetting('license_key4')) {
            $str = $this->getCopyrightInfoMsg();
            $tpl->tplAssign('copyright_info', $str);
        }

        $tpl->tplAssign($a);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function parseCustomPageIn(&$tpl, $manager) {
        return false;
    }


    function getCopyrightInfoMsg() {
        $str = '<a href="%s" title="%s" target="_blank">Powered by %s</a> <span>(%s)</span>';
        return sprintf($str, $this->conf['product_www'], $this->conf['product_name'],
                             $this->conf['product_name'], $this->conf['product_desc']);
    }


    function parseForm(&$tpl, $manager) {

        // categories ?
        // probably in some cases we should not dispaly categories if only 1 category for example
        if($this->display_categories && $this->parse_category_select) {

            $view = (in_array($this->view_id, $this->files_views)) ? 'files' : '';
            // $view = (in_array($this->view_id, $this->trouble_views)) ? 'troubles' : '';
            $view = (in_array($this->view_id, $this->forum_views)) ? 'forums' : '';

            $tpl->tplAssign('category_page', $this->getLink($view));
            $tpl->tplAssign('rewrite', ($this->controller->mod_rewrite) ? 1 : 0);

            // load article manager to display select, it also will be in ajax
            if(!$manager->categories) {
                $manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'index');
            }

            $top_cat_id = 0;
            if($this->category_id) {
                $top_cat_id = TreeHelperUtil::getTopParent($manager->categories, $this->category_id);
            }

            $tpl->tplAssign('filter_select', $this->_getCategoryFilterSelect($manager, $top_cat_id));

            //xajax
            $ajax = &$this->getAjax('category_filter');
            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('getChildCategories', $ajax, 'ajaxGetChildCategories'));

            if ($top_cat_id) {
                list($category_select, $is_empty) = $this->_getCategorySelect($manager, $top_cat_id);
                $tpl->tplAssign('category_select', $category_select);
                $display = ($is_empty) ? 'none' : 'block';
            } else {
                $display = 'none';
            }

            $tpl->tplAssign('category_filter_display', $display);
        }

        // files
        if(!$this->controller->mod_rewrite && ($this->view_id == 'files')) {
            $arr = array($this->controller->getRequestKey('view') => 'files');
            $hidden_category = http_build_hidden($arr, true);
            $tpl->tplAssign('hidden_category', $hidden_category);
        }

        // trouble
        if(!$this->controller->mod_rewrite && ($this->view_id == 'troubles')) {
            $arr = array($this->controller->getRequestKey('view') => 'troubles');
            $hidden_category = http_build_hidden($arr, true);
            $tpl->tplAssign('hidden_category', $hidden_category);
        }

        // forum
        if(!$this->controller->mod_rewrite && (in_array($this->view_id, $this->forum_views))) {
            $arr = array($this->controller->getRequestKey('view') => 'forums');
            $hidden_category = http_build_hidden($arr, true);
            $tpl->tplAssign('hidden_category', $hidden_category);
        }

        // search
        // $sp = $this->_getSearchFormParams();
        $tpl->tplAssign('category_id', (!empty($this->category_id)) ? $this->category_id : 0);

        // in browseable view
        $tpl->tplAssign('form_category_action', $this->getLink('form'));
        // $tpl->tplAssign('advanced_search_link', $this->getLink('search', $this->category_id) . $sp['search_str']);

        // $tpl->tplAssign('search_block', $this->getSearchBlock($manager));

        $tpl->tplSetNeededGlobal('form');
    }


    function getSearchBlock($manager) {

        $sp = $this->_getSearchFormParams();
        $default_in = $manager->getSetting('search_default');

        $tpl = new tplTemplatez($this->getTemplate('block_search.html'));

        $view_format = $this->controller->getSetting('view_format');
        //if ($view_format != 'left') {
            // $tpl->tplSetNeeded('/focus_animation');
        //}

        $submit_icon = ($view_format == 'default') ? 'search_submit' : 'search_submit2';
        $tpl->tplAssign('submit_icon', $submit_icon);

        $msg = AppMsg::getMsg('ranges_msg.ini', 'public', 'search_in_range');

        // main
        $keys = array('article');

        if($manager->getSetting('module_file')) {
            $keys[] = 'file';
        }

        if($manager->getSetting('module_news')) {
            $keys[] = 'news';
        }

        if($manager->getSetting('module_forum')) {
            $keys[] = 'forum';
        }

        // categories
        $views = array('index', 'entry', 'files', 'forums', 'topic');
        if (in_array($this->view_id, $views) && $this->category_id) {
            array_unshift($keys, 'category');

            if (in_array($this->view_id, $this->forum_views)) {
                $msg['category'] = $this->msg['this_forum_msg'];
            }
        }

        // additional
        if ($this->view_id == 'topic') {
            array_unshift($keys, 'topic');
        }

        array_unshift($keys, 'all');

        $last_key = end($keys);
        foreach ($keys as $key) {
            $v['filter_id'] = $key;

            $v['filter_key'] = $key;
            if ($key == 'category') {
                if (in_array($this->view_id, $this->forum_views)) {
                    $v['filter_key'] = 'forum';

                } elseif (in_array($this->view_id, $this->files_views)) {
                    $v['filter_key'] = 'file';

                } else {
                    $v['filter_key'] = 'article';
                }
            }

            if ($key == 'topic') {
                $v['filter_key'] = 'forum';
            }

            $v['filter_title'] = $msg[$key];

            $v['filter_options'] = '';
            if ($key == 'category') {
                $v['filter_options'] = sprintf('data-category="%s"', $this->category_id);
            }

            if ($key == 'topic') {
                $v['filter_options'] = sprintf('data-topic="%s"', $this->entry_id);
            }

            $v['class'] = '';
            if (empty($_GET['in'])) {
                if ($key == $default_in) {
                    $v['filter_options'] .= ' checked';
                    $v['class'] = 'selected';
                }

            } elseif ($_GET['in'] == $key) {
                $v['filter_options'] .= ' checked';
                $v['class'] = 'selected';
            }

            $tpl->tplParse($v, 'filter_row');
        }

        $tpl->tplAssign('advanced_search_link',
            $this->getLink('search', $this->category_id));

        if($manager->getSetting('search_suggest')) {
            $tpl->tplSetNeeded('/search_suggest');
            $tpl->tplAssign('suggest_link', $this->controller->kb_path . 'endpoint.php?type=suggest');
        }

        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('q', ''); // we always has advanced from after submit
		$tpl->tplAssign('alert_empty_search', addslashes($this->msg['alert_empty_search_msg']));

        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }


    function _getCategorySelect($manager, $top_category_id) {

        $range_ = $manager->getCategorySelectRange($manager->categories, $top_category_id);

        $range = array();
        if(isset($manager->categories[$top_category_id])) {
            $range[$top_category_id] = $manager->categories[$top_category_id]['name'];
        }

        if (!empty($range_)) {
            foreach (array_keys($range_) as $cat_id) {
                $range[$cat_id] = '-- '. $range_[$cat_id];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);

        return array($select->select($this->category_id), empty($range_));
    }


    function _getCategoryFilterSelect($manager, $top_category_id) {

        $range = array();
        foreach(array_keys($manager->categories) as $cat_id) {
            if ($manager->categories[$cat_id]['parent_id'] == 0) {
                $range[$cat_id] = $manager->categories[$cat_id]['name'];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range, array('all' => '__'));

        return $select->select($top_category_id);
    }


    function _getSearchFormParams() {

        $search_str = '';
        if(isset($_GET['q'])) {
            $q = $this->stripVars(trim($_GET['q']), array(), 'asdasdasda');
            $sign = ($this->controller->mod_rewrite) ? '?' : '&';
            $search_str = sprintf('%sq=%s', $sign, $q);
        }

        $hidden_search = '';
        if(!$this->controller->mod_rewrite) {
            $arr = array($this->controller->getRequestKey('view') => 'search');
            $hidden_search = http_build_hidden($arr, true);
        }

        return array('search_str' => $search_str, 'hidden_search' => $hidden_search);
    }


    function &getTopMenuData($manager) {

        $menu = array();

        $menu[2]['views'] = array(
            'index', 'entry', 'comment', 'comments',
            'afile', 'recent', 'popular', 'category');
        $menu[2]['item_menu'] = $this->msg['menu_article_msg'];
        $menu[2]['item_link'] = $this->getLink('index');

        if($manager->getSetting('module_news') && $manager->getSetting('show_news_link')) {
            $menu[1]['views'] = array('news');
            $menu[1]['item_menu'] = $this->msg['menu_news_msg'];
            $menu[1]['item_link'] = $this->getLink('news');
        }

        if($manager->getSetting('module_file')) {
            $menu[5]['views'] = array('files', 'file', 'download', 'fsearch');
            $menu[5]['item_menu'] = $this->msg['menu_file_msg'];
            $menu[5]['item_link'] = $this->getLink('files');
        }

        // if($manager->getSetting('module_trouble')) {
        //     $menu[6]['views'] = array('troubles', 'trouble');
        //     $menu[6]['item_menu'] = $this->msg['menu_trouble_msg'];
        //     $menu[6]['item_link'] = $this->getLink('trouble');
        // }

        if($manager->getSetting('module_forum')) {
            $menu[6]['views'] = array('forums', 'topic', 'member_topic', 'member_topic_message', 'forum_recent');
            $menu[6]['item_menu'] = $this->msg['menu_forum_msg'];
            $menu[6]['item_link'] = $this->getLink('forums');
        }

        if($manager->getSetting('module_glossary')) {
            $menu[7]['views'] = array('glossary');
            $menu[7]['item_menu'] = $this->msg['menu_glossary_msg'];
            $menu[7]['item_link'] = $this->getLink('glossary');
        }

        if($manager->getSetting('module_tags')) {
            $menu[9]['views'] = array('tags');
            $menu[9]['item_menu'] = $this->msg['menu_tags_msg'];
            $menu[9]['item_link'] = $this->getLink('tags');
        }

        if($manager->getSetting('allow_contact')) {
            $menu[10]['views'] = array('contact');
            $menu[10]['item_menu'] = $this->msg['menu_contact_us_msg'];
            $menu[10]['item_link'] = $this->getLink('contact', $this->category_id);
        }

        // home behaviour
        if(0) {

            $menu[0]['views'] = array('index');
            $menu[0]['item_menu'] = $this->controller->getSetting('nav_title');
            $menu[0]['item_link'] = $this->getLink('index');

            // rewtite knowledgebase menu
            $this->msg['menu_article_msg'] = $this->msg['menu_entry_msg'];
            $menu[2]['views'] = array('entries', 'entry', 'comment', 'comments', 'afile');
            $menu[2]['item_menu'] = $this->msg['menu_article_msg'];
            $menu[2]['item_link'] = $this->getLink('entries');

            ksort($menu);
        }

        // extra
        if($menu_extra = $manager->getSetting('menu_extra')) {
            $num = 20;
            foreach(explode('||', $menu_extra) as $v) {
                $key = 'extra_' . $num++;
                @list($title, $link, $options, $more) = explode('|', $v);
				$options = (empty($options)) ? '' : $options; // convert 0 to ''

                $menu[$key]['views'] = array();
                $menu[$key]['item_menu'] = trim($title);
                $menu[$key]['item_link'] = trim($link);
                $menu[$key]['options'] = trim($options);
                $menu[$key]['more'] = $more;
            }
        }

        if($this->mobile_view) {
            $menu[11]['views'] = array();
            $menu[11]['item_menu'] = $this->msg['search_msg'];
            $menu[11]['item_link'] = $this->getLink('search', $this->category_id);

            $menu[12]['views'] = array();
            $menu[12]['item_menu'] = $this->msg['browse_msg'];
            $menu[12]['item_link'] = $this->getLink('category');
        }

        $this->msg['menu_title_msg'] =  $this->msg['menu_article_msg'];
        if(in_array($this->view_id, $this->files_views)) {
            $this->msg['menu_title_msg'] =  $this->msg['menu_file_msg'];
        }
        if(in_array($this->view_id, $this->trouble_views)) {
            $this->msg['menu_title_msg'] =  $this->msg['menu_trouble_msg'];
        }

        // not to display one menu item
        if(count($menu) == 1) {
            $menu = array();
        }

        return $menu;
    }


    function getActionMenu($items) {

		if(!$items) {
			return '';
		}

        $html = array();
        $html[] = '<ul class="dropdown-menu">';
        $item_str = '<li><a href="%s" rel="nofollow" %s>%s</a><li class="dropdown-divider"></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';

        foreach ($items as $item) {
            if ($item[1]) {
                $str = 'onclick="confirm2(\'%s\', \'%s\');return false;"';
                $confirmation = (!empty($item[2])) ? sprintf($str, $this->msg['sure_msg'], $item[1]) : '';
                $html[] = sprintf($item_str, $item[1], $confirmation, $item[0]);

            } else {
                $html[] = sprintf($disabled_item_str, $item[0]);
            }
        }
        $html[] = '</ul>';

        return implode('', $html);
    }


    function &getManageMenuData($manager) {

        $manage = array();
        $menu = array();

        // edit mode
        /*
        $allowed = AuthPriv::getPrivAllowed('kb_entry');
        if(in_array('update', $allowed)) {// full priv to update

            $allowed_ = array('kb_entry' => $allowed);
            if(!AuthPriv::isPrivOptionalStatic('update', 'draft', 'kb_entry', $allowed_)) {
                $emode = (!empty($_COOKIE['kb_emode_']));
                $msg_key = ($emode) ? 'edit_mode_off_msg' : 'edit_mode_on_msg';
                $js_action = ($emode) ? 'deleteCookie(\'kb_emode_\', \'/\');' : 'createCookie(\'kb_emode_\', true);';
                $manage['edit_mode'] = array(
                    'item_link' => sprintf('javascript:location.reload();%s', $js_action),
                    'item_menu' => $this->msg[$msg_key]
                );

                $manage[] = 'delim';
            }
        }*/


        // add article here new
        if($manager->isEntryAddingAllowedByUser()) {
            $more = array('mode' => 'client_new');
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'category', $more);
            $options = sprintf('onclick="PopupManager.create(\'%s\',\'\',\'\',\'\',770,400);"', $link);
            $link = '#';

            if((in_array($this->view_id, array('index', 'entry', 'entry_add'))
                    && $this->category_id)) {
                if($manager->isEntryAddingAllowedByUser($this->category_id)) {
                    $link = $this->controller->getLink('entry_add', $this->category_id);
                    $options = false;
                }
            }

            $manage['article_here'] = array(
                'item_menu' => $this->msg['menu_add_article_here_msg'],
                'item_link' => $link,
                'item_options' => $options
            );

            $manage[] = 'delim';
        }


        // actions to admin area
        $views = array('index', 'entry', 'comment');
        $menu[1]['article'] = array($views, 'knowledgebase', 'kb_entry', 'menu_add_article_msg', 1);
        $menu[1]['article_draft'] = array($views, 'knowledgebase', 'kb_draft', 'menu_add_article_draft_msg', 1);
        $menu[1]['article_c'] = array(array(), 'knowledgebase', 'kb_category', 'menu_add_article_cat_msg');

        $views = array('files');
        $menu[2]['file'] = array($views, 'file', 'file_entry', 'menu_add_file_msg', 1);
        $menu[2]['file_draft'] = array($views, 'file', 'file_draft', 'menu_add_file_draft_msg', 1);
        $menu[2]['file_c'] = array(array(), 'file', 'file_category', 'menu_add_file_cat_msg');

        // $views = array('troubles', 'trouble');
        // $menu['trouble'] = array($views, 'trouble', 'trouble_entry', 'menu_add_trouble_msg', 1);
        // $menu[] = 'devider';

        $menu[3]['news']      = array(array(), 'news', 'news_entry', 'menu_add_news_msg');

        $delim = array();
        $drafts = array('kb_entry', 'file_entry');

        foreach($menu as $group_key => $group) {

            foreach($group as $k => $v) {
                // if($v == 'delim') {
                //    $manage[] = $v;
                //    continue;
                // }

                $allowed = AuthPriv::getPrivAllowed($v[2]);
                if(in_array('insert', $allowed)) {
                    $more = array();
                    $more['referer'] = 'client';
                    if(in_array($this->view_id, $v[0]) && $this->category_id) {
                        $more['filter[c]'] = $this->category_id;
                    }

                    $link = $this->controller->getAdminRefLink($v[1], $v[2], false, 'insert', $more, false);
                    $manage[$k]['item_link'] = $link;
                    $manage[$k]['item_menu'] = $this->msg[$v[3]];

                    // only drafts allowed
                    if(in_array($v[2], $drafts)) {
                        $allowed_ = array($v[2] => $allowed);
                        if(AuthPriv::isPrivOptionalStatic('insert', 'draft', $v[2], $allowed_)) {
                            unset($manage[$k]);
                        }
                    }
                }

                if(!empty($manage[$k])) {
                    $delim[$group_key] = true;
                }
            }


            if(!empty($delim[$group_key])) {
                $manage[] = 'delim';
            }
        }


        return $manage;
    }


    function &getTopMenuBlock($top_menu) {

        $tpl = new tplTemplatez($this->getTemplate('block_menu_top.html'));

        $view_format = $this->controller->getSetting('view_format');
        $more_button = ($view_format != 'fixed');
        $more_items = array();

        foreach($top_menu as $k => $v) {
            if ($more_button && !empty($v['more'])) {
                $more_items[] = $v;
                continue;
            }

            $v['class'] = (in_array($this->view_id, $v['views'])) ? 'menuItemSelected' : 'menuItem';
            $v['options'] = (!empty($v['options'])) ? ' ' . $v['options'] : '';
            $tpl->tplParse($v, 'row');
        }

        if (!empty($more_items)) {
            $tpl->tplSetNeeded('/more_button');

            foreach ($more_items as $v) {
                $tpl->tplParse($v, 'more_row');
            }
        }

        if($this->parse_form) {
            $tpl->tplSetNeeded('/form');

            if($view_format == 'left') {
                $sidebar_action_title = $this->msg['hide_sidebar_msg'];
                $next_action_title = $this->msg['show_sidebar_msg'];
                if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 0) {
                    $sidebar_action_title = $this->msg['show_sidebar_msg'];
                    $next_action_title = $this->msg['hide_sidebar_msg'];
                }

                $tpl->tplAssign('action_title', $sidebar_action_title);
                $tpl->tplAssign('next_action_title', $next_action_title);
            }
        }

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getLoginBlock($manager, $manage_menu) {

        require_once 'core/base/Controller.php';
        require_once 'core/app/AppController.php';

        $tpl = new tplTemplatez($this->getTemplate('block_login.html'));

        $msg['logged_msg'] = $this->msg['logged_msg'];
        $msg['logout_msg'] = $this->msg['logout_msg'];
        $msg['admin_view_msg'] = $this->msg['admin_view_msg'];
        $msg['register_msg'] = $this->msg['register_msg'];
        $msg['login_msg'] = $this->msg['login_msg'];

        if($manager->is_registered) {

            $tpl->tplAssign('username', AuthPriv::getUsername());
            $tpl->tplAssign('logout_link', $this->getLink('logout'));
            $tpl->tplAssign('username_link', $this->getLink('member'));
            $tpl->tplAssign('delim', '|');

            if(AuthPriv::getCookie()) {
                $this->msg['logout_msg'] = '';
                $tpl->tplAssign('delim', '');
            }

            if($manager->user_priv_id) {
                $tpl->tplAssign('admin_link', APP_ADMIN_PATH . 'index.php');
                $tpl->tplAssign('admin_path', APP_ADMIN_PATH . 'index.php');
                $tpl->tplSetNeeded('/admin_view');

                if($manage_menu) {

                    $html = array();
                    $action_item_str = '<li><a href="%s" %s>%s</a></li>';
                    $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
                    $divider_str = '<li class="dropdown-divider"></li>';

                    foreach($manage_menu as $k => $v) {

                        if ($v == 'delim') {
                            $html[] = $divider_str;

                        } else {
                            if ($v['item_link']) {
                                $options = (!empty($v['item_options'])) ? $v['item_options'] : '';
                                $html[] = sprintf($action_item_str, $v['item_link'], $options, $v['item_menu']);

                            } else {
                                $html[] = sprintf($disabled_item_str, $v['item_menu']);
                            }
                        }
                    }

                    $tpl->tplAssign('manage_menu', implode("\n", $html));
                    $tpl->tplSetNeeded('/manage');
                }
            }

            // account
            if(AuthPriv::isRemote()) {
                if(AuthPriv::getRemoteUsername() !== null) {
                    $tpl->tplAssign('username', AuthPriv::getRemoteUsername());
                }
            } else {
                $tpl->tplSetNeeded('/account');
            }

            $tpl->tplSetNeeded('/logged');

        } else {

            $n = 0;
            if($manager->getSetting('register_policy')) {
                $n++;
                $tpl->tplAssign('register_link', $this->getLink('register'));
                $tpl->tplSetNeeded('/register_link');
            }

            //should check more to redirect correct after login
            if($manager->getSetting('login_policy') == 1) {
                $n++;
                if($this->view_id == 'index' && $this->category_id) {
                    $link = $this->getLink('login', $this->category_id, $this->category_id, '_' . 'category');

                } elseif(in_array($this->view_id, array('files', 'forums', 'troubles')) && $this->category_id) {
                    $link = $this->getLink('login', $this->category_id, $this->category_id, '_' . $this->view_id);

                } else {
                    $view_id = ($this->view_id == '404') ? '' : '_' . $this->view_id;
                    $link = $this->getLink('login', $this->category_id, $this->entry_id, $view_id);
                }

                $tpl->tplAssign('login_link', $link);
                $tpl->tplSetNeeded('/login_link');
            }

            $tpl->tplAssign('delim', ($n == 2) ? '|' : '');
        }

        // mobile view
        if (isset($_COOKIE['full_view_'])) {
            $link = $this->controller->getFullUrl($_GET, array('mobile' => 1));
            $tpl->tplAssign('mobile_view_link', $link);
            $tpl->tplSetNeeded('/mobile_view_link');

        } elseif ($this->mobile_view) {
            if ($this->controller->view_id == 'category') {
                if (!empty($_GET['filter'])) {
                    unset($_GET['filter']);
                }
            }

            $link = $this->controller->getFullUrl($_GET, array('mobile' => 0));
            $tpl->tplAssign('full_view_link', $link);
        }

        // pool
        if($manager->getSetting('show_pool_link')) {
            $tpl->tplSetNeeded('/pool_link');
            $tpl->tplAssign('pool_link', $this->getLink('pool'));

            $display_pool = 'none';
            $ids = $this->getUserPool('pool');
            if (count($ids) > 0) {
                $display_pool = ($this->mobile_view) ? 'block' : 'inline';
                $tpl->tplAssign('pool_num', count($ids));
            }

            $tpl->tplAssign('display_pool', $display_pool);
        }

        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }


    function getUserPool($name) {
        $cookie_name = sprintf('kb_%s_', $name);
        $ids = array();
        if (isset($_COOKIE[$cookie_name])) {
            if (!preg_match('#^\[\d+(,\d+)*]$#', $_COOKIE[$cookie_name])) {
                $_COOKIE[$cookie_name] = array();
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', time() - 3600, '/');

            } else {
                $ids = substr($_COOKIE[$cookie_name], 1, -1);
                $ids = explode(',', $ids);
            }
        }

        return $ids;
    }


    function _getFormatedNavigation(&$manager, $article_name = false) {

        // skip category generate on some vies or if no categories (one only)
        if($this->category_nav_generate && $this->display_categories) {
            $arr = TreeHelperUtil::getParentsById($manager->categories, $this->category_id, 'name');
            $arr = $this->stripVars($arr);
            $manager->categories_parent = $arr;
        } else {
            $arr = array();
            if($this->category_id) {
                $this->home_link = true;
            }
        }

        $view = 'index';
        $arr1 = array();
        $files = in_array($this->view_id, $this->files_views);
        $trouble = in_array($this->view_id, $this->trouble_views);
        $forum = in_array($this->view_id, array('forums', 'topic'));
        $str = '<a href="%s" class="navigation">%s</a>';

        // extra
        $nav_extra = $this->controller->getSetting('nav_extra');
        if($nav_extra) {
            foreach(explode('||', $nav_extra) as $v) {
                list($title, $link) = explode('|', $v);
                $arr1[] = sprintf($str, trim($link), trim($title));
            }
        }

        if($arr || $this->home_link || $files || $trouble || $forum) {
            $arr1[] = sprintf($str, $this->getLink(), $this->controller->getSetting('nav_title'));
            if($files) {
                $view = 'files';
                if($this->view_id == 'fsearch' || $this->category_id) {
                    $arr1[] = sprintf($str, $this->getLink('files'), $this->msg['menu_file_msg']);
                } else {
                    $arr1[] = $this->msg['menu_file_msg'];
                }
            }

            if($trouble) {
                $view = 'troubles';
                if($this->category_id) {
                    $arr1[] = sprintf($str, $this->getLink('troubles'), $this->msg['menu_trouble_msg']);
                } else {
                    $arr1[] = $this->msg['menu_trouble_msg'];
                }
            }

            if ($forum) {
                $view = 'forums';
                if($this->category_id) {
                    $arr1[] = sprintf($str, $this->getLink('forums'), $this->msg['menu_forum_msg']);

                } else {
                    $arr1[] = $this->msg['menu_forum_msg'];
                }
            }

        } else {
            $arr1[] = $this->controller->getSetting('nav_title');
        }


        // add article title if have it
        if($article_name !== false) {
            $article_name = (is_array($article_name)) ? $article_name : array($article_name);
            foreach($article_name as $k => $v) {
                $arr[$k] = $v;
            }
        }


        $num = count($arr);
        $i = 1;
        foreach($arr as $k => $v) {
            if($num != $i) {
                //$k = (is_numeric($k)) ? $this->getLink($view, $k, false) : $k; // link to category
                if(is_numeric($k)) { // link to category
                    $cat_id = $this->controller->getEntryLinkParams($k, $v);
                    $k = $this->getLink($view, $cat_id); 
                }
                
                $arr1[] = sprintf($str, $k, $v);
            } else {
                $arr1[] = $v;
            }

            $i++;
        }

        $span1 = '<span class="navigation">';
        $span2 = '</span>';
        $delim = $span2. $this->nav_delim . $span1;

        return $span1 . implode($delim, $arr1) . $span2;
    }


    function getRatingBlock($manager, $data) {

        $ratingable = $this->isRatingable($manager, $data['ratingable']);
        $comentable = ($manager->getSetting('allow_rating_comment'));
        $rating_text = ($manager->getSetting('rating_type') == 1);

        if(!$ratingable && !$comentable) {
            return;
        }


        $tpl = new tplTemplatez($this->getTemplate('block_rating.html'));

        if($manager->isUserVoted($data['id']) === false || @$_POST['xajax'] == 'doRateFeedback') {

            if($ratingable) {
                $tpl->tplSetNeededGlobal('show_rating_option');
            }

            if($manager->getSetting('allow_rating_comment')) {
                $class = ($ratingable) ? 'fright' : 'fleft';
                $tpl->tplAssign('rating_comment_class', $class);

                $tpl->tplSetNeededGlobal('show_rating_comment');
            }

            if($rating_text) {
                $row_block = 'rating_row';
                $range = AppMsg::getMsg('ranges_msg.ini', 'public', 'rating');
            } else {
                $row_block = 'rating_row2';
                $range = AppMsg::getMsg('ranges_msg.ini', 'public', 'rating2');
                $range = array_reverse($range, true);
            }

            $cr = count($range); $i = 1;
            foreach($range as $k => $v) {
                $a['rate_value'] = $k;
                $a['rate_item'] = $v;
                $a['delim'] = ($cr > $i++) ? ' | ' : '';
                $tpl->tplParse($a, $row_block);
            }

            //xajax
            $ajax = &$this->getAjax('entry');
            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('doRate', $ajax, 'doRateResponse'));
            $xajax->registerFunction(array('doRateFeedback', $ajax, 'doRateFeedbackResponse'));

            $tpl->tplAssign('form_action', $this->getLink('all'));

        } else {

            // $tpl->tplAssign('rating_percent', ceil($data['rating'] * 10) . '%');
            // $tpl->tplAssign('rating', $this->_getRating($data['rating']));
            // $tpl->tplAssign('votes', ($data['votes']) ? $data['votes'] : 0);

			// current raiting
                //             if($data['rating'] && $rating_text) {
                //
                // if($data['rating']/$data['votes'] != 1) { // all rates "not usefull" no or "1" star = 1
                //     $raiting = ceil($data['rating'] * 10) . '%';
                //     $current_rating = AppMsg::replaceParse($this->msg['current_rating_msg'], array('percent'=>$raiting));
                //     $tpl->tplAssign('current_rating', $current_rating);
                // }
                //             }

            $tpl->tplSetNeeded('/show_rating');
        }


        $title = ($manager->entry_type == 1) ? $this->msg['add_rate2_msg'] : $this->msg['add_rate2_trouble_msg'];
        $tpl->tplAssign('title', $title);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // is entry private read
    function isPrivateEntry($entry, $category) {
        return (in_array($entry, array(1,3)) || in_array($category, array(1,3)));
    }


    function &getAjax($view) {
        require_once $this->controller->kb_dir . 'client/inc/KBClientAjax.php';
        $ajax = &KBClientAjax::factory($view);

        $ajax->view = $this;

        $this->ajax_call = true;
        return $ajax;
    }


    function runAjax() {
        if($this->ajax_call) {
            require_once $this->controller->kb_dir . 'client/inc/KBClientAjax.php';
            return KBClientAjax::processRequests(false, $this->ajax_post_action);
        }
    }


    // CKEditor // --------------------

    function getEditor($value, $cfile, $fname = 'body', $cconfig = array()) {

        require_once APP_ADMIN_DIR . 'tools/ckeditor_custom/ckeditor.php';

        $admin_path = $this->controller->getAdminJsLink();

        $config_file = array(
          'article' => 'ckconfig_article.js',
          'forum'   => 'ckconfig_forum.js'
        );

        $CKEditor = new CKEditor();
        $CKEditor->returnOutput = true;
        $CKEditor->basePath = $admin_path . 'tools/ckeditor/';

        $config = array();
        $config['customConfig'] = $admin_path . 'tools/ckeditor_custom/' . $config_file[$cfile];

        foreach($cconfig as $k => $v) {
            $config[$k] = $v;
        }

        $events = array();
        // $events['instanceReady'] = 'function (ev) {
        //     alert("Loaded: " + ev.editor.name);
        // }';

        return $CKEditor->editor($fname, $value, $config, $events);
    }


    // Custom fields // ---------------

    function getCustomData($rows, $bottom_as_inline = false) {

        $data = array(1 => array(), 2 => array(), 3 => array());
        if(!$rows) {
            return $data;
        }

        require_once 'core/common/CommonCustomFieldModel.php';
        require_once 'core/common/CommonCustomFieldView.php';

        $custom_data = array();
        foreach($rows as $field_id => $v) {
            $custom_data[$field_id] = $v['data'];
        }

        $ch_value = $this->getCustomDataCheckboxValue();
        $cf_manager = new CommonCustomFieldModel();
        $custom = CommonCustomFieldView::getCustomData($custom_data, $cf_manager, $ch_value);

        foreach ($custom as $field_id => $v) {
            $row = $rows[$field_id];

            if($row['display'] == 3 && !$bottom_as_inline) {
                $template = array('title' => $v['title'], 'value' => $v['value']);

            } else {
                if(empty($row['html_template'])) {
                    $row['html_template'] = '{title}: {value}';
                }

                $r = array('{title}' => $v['title'], '{value}' => $v['value']);
                $template = strtr($row['html_template'], $r);
            }

            $data[$row['display']][$field_id] = $template;
        }

        return $data;
    }


    function getCustomDataCheckboxValue() {
        return 'checkbox';
    }


    function parseCustomData($data, $position) {
        $ret = '';
        if($data) {
            $data = DocumentParser::parseCurlyBracesSimple($data);

            $html = array();
            foreach ($data as $custom_id => $str) {
                $html[] = sprintf('<div id="custom_block_%s" class="customFieldItem">%s</div>', $custom_id, $str);
            }

            return implode('', $html);
        }

        return $ret;
    }


    function getTagsArray($tags, $search_in) {
        $rows = array();
        foreach ($tags as $tag_id => $title) {
            $more = array('s' => 1, 'q' => $title, 'in' => $search_in, 'by' => 'keyword');
            $rows[$tag_id]['link'] = $this->getLink('search', false, false, false, $more);
            $rows[$tag_id]['title'] = $title;
        }

        return $rows;
    }


    function convertRequiredMsg($msg) {
        return array();
    }


    // share link

    function getShareLinkBlockHtml($manager, $share_msg, $link_msg) {

        $items = $manager->getSetting('item_share_link');

        $tpl = new tplTemplatez($this->getTemplate('block_share_link.html'));

        $tpl->tplAssign('caption', $share_msg);
        $tpl->tplAssign('link_desc', $link_msg);

        foreach(explode("\n", $items) as $v) {
            @list($title, $url, $icon) = explode('|', $v);

            $row['title'] = $this->stripVars(trim($title));
            $row['url'] = $url;

            $icon = trim($icon);
            $row['icon'] = (!empty($icon)) ? str_replace('[size]', '24x24', $icon) : '{client_href}images/icons/share.svg';

            $tpl->tplParse($row, 'item');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // validation

    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        $ret['options'] = array($values);

        $fct = new ReflectionMethod(get_class($this), 'validate');
        if($fct->getNumberOfParameters() > 1) {
            $ret['options'][] = 'manager';
        }

        // return AppView::ajaxValidateForm($func, $options);
        return $ret;
    }

}
?>