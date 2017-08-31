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

class KBClientView_common extends KBClientView
{
    
    var $own_format = 'none';
    var $default_format = 'default';
    var $view_template = array('page_in.html', 'block_menu_top.html');
    
    

    function setTemplateDir($format, $skin) {
        $this->template_dir = $this->getTemplateDir($format, 'default');
        // echo '<pre>', print_r($this->template_dir, 1), '</pre>';
    }    
 
    
    function &getLeftMenu($manager) {
        $a = ''; return $a;
    }
    
}
?>