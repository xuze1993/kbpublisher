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

class KBClientMenu
{

    var $utf_replace = true; // replace bad sign to ? to avoid error with ajax
    var $entry_menu_max_len = 50; // nums signs to leave in menu items, set to 0 to disable cut off


    function __construct(&$view) {

        $this->view =& $view;

        $this->view_id = $view->view_id;
        $this->entry_id = $view->entry_id;
        $this->category_id = $view->category_id;

        $this->kb_path = $view->controller->kb_path;

        $this->loadUtf8Lib();
    }


    function parseTopCategyJsMenu($categories, $parse_dropdown = true) {

        $view_id = $this->getViewIdIndex();
        $top_cats = array_filter($categories, array($this, 'array_filter_callback_top_cats'));

        $tpl = new tplTemplatez($this->view->getTemplate('block_top_cat_menu.html'));

        if($parse_dropdown) {
            $tpl->tplSetNeeded('/dropdown');
        }

        foreach(array_keys($top_cats) as $cat_id) {
            $v = array();
            $v['name'] = $top_cats[$cat_id]['name'];

            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $v['name']);
            $v['link'] = $this->view->getLink($view_id, $cat_id_params);

            @$i++;
            if($i > 15) {
                $v['name'] = '...';
                $v['link'] = $this->view->getLink('index');
                $tpl->tplParse($v, 'row');
                break;
            }

            $tpl->tplParse($v, 'row');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function array_filter_callback_top_cats($v) {
        return $v['parent_id'] == 0;
    }

    // UTILS // ----------------

    function getViewIdIndex() {
        $view_id = (in_array($this->view_id, $this->view->files_views)) ? 'files' : 'index';
        $view_id = (in_array($this->view_id, $this->view->trouble_views)) ? 'troubles' : $view_id;
        return $view_id;
    }

    function getViewIdEntry() {
        $view_id = (in_array($this->view_id, $this->view->files_views)) ? 'files' : 'entry';
        $view_id = (in_array($this->view_id, $this->view->trouble_views)) ? 'trouble' : $view_id;
        return $view_id;
    }


    // to parse single items, mostly category entry
    function stripVarStatic($str) {
        if($this->utf_replace) {
            $str = $this->replaceBadUtf8String($str);
        }

        return $this->view->stripVars($str);
    }


    function loadUtf8Lib() {

        if(strtolower($this->view->encoding) != 'utf-8') {
            $this->utf_replace = false;
        }

        // only in ajax
        if(empty($_GET['ajax'])) {
            $this->utf_replace = false;
        }

        if($this->utf_replace) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';
        }
    }


    function replaceBadUtf8(&$arr, $parse_keys = array()) {
        if($this->utf_replace) {
            foreach(array_keys($arr) as $k) {
                if(is_array($arr[$k])) {
                    $this->replaceBadUtf8($arr[$k], $parse_keys);
                } else {
                    if(in_array($k, $parse_keys)) {
                        $arr[$k] = $this->replaceBadUtf8String($arr[$k]);
                    }
                }
            }
        }
    }


    function replaceBadUtf8String($str) {
        if(!utf8_compliant($str)) {
            $str = utf8_bad_replace($str, '?');
        }

        return $str;
    }


    function getIcon($name, $attributes = '') {
        $str = '<img src="%sclient/images/icons/%s.gif" %s />';
        return sprintf($str, $this->kb_path, $name, $attributes);
    }
}



class KBClientMenu_entry extends KBClientMenu
{

    var $parents = array();
    var $parse_category_select = false;


