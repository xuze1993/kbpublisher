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

// it is old menu, should be depricated left for compability
// in v6.0 by default use ajax menu like in fixed view


class KBClientMenu2  extends KBClientMenu
{

    var $tree_menu_limit = 25; // num items (entries) shown in menu
    var $entry_menu_max_len = 0; // nums signs to leave in menu items, set to 0 to disable cut off

    var $parse_category_select = false; // no need in category select, save some generatin time

    // how to parse entries (not categories (folders))
    // entry    - display entry items for category where current entry open
    // category - display entry items for category where current entry open or category open
    // all      - show all tree all the time
    var $tree_entry_display = 'entry'; // entry, category, all

    var $utf_replace = true; // replace bad sign to ? to avoid error with ajax


    // to parse single items, mostly category entry
    function stripVarJs($str) {
        if($this->utf_replace) {
            $str = $this->replaceBadUtf8String($str);
        }

        return $this->view->jsEscapeString($this->view->stripVars($str));
    }


    function callLeftMenuAjax($manager, $type) {

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);

        if ($type == 'followon') {
            $xajax->registerFunction(array('getAllFollowEntries', $ajax, 'getAllFollowEntries'));

        } else {
            $xajax->registerFunction(array('getAllTreeEntries', $ajax, 'getAllTreeEntries'));
        }
    }
}


class KBCLientMenu_entry2  extends KBClientMenu2
{

    function &getTreeMenu(&$manager, $parent_id = 0, $is_write = true, $is_top = false) {

        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('tree_menu.html', $template_dir));

        $str = $this->getTreeJavascript($manager, $parent_id, $is_write, $is_top);

        $tpl->tplAssign('client_href', $this->view->controller->client_path);
        $tpl->tplAssign('js_tree', $str);

        $this->callLeftMenuAjax($manager, 'tree');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function &getTopTreeMenu(&$manager) {

        $max_len = $this->entry_menu_max_len;
        $view_id = $this->getViewIdIndex();

        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('tree_top_menu.html', $template_dir));

        $tpl->tplAssign('menu_title_top', $this->stripVarStatic($this->view->msg['menu_title_msg']));
        $tpl->tplAssign('menu_link_top', $this->view->getLink($view_id));

        // top category menu
        if($this->category_id) {
            $block = $this->parseTopCategyJsMenu($manager->categories);
            $tpl->tplAssign('top_category_menu_block', $block);
        }

        if($this->category_id) {
            $top_category_id = TreeHelperUtil::getTopParent($manager->categories, $this->category_id);
            $tpl->tplAssign('js_tree', $this->getTreeMenu($manager, $top_category_id, true, true));

        } else {

            foreach(array_keys($manager->categories) as $cat_id) {
                $v = $manager->categories[$cat_id];
                if($v['parent_id'] != 0) {
                    continue;
                }

                $title = $v['name'];
                $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
                $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);

                // stripVarStatic enough here, dispalying category listing, no js
                $v['title'] = $this->stripVarStatic($title);
                $v['short_title'] = $this->stripVarStatic($short_title);
                $v['menu_link'] = $this->view->getLink($view_id, $cat_id_params);

                $private = $this->view->isPrivateEntry(false, $v['private']);
                $v['item_img'] = $this->view->_getItemImg($manager->is_registered, $private, true);

                $tpl->tplParse($v, 'row');
            }

