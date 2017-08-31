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


class KBClientView_preview extends KBClientView_common
{
    
    function execute($manager) {        
        
        $this->meta_title = $this->msg['preview_msg'];
        
        $tpl = new tplTemplatez($this->template_dir . 'article_preview.html');
        
        $tpl->tplAssign('codesnippet_files', DocumentParser::parseCode2GetFiles($this->controller));
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);    
    }
    
}
?>