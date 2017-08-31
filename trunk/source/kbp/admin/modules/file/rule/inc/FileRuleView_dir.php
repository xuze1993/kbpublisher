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


class FileRuleView_dir extends AppView
{
    
    var $template = 'list_directory.html';
    
    
    function execute(&$obj, &$manager, $manager_file) {
        
        $this->addMsg('error_msg.ini', 'file');

        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $directory = urldecode($this->controller->getMoreParam('dir'));

        if (!is_dir($directory) || !is_readable($directory)) {
            $msg['title'] = false;
            $msg['body'] = $this->msg['dir_not_readable_msg'];
            $tpl->tplAssign('error_msg', BoxMsg::factory('error', $msg));
        
        } else {
            $tpl->tplAssign('hint_msg', AppMsg::hintBox('read_dir', 'file'));
            
            list($files, $dirs) = $manager_file->readDirectory($directory, true, true);
            
            foreach($dirs as $dir) {
                $row['dirname'] = basename($dir);
                $tpl->tplParse($row, 'dir_row');            
            }
        
            foreach($files as $file) {
                $row['filename'] = basename($file);
                $tpl->tplParse($row, 'file_row');            
            }
            
            if (empty($files) && empty($dirs)) {
                $tpl->tplSetNeeded('/no_files');
            }
        }
        
        $tpl->tplAssign('directory', $directory); 
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>