    function &getLeftMenu($manager, $parse_top_cat_menu = false) {

        $max_len = $this->entry_menu_max_len;

        $template_dir = $this->view->getTemplateDir('fixed', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('sidebar.html', $template_dir));

        $tpl->tplSetNeeded('/menu_title');
        $tpl->tplAssign('menu_title', $this->view->msg['menu_title_msg']);

        $view_id = $this->getViewIdIndex();
        $tpl->tplAssign('menu_title_link', $this->view->getLink($view_id));

        // top category menu
        if($this->category_id && $manager->getSetting('view_menu_type') == 'top_tree') {
            $tpl->tplSetNeeded('/top_category_menu');

            // when in left menu view
            if($parse_top_cat_menu) {
                $block = $this->parseTopCategyJsMenu($manager->categories, false);
                $tpl->tplAssign('top_category_menu_block', $block);
            }
        }

        $this->setVars($manager);

        $top_ids = array();
        foreach($this->tree as $id => $level) {
            if($level == 0) {
                $top_ids[] = $id;
            }
        }

        $parent_id = false;
        if($this->category_id) {
            $this->parents = array_keys($manager->categories_parent);
            $parent_id = $this->parents[0];
        }

        foreach($top_ids as $cat_id) {

            if($manager->getSetting('view_menu_type') == 'top_tree') {
                if (!empty($parent_id) && ($cat_id != $parent_id)) {
                    continue;
                }
            }

            $title = $manager->categories[$cat_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);

            $v = array();
            $v['id'] = $cat_id;
            $v['title'] = $this->stripVarStatic($title);
            $v['short_title'] = $this->stripVarStatic($short_title);
            $v['link'] = $this->view->getLink($this->view_id, $cat_id_params);
            $v['padding'] = 7;
            $v['item_class'] = (($cat_id == $this->category_id) && !$this->entry_id) ? 'menu_item_selected' : '';

            $v['block_class'] = ($cat_id == $parent_id) ? 'category_loaded' : '';
            $v['icon'] = ($cat_id == $parent_id) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $private = $this->view->isPrivateEntry(false, $manager->categories[$cat_id]['private']);
            if ($private && !$manager->is_registered) {
                $v['icon_str'] = $this->getIcon('menu_category_collapsed_private');

            } else {
                $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
                $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
                $v['icon_str'] = $this->getIcon($v['icon'], $attributes);
            }

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            if ($cat_id == $parent_id) { // this top category is expanded
                $this->parseSubtree($tpl, $manager, $cat_id);
            }
        }

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getCategoryChildren', $ajax, 'getCategoryChildren'));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function setVars($manager) {
        $this->view_id = $this->getViewIdIndex();
        $this->tree = $manager->getTreeHelperArray($manager->categories);

        $this->tree2 = new TreeHelper();
        foreach($manager->categories as $k => $row) {
            $this->tree2->setTreeItem($row['id'], $row['parent_id']);
        }
    }


    function parseSubtree($tpl, $manager, $root_id) {

        $max_len = $this->entry_menu_max_len;

        $all_children = $this->tree2->getChildsById($root_id);

        // category's entries
        $padding = ($this->tree[$root_id] + 2) * 8;
        $this->parseEntries($tpl, $manager, $root_id, $padding, $all_children);

        $direct_children = array();

        foreach($all_children as $child_id) {
            if($manager->categories[$child_id]['parent_id'] == $root_id) {
                $direct_children[$child_id] = $child_id;
            }
        }

        foreach($direct_children as $child_id) {

            $title = $manager->categories[$child_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $cat_id_params = $this->view->controller->getEntryLinkParams($child_id, $title);

            $v = array();
            $v['id'] = $child_id;
            $v['title'] = $this->stripVarStatic($title);
            $v['short_title'] = $this->stripVarStatic($short_title);
            $v['link'] = $this->view->getLink($this->view_id, $cat_id_params);
            $v['padding'] = ($this->tree[$child_id] + 1) * 8;
            $v['item_class'] = (($child_id == $this->category_id) && !$this->entry_id) ? 'menu_item_selected' : '';
            $v['base_href'] = $this->kb_path;

            $v['block_class'] = (in_array($child_id, $this->parents)) ? 'category_loaded' : '';
            $v['icon'] = (in_array($child_id, $this->parents)) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $private = $this->view->isPrivateEntry(false, $manager->categories[$child_id]['private']);
            if ($private && !$manager->is_registered) {
                $v['icon_str'] = $this->getIcon('menu_category_collapsed_private');

            } else {
                $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
                $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
                $v['icon_str'] = $this->getIcon($v['icon'], $attributes);
            }

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            // entries
            if (in_array($child_id, $this->parents)) { // go deeper
                $this->parseSubtree($tpl, $manager, $child_id);
            }
        }
    }


    function parseEntries($tpl, $manager, $category_id, $padding, $children) {

        $max_len = $this->entry_menu_max_len;

        if($this->view_id == 'index') {
            $sort = $manager->getSortOrder();
            $manager->setSqlParamsOrder('ORDER BY ' . $sort);

            $entries = $manager->getCategoryEntries($category_id, 0);

            if (empty($entries) && !$children) {

                $title = $this->view->msg['empty_category_msg'];
                $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

                $a = array();
                $a['title'] = $this->stripVarStatic($title);
                $a['short_title'] = $this->stripVarStatic($short_title);
                $a['id'] = 'msg';
                $a['padding'] = $padding;
                $a['base_href'] = $this->kb_path;
                $a['icon_str'] = $this->getIcon('menu_category_empty');

                $tpl->tplSetNeeded('row/message');
                $tpl->tplParse($a, 'row');

            } else {

                foreach ($entries as $entry) {

                    $title = $entry['title'];
                    $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
                    $entry_id_param = $this->view->controller->getEntryLinkParams(
                                        $entry['id'], $entry['title'], $entry['url_title']);

                    $a = array();
                    $a['title'] = $this->stripVarStatic($title);
                    $a['short_title'] = $this->stripVarStatic($short_title);
                    $a['id'] = 'entry_' . $entry['id'];
                    $a['padding'] = $padding;
                    $a['link'] = $this->view->getLink('entry', false, $entry_id_param);
                    $a['category_id'] = $category_id;
                    $a['category_id_cookie'] = $category_id;
                    $a['item_class'] = ($entry['id'] == $this->entry_id && $this->category_id == $category_id) ? 'menu_item_selected' : '';
                    $a['base_href'] = $this->kb_path;

                    $private = $this->view->isPrivateEntry($entry['private'], $manager->categories[$category_id]['private']);
                    if ($private && !$manager->is_registered) {
                        $a['icon_str'] = $this->getIcon('menu_entry_private');
                    } else {
                        $a['icon_str'] = $this->getIcon('menu_entry');
                    }

                    $tpl->tplSetNeeded('row/link');
                    $tpl->tplParse($a, 'row');
                }
            }

        } else { // files

            $manager->setSqlParams('AND e_to_cat.category_id = ' . $category_id, null, true);
            $count = $manager->getEntryCount();

            if ($count || !$children) {
                $a = array();
                $a['id'] = 'files_' . $category_id;

                if ($count) {
                    $title = sprintf('%s (%s)', $this->view->msg['file_title_msg'], $count);
                    $title = $this->stripVarStatic($title);
                    $a['link'] = $this->view->getLink($this->view_id, $category_id);
                    $a['style'] = 'font-size: 0.9em;color: #aaaaaa;';

                    $tpl->tplSetNeeded('row/link');

                } else {
                    $title = $this->view->msg['empty_category_msg'];
                    $tpl->tplSetNeeded('row/message');
                }

                $a['title'] = $title;
                $a['short_title'] = $title;
                $a['padding'] = $padding;
                $a['base_href'] = $this->kb_path;
                $a['icon_str'] = $this->getIcon('menu_entry');

                //$tpl->tplSetNeeded('row/message');
                $tpl->tplParse($a, 'row');
            }
        }
    }
}


class KBClientMenu_news extends KBClientMenu
{

