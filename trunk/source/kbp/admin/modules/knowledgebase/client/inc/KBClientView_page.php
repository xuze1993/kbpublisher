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

class KBClientView_page extends AppView
{
    
    var $tmpl = 'page.html';
    
    
    function execute() {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('page_src', APP_CLIENT_PATH);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>