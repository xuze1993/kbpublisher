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


class KBClientAjax_menu extends KBClientAjax
{

    function getAllTreeEntries($mode, $img_id) {

        $objResponse = new xajaxResponse();

        $this->menu->view->msg['menu_title_msg'] = $this->menu->view->msg['menu_article_msg'];
        $this->menu->tree_menu_limit = -1; // no limit

        // top tree
        switch ($mode) {
            case 'top':
                $top_category_id = TreeHelperUtil::getTopParent($this->manager->categories, $this->category_id);
                $tree_js = $this->menu->getTreeJavascript($this->manager, $top_category_id, false, true);
                $tree_html = $this->menu->getTopTreeMenu($this->manager);

                break;

            case 'all':
                $tree_js = $this->menu->getTreeJavascript($this->manager, 0, false);
                $tree_html = $this->menu->getTreeMenu($this->manager);

                break;
        }

        $css = '<link rel="stylesheet" href="' . $this->menu->view->controller->client_path .'skin/dtree.css" type="text/css" />';

        $objResponse->addScript($tree_js);
        $objResponse->assign('menu_content', 'innerHTML', $tree_html);

        $objResponse->addScript('document.getElementById("dtree_content").innerHTML = d;');
        $objResponse->addScript("d.openTo($this->category_id, false);");

        $objResponse->append('menu_content', 'innerHTML', $css);
        $objResponse->addScript("$('#$img_id').css('display', 'inline');");

        return $objResponse;
    }


    function getAllFollowEntries() {

        $objResponse = new xajaxResponse();

        $this->menu->view->msg['menu_title_msg'] = $this->menu->view->msg['menu_article_msg'];
        $this->menu->tree_menu_limit = -1;
        $html = $this->menu->getFollowMenu($this->manager);

        $objResponse->assign('dtree_follow', 'innerHTML', $html);

        return $objResponse;
    }


    function getCategoryChildren($id) {

        $objResponse = new xajaxResponse();

        $template_dir = $this->menu->view->getTemplateDir('fixed', 'default');
        $tpl = new tplTemplatez($this->menu->view->getTemplate('sidebar.html', $template_dir));
        
        $tpl->tplAssign('base_href', $this->menu->view->controller->kb_path);

        $this->menu->parseSubtree($tpl, $this->manager, $id);

        $tpl->tplParse();
        $html = $tpl->tplPrint(1);

        $objResponse->call('expandCategory', $id, $html);

        return $objResponse;
    }

}