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


class KBClientAjax_search extends KBClientAjax
{

    function ajaxGetQuickResponce($str) {

        if(_strlen($str) < 10) {
            $objResponse = new xajaxResponse();
            $objResponse->addAssign("quick_response", "innerHTML", "");
            return $objResponse;
        }

        $str = _substr($str, 0, 300);
        
        $values['q'] = trim($str);
        $values['in'] = 'article';
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $conf['debug_sphinx_sql'] = 0;

        $this->controller->loadClass('search');
        $seach_view = $this->controller->getView('search_list');
        $entries = $seach_view->getEntryListQuickResponce($this->manager, $values);


        $objResponse = new xajaxResponse();
        //$objResponse->addAlert(strlen($str));
        $objResponse->addAssign("quick_response", "innerHTML", "$entries");

        return $objResponse;
    }


    function ajaxGetCustomFields($entry_type) {

        $view = $this->controller->getView('search_form');
        $values = $view->getSearchParams();
        $values = (isset($values['custom'])) ? $values['custom'] : array();
        $custom_blocks = $view->getCustomFieldBlocks($entry_type, $values, $this->manager);
        
        $objResponse = new xajaxResponse();
        
        if (!empty($custom_blocks)) {
            $block_num = 1;
            if($entry_type == 1) {
                $block_num = 3;
    
            } elseif($entry_type == 2) {
                $block_num = 2;
            }
            
            $show_extra_link = false;
            foreach ($custom_blocks as $block) {
                $objResponse->call('insertCustomField', $block_num, $block);
                
                if ($block_num == 3) {
                    $block_num = 1;
                    
                } else {
                    $block_num ++;
                }
            }
            
        } else {
            if ($entry_type == 3) {
                $objResponse->script('$("#extra_link").hide();');
            }
        }

        return $objResponse;
    }


    function ajaxGetExtraFields($entry_type) {

        $view = $this->controller->getView('search_form');
        $extra_range = $view->getSearchByExtraRange($entry_type);
        
        $objResponse = new xajaxResponse();
        
        if ($view->mobile_view) {
            $html = '<option class="search_extra_item" value="%s">%s</option>';
            
            foreach ($extra_range as $k => $v) {
                $block = sprintf($html, $k, $v);
                
                $script = sprintf('$("#by").append(\'%s\');', $block);
                $objResponse->script($script);
            }
            
        } else {
            $html = '<div class="search_item search_extra_item">' .
                '<input type="radio" name="by" id="%s_by" value="%s" />' .
                '<label for="%s_by">%s</label></div>';
            
            foreach ($extra_range as $k => $v) {
                $block = sprintf($html, $k, $k, $k, $v);
                
                $script = sprintf('$("#search_column_2").append(\'%s\');', $block);
                $objResponse->script($script);
            }
            
            $objResponse->script('$("#search_column_2 input[type=radio]").iCheck({radioClass: "iradio_square-blue"});');
        }
        
        return $objResponse;
    }

}
?>