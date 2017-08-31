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

require_once 'eleontev/Dir/ImageResize.php'; 


class KBClientView_common extends KBClientView
{
    
    var $own_format = 'none';
    var $default_format = 'default';
    var $view_template = array(
        'page.html', 'page_in.html', '404.html',
        'block_menu_top.html', 'block_login.html',
        'register_form.html', 'login_form.html', 'login_saml_form.html',
        'top_article_format.html', 'contact_form.html',
        'search_form.html', 'search_list.html', 
        'block_search.html', 'browse_category.html',
        'comment_list.html', 'comment_form.html',
        'send_form.html', 'article_block_bb.html',
        'password_form.html', 'password_reset_form.html', 
        'category_list.html', 'article_list.html',
        'news_list.html', 'file_list.html',
        'article_stuff_other.html', 'article_stuff_attachment.html',
        'article_stuff_tag.html', 'article_stuff_custom.html', 'block_rating.html',
        'rss_list.html', 'glossary_list.html',
        'download.html', 'pool_list.html',
        'article_list_faq.html', 'member_tmpl.html',
        'member_home.html', 'block_tab_menu.html',
        'member_password.html', 'member_api.html',
        'block_list_option.html', 'tags_list.html',
        'news_entry_block.html', 'success_go.html',
        'forum_list_forum.html', 'forum_list_forum_sections.html', 
        'forum_list_topic.html', 'forum_list_message.html',
        'forum_entry_stuff_manage.html', 'forum_form_message.html',
        'block_share_link.html'
        );
        
    var $ajax_post_action = 'ajaxPostAction';
    var $mobile_view = true;
    
    
    // reassigned // --------------------
    
    function getPageByPageObj($class, $limit = false, $hidden = false, $action_page = false) {
        
        $msg = array(
            $this->msg['page_msg'], 
            $this->msg['record_msg'], 
            $this->msg['record_from_msg'],
            $this->msg['prev_msg'],
            $this->msg['next_msg']
        );

        $bp = PageByPage::factory('mobile', $limit, $hidden, $action_page);
        $bp->setMsg($msg);
        
        return $bp;
    }
    
    
    function pageByPageBottom($bp) {
        return PageByPage::factory('mobile', $bp);
    }
    
    
    function getCopyrightInfoMsg() {
        $str = '<a href="%s" title="%s" target="_blank" class="pull-left">Powered by %s</a>';
        return sprintf($str, $this->conf['product_www'], $this->conf['product_name'], $this->conf['product_name']);
    }
    
    
    function convertRequiredMsg($keys) {
        $str = '<span style="white-space: nowrap;"><span class="requiredSign">* </span>%s</span>';
        $data = array();
        
        foreach ($keys as $key) {
            $words = explode(' ', $this->msg[$key]);
            $nowrap_part = substr($words[0], 0, 3);
            $wrap_part = substr($words[0], 3);
            $words[0] = $wrap_part;
            
            $data[$key] = sprintf($str, $nowrap_part) . implode(' ', $words);
        }
        return $data;
    }
    
    
    function getCustomDataCheckboxValue() {
        return 'image';
    }
    
    
    function getMoreLink($view_id) {
        if ($this->category_id) {
            $link = $this->getLink($view_id, $this->category_id);

        } else {
            $link = $this->getLink($view_id);
        }

        return $link;
    }
    
}
?>