            $tpl->tplSetNeeded('/top_category');
        }

        $this->callLeftMenuAjax($manager, 'tree_top');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getTreeJavascript($manager, $parent_id = 0, $is_write = true, $is_top = false) {

        $max_len = $this->entry_menu_max_len;
        $rows = &$manager->categories;
        $view_id = $this->getViewIdIndex();

        // here we genearte tree_helper once, for all views it is possible by &$manager ???
        $tree_helper = &$manager->getTreeHelperArray($rows, $parent_id);

        // set kb_path to '' for generation shorter js, restore it later
        $link_path = $this->view->controller->link_path;
        $this->view->controller->link_path = '';


        $str[] = sprintf("var img_path  = '%sjscript/dtree/';", $this->view->controller->client_path);
        $str[] = sprintf("var icon_path = '%simages/icons/';", $this->view->controller->client_path);
        $str[] = sprintf("var basehref  = '%s';", $this->view->controller->kb_path);

        $str[] = "d = new dTree('d');";
        $str[] = "d.config.useCookies = false;";
        //$str[] = "d.config.useStatusText = true;";
        //$str[] = "d.config.closeSameLevel = true;";

        // if parent nodes are always added before children, setting this to true speeds up the tree
        $str[] = "d.config.inOrder = true;";

		$item_img = $this->view->_getItemImg($manager->is_registered, false, 'home', false, true, '');
        $str[] = "d.icon.root=icon_path+'{$item_img}'";
        $str[] = "d.icon.root=icon_path+'{$item_img}'";

        if($parent_id == 0) {

            $title = $this->stripVarJs($this->view->msg['menu_title_msg']);

            $str1 = "d.add(0,-1,' <b>%s</b>', basehref+'%s');";
            $str[] = sprintf($str1, $title, $this->view->getLink($view_id));
			// echo '<pre>', print_r($str,1), '<pre>';

        } else {

            $title = $rows[$parent_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $title = $this->stripVarJs($title);
            $short_title = $this->stripVarJs($short_title);

            $cat_id_params = $this->view->controller->getEntryLinkParams($parent_id, $title);
            $entry_link = $this->view->getLink($view_id, $cat_id_params);

            $str1 = "d.add(%d, %d, ' <b>%s</b>', basehref+'%s', '%s');";
            $str[] = sprintf($str1, $parent_id, '-1',  $short_title, $entry_link, $title);

            // for top level articles
            if($view_id == 'index' &&
                strpos($manager->getCategoryType($this->view->category_id), 'faq') === false) {

                $str[] = $this->_getTreeEntriesItems($manager, $rows, $parent_id, $max_len, $is_top, -1);

            } elseif($view_id == 'troubles') {
                $str[] = $this->_getTreeEntriesItems($manager, $rows, $parent_id, $max_len, $is_top, -1);
            }
        }

        foreach($tree_helper as $cat_id => $level) {

            $parent_id = $rows[$cat_id]['parent_id'];

            $title = $rows[$cat_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $title = $this->stripVarJs($title);
            $short_title = $this->stripVarJs($short_title);

            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);
            $entry_link = $this->view->getLink($view_id, $cat_id_params);

            // return just image name
            $private = $this->view->isPrivateEntry(false, $rows[$cat_id]['private']);
            $item_img = $this->view->_getItemImg($manager->is_registered, $private, true, false, true, '');

            $str1 = "d.add(%d, %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $cat_id, $parent_id, $short_title, $entry_link, $title,
                                $item_img, $item_img);


            if($this->tree_entry_display == 'entry') {
                if($this->entry_id && $cat_id == $this->category_id) {
                    $str[] = $this->_getTreeEntriesItems($manager, $rows, $cat_id, $max_len, $is_top);
                }

            } elseif($this->tree_entry_display == 'category') {
                if($cat_id == $this->category_id) {
                   $str[] = $this->_getTreeEntriesItems($manager, $rows, $cat_id, $max_len, $is_top);
                }

            } elseif($this->tree_entry_display == 'all') {
                $str[] = $this->_getTreeEntriesItems($manager, $rows, $cat_id, $max_len, $is_top);
            }
        }

        if ($is_write) {
            $str[] = "document.write(d);";
        }


        $str = implode("\n", $str);


        if($this->category_id) {
            $str .= "d.openTo($this->category_id, false);";
        }

        if(!$this->entry_id && $this->category_id) {
            $parent_id = $manager->categories[$this->category_id]['parent_id'];
            $search = "#(d\.add\($this->category_id, $parent_id), '(.*?)', basehref.*?#";
            $str = preg_replace($search, "$1, ' <span class=\"treeNodeSelected\">$2</span>', ", $str, 1);

        } elseif($this->entry_id) {
            $search = "#(d\.add\('e$this->entry_id', $this->category_id), '(.*?)', basehref.*?#";
            $str = preg_replace($search, "$1, ' <span class=\"treeNodeSelected\">$2</span>', ", $str, 1);
        }

         // assign back kb_path
        $this->view->controller->link_path = $link_path;

        return $str;
    }


    function _getTreeEntriesItems($manager, $rows, $cat_id, $max_len, $is_top = false, $limit = false) {

        $view_id = $this->getViewIdIndex();
        $view_id_enttry = $this->getViewIdEntry();

        $str = array();
        $sort = $manager->getSortOrder();
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);

        // getCategoryEntries($category_id, $entry_id, $limit = -1, $offset = 0) {
        $entries = $manager->getCategoryEntries($cat_id, 0);

        // limit entries
        if(!$limit) {
            $limit = $this->tree_menu_limit;
        }

        $pos = 0;
        $show_all_button = false;

        if ($limit != -1 && count($entries) > $limit) {
            foreach(array_keys($entries) as $key) {
                if ($entries[$key]['id'] == $this->entry_id) {
                    $pos = $key;
                    break;
                }
            }

            $show_all_button = true;
            $offset = $pos - ceil($limit / 2);
            $offset = ($offset < 0) ? 0 : $offset;

            $entries = array_slice($entries, $offset, $limit, true);

            if($offset != 0) {
                $str[] = "d.add('more', $cat_id, '...');";
            }
        }

        // strip entries
        $this->replaceBadUtf8($entries, array('title'));
        $entries = $this->view->stripVars($entries);

        foreach(array_keys($entries) as $k1) {
            $v1 = $entries[$k1];

            $entry_id_param = $this->view->controller->getEntryLinkParams(
                                $v1['entry_id'], $v1['title'], $v1['url_title']);
            $entry_link = $this->view->getLink($view_id_enttry, $cat_id, $entry_id_param);

            $title = $v1['title'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $title = $this->view->jsEscapeString($title);
            $short_title = $this->view->jsEscapeString($short_title);

            $private = $this->view->isPrivateEntry($v1['private'], $rows[$cat_id]['private']);
            $item_img = $this->view->_getItemImg($manager->is_registered, $private, 'article', false, true, '');

            $str1 = "d.add('e%d', %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $v1['entry_id'], $cat_id, $short_title, $entry_link, $title,
                                $item_img, $item_img);
        }

        // show more link
        if($show_all_button) {

            $mode = ($is_top) ? 'top' : 'all';

            $show_block = array();
            $show_block[] = '<span id="show_all_load" style="display: none;">';
            $show_block[] = '<img id="show_img" src="%simages/ajax/indicator.gif" alt="Loading" />';
            $show_block[] = '</span><span id="show_all_link">Show all...</span>';

            $show_block = implode('', $show_block);
            $show_block = sprintf($show_block, $this->view->controller->client_path);

            $show_link = "javascript:(function(){showAllTree(\'$mode\');})();";

            $str2 = "d.add('%s', '%d', '%s', '%s', '', '', icon_path + 'arrow_menu.gif')";
            $str[] = sprintf($str2, 'show_all', $cat_id, $show_block, $show_link);
        }

        return implode("\n", $str);
    }


    function &getFollowMenu(&$manager) {

        $max_len = $this->entry_menu_max_len;
        $limit = $this->tree_menu_limit;
        $rows = &$manager->categories;
        $view_id = $this->getViewIdIndex();
        $view_id_enttry = $this->getViewIdEntry();

        $parents = array();
        $childs = array();

        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('followup_menu.html', $template_dir));

        // set parent id
        $parent_id = $this->view->top_parent_id;
        if($this->category_id) {
            $parent_id = $manager->categories[$this->category_id]['parent_id']; // 1 behaviour
        }

        // top category menu
        if($this->category_id) {
            $block = $this->parseTopCategyJsMenu($manager->categories);
            $tpl->tplAssign('top_category_menu_block', $block);
        }

        foreach(array_keys($manager->categories) as $cat_id) {
            if($manager->categories[$cat_id]['parent_id'] == $this->category_id) {
                $childs[$cat_id] = $cat_id;
            }
        }

        if($this->category_id) {
            $parents = array_keys($manager->categories_parent);
            if(!$parents) {
                $parents = TreeHelperUtil::getParentsById($rows, $this->category_id);
            }
        }

        // echo '<pre>parents: ', print_r($parents, 1), '</pre>';
        // echo '<pre>childs: ', print_r($childs, 1), '</pre>';
        // echo '<pre>levels: ', print_r($levels, 1), '</pre>';
        // echo '<pre>categories: ', print_r($rows, 1), '</pre>';

        foreach(array_merge($parents, $childs) as $cat_id) {

            $a['entry_class'] = ($cat_id == $this->category_id) ? 'followMenuSelected' : 'followMenu';

            if(in_array($cat_id, $parents)) {
                $a['entry_class'] = 'followMenuUp';
            }

            if(in_array($cat_id, $childs)) {
                $icon = true;
            } else {
                $icon = ($this->category_id && $this->category_id != $cat_id) ? 'up' : true;
            }

            $private = $this->view->isPrivateEntry(false, $rows[$cat_id]['private']);
            $a['item_img'] = $this->view->_getItemImg($manager->is_registered, $private, $icon);

            $title = $rows[$cat_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);
            $a['entry_link'] = $this->view->getLink($view_id, $cat_id_params);

            $a['title'] = $this->stripVarStatic($title);
            $a['short_title'] = $this->stripVarStatic($short_title);

            $tpl->tplParse($a, 'category_row');
        }


        $tpl->tplAssign('menu_title', $this->view->msg['menu_title_msg']);
        $tpl->tplAssign('menu_title_link', $this->view->getLink($view_id));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}