    function &getLeftMenu($manager) {

        $current_year = &$this->category_id;
        if($this->entry_id) {
            $current_year = $manager->getYearByEntryId($this->entry_id);
        }

        $year_range = $manager->getNewsYears();
        rsort($year_range);
        $year_range = array_slice($year_range, 0, 100);

        $template_dir = $this->view->getTemplateDir('fixed', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('sidebar.html', $template_dir));

        $tpl->tplSetNeeded('/menu_title');
        $tpl->tplAssign('menu_title', $this->view->msg['menu_news_msg']);
        $tpl->tplAssign('menu_title_link', $this->view->getLink('news'));

        foreach($year_range as $k => $year) {

            $category_link = $this->view->getLink('news', $year);
            $category_link = $this->view->parseCategoryLink($category_link);

            $v = array();
            $v['id'] = $year;
            $v['title'] = $year;
            $v['short_title'] = $year;
            $v['link'] = $category_link;
            $v['padding'] = 7;
            $v['item_class'] = (($year == $current_year) && !$this->entry_id) ? 'menu_item_selected' : '';
            $v['block_class'] = ($year == $current_year) ? 'category_loaded' : '';
            $v['icon'] = ($year == $current_year) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
            $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
            $v['icon_str'] = $this->getIcon($v['icon'], $attributes);

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            if ($year == $current_year) {
                $this->parseSubtree($tpl, $manager, $year);
            }
        }

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);

        $xajax->registerFunction(array('getCategoryChildren', $ajax, 'getCategoryChildren'));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function parseSubtree($tpl, $manager, $root_id) {

        $max_len = $this->entry_menu_max_len;

        $str = array();
        $entries = $this->view->stripVars($manager->getNewsByYear($root_id));

        foreach(array_keys($entries) as $k) {
            $v = $entries[$k];

            $title = $v['title'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $entry_id_param = $this->view->controller->getEntryLinkParams($v['id'], $title, false);

            $a = array();
            $a['id'] = $v['id'];
            $a['title'] = $this->stripVarStatic($title);
            $a['short_title'] = $this->stripVarStatic($short_title);
            $a['padding'] = 16;
            $a['link'] = $this->view->getLink('news', false, $entry_id_param);
            $a['item_class'] = ($v['id'] == $this->entry_id) ? 'menu_item_selected' : '';
            $a['base_href'] = $this->kb_path;
            $a['icon_str'] = $this->getIcon('menu_entry');

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($a, 'row');
        }

        return implode("\n", $str);
    }
}
?>