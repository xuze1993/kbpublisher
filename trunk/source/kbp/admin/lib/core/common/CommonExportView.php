<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

class CommonExportView
{    

    static function getExportFormBlock($obj, $manager, $export_links, $total_row = true) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'report/usage/template/block_export_form.html');
        
        if ($total_row) {
            $tpl->tplSetNeeded('/total_row');
        }
        
        foreach ($export_links as $type => $link) {
            $tpl->tplAssign('export_' . $type . '_link', $link);             
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }

}
?>