class KBClientMenu_news2  extends KBClientMenu2
{

    function &getTreeMenu($manager) {

        $view_id = 'news';

        $year_range = $manager->getNewsYears();
        rsort($year_range);
        $year_range = array_slice($year_range, 0, 100);


        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('tree_menu.html', $template_dir));


        $str[] = "var img_path  = '".$this->view->controller->client_path . "jscript/dtree/';";
        $str[] = "var icon_path = '".$this->view->controller->client_path . "images/icons/';";
        $str[] = "var basehref = '".$this->view->controller->kb_path."';";

        $str[] = "d = new dTree('d');";
        $str[] = "d.config.useCookies = false;";
        //$str[] = "d.config.closeSameLevel = true;";
        $str[] = "d.config.inOrder = true;"; //If parent nodes are always added before children, setting this to true speeds up the tree


        // set kb_path to '' for generation shorter js, restore it later
        $link_path = $this->view->controller->link_path;
        $this->view->controller->link_path = '';

        $str1 = "d.add(0,-1,' <b>%s</b>', basehref+'%s');";
        $str[] = sprintf($str1, addslashes($this->view->msg['menu_news_msg']), $this->view->getLink($view_id));

        foreach($year_range as $k => $year) {

            $entry_link = $this->view->getLink($view_id, $year);
            $title = $year;
            $category_link = $this->view->getLink('news', $year);
            $category_link = $this->view->parseCategoryLink($category_link);

            // return just image name
            $item_img = $this->view->_getItemImg($manager->is_registered, false, true, false, true, '');

            $str1 = "d.add(%d, %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $year, 0, $title, $category_link, $title, $item_img, $item_img);

            if($this->entry_id) {
                $year2 = $manager->getYearByEntryId($this->entry_id);
                if($year2 == $year) {
                    $str[] = $this->getTreeEntriesItemsNews($manager, $year);
                }
            }
        }

        $str[] = "document.write(d);";
        $str = implode("\n", $str);

        //if($view->category_id) {
        //    $str .= "d.openTo($view->category_id, false);";
        //}

        if(!$this->entry_id && $this->category_id) {
            $search = "#(d\.add\($this->category_id, 0), '(.*?)',.*?#";
            $str = preg_replace($search, "$1, '<span class=\"treeNodeSelected\">$2</span>', ", $str, 1);

        } elseif($this->entry_id) {
            if(!isset($year2)) {
                $year2 = $manager->getYearByEntryId($this->entry_id);
            }
            $str .= "d.openTo({$year2}, false);";

            $search = "#(d\.add\('e{$this->entry_id}', $year2), '(.*?)',.*?#";
            $str = preg_replace($search, "$1, '<span class=\"treeNodeSelected\">$2</span>', ", $str, 1);
        }

        // assign back kb_path
        $this->view->controller->link_path = $link_path;

        $tpl->tplAssign('js_tree', $str);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getTreeEntriesItemsNews($manager, $year) {

        $max_len = $this->entry_menu_max_len;

        $str = array();
        $entries = $this->view->stripVars($manager->getNewsByYear($year));

        foreach(array_keys($entries) as $k) {
            $v = $entries[$k];
            $entry_id = $entries[$k]['id'];

            $entry_id_param = $this->view->controller->getEntryLinkParams($entry_id, $v['title'], false);
            $entry_link = $this->view->getLink('news', false, $entry_id_param);

            $title = $v['title'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $title = addslashes($title);
            $short_title = addslashes($short_title);

            $private = $this->view->isPrivateEntry($v['private'], false);
            $item_img = $this->view->_getItemImg($manager->is_registered, $private, 'news', false, true, '');

            $str1 = "d.add('e%d', %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $entry_id, $year, $short_title, $entry_link, $title, $item_img, $item_img);
        }

        return implode("\n", $str);
    }

}

/*
id      Number      Unique identity number.
pid     Number     Number refering to the parent node. The value for the root node has to be -1.
name     String     Text label for the node.
url     String     Url for the node.
title     String     Title for the node.
targe     String     Target for the node.
icon     String     Image file to use as the icon. Uses default if not specified.
iconO    String     Image file to use as the open icon. Uses default if not specified.
open     Boolean     Is the node open.
*/

?>