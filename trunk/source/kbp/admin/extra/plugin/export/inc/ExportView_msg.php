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


class ExportView_msg extends AppView
{
                                            
    function execute(&$obj, &$manager) {
    
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'empty.html');
        
        $msg = AppMsg::pluginBox('no_export_plugin_error');
        $tpl->tplAssign('msg', $msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}

?>