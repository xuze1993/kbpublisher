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

class KBClientView_category extends KBClientView_common
{

    function &execute(&$manager) {

        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['browse_msg'];
        
        $tpl = new tplTemplatez($this->getTemplate('browse_category.html'));
        
        $nav = array();
        
        $entry_type = false;
        if(isset($_GET['entry_type'])) {
            $entry_type = (int) $_GET['entry_type'];
        }
        
        $filter = false;
        if(isset($_GET['filter'])) {
            $filter = (int) $_GET['filter'];
        }
        
        if (!empty($entry_type)) {
            $view = ($entry_type == 2) ? 'files' : false;
            $manager = &KBClientLoader::getManager($manager->setting, $this->controller, $view);
            
            $link = $this->getLink('category');
            $nav = array($link => $this->msg['browse_msg']);
            $entry_type_key = ($entry_type == 1) ? 'entry_title_msg' : 'menu_file_msg';
            
            $tpl->tplSetNeeded('/filter');
                
            if (empty($filter)) { // 2nd screen, top categories
                
                $range = array();
                foreach(array_keys($manager->categories) as $cat_id) {
                    if ($manager->categories[$cat_id]['parent_id'] == 0) {
                        $range[$cat_id] = $manager->categories[$cat_id]['name'];
                    }   
                }
            
                foreach ($range as $value => $title) {
                    $more = array('entry_type' => $entry_type, 'filter' => $value);
                    $v['link'] = $this->getLink('category', false, false, false, $more);
                    $v['title'] = $title;
                    $tpl->tplParse($v, 'row');
                }
                
                
                $nav[] = $this->msg[$entry_type_key];
                    
            } else { // 3rd screen, child categories
                $categories =& $manager->categories;
                
                $range_ = $manager->getCategorySelectRange($categories, $filter);
                $range = array();
                if(isset($categories[$filter]['name'])) {
                    $range[$filter] = $categories[$filter]['name'];
                }
        
                if (!empty($range_)) {
                    foreach (array_keys($range_) as $cat_id) {
                        $range[$cat_id] = '-- '. $range_[$cat_id];
                    }
                }
                
                foreach ($range as $value => $title) {
                    $view = ($entry_type == 1) ? 'index' : 'files';
                    $v['link'] = $this->getLink($view, $value);                    
                    $v['title'] = $title;
                    $tpl->tplParse($v, 'row');
                }
                
                $more = array('entry_type' => $entry_type);
                $et_link = $this->getLink('category', false, false, false, $more);
                $nav[$et_link] = $this->msg[$entry_type_key];
                $nav[] = $manager->categories[$filter]['name'];
            }
            
        } elseif ($manager->getSetting('module_file')) { // 1st screen, entry types
            $range = array(1 => $this->msg['entry_title_msg'], 2 => $this->msg['menu_file_msg']);
            foreach ($range as $value => $title) {
                $more = array('entry_type' => $value);
                $v['link'] = $this->getLink('category', false, false, false, $more);
                $v['title'] = $title;
                $tpl->tplParse($v, 'row');
            }
            
            $nav = array($this->msg['browse_msg']);
            
        } else {
            $more = array('entry_type' => 1);
            $link = $this->controller->getRedirectLink('category', false, false, false, $more);
            header('Location: ' . $link);
            exit;
        }
        
        $this->nav_title = $nav;

        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);      
    }
}
?>