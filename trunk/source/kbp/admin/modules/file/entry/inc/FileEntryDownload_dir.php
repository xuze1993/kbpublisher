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

class FileEntryDownload_dir
{

    static function sendFileDownload($data, $file_dir, $attachment = true) {
        
        $params['file'] = FileEntryDownload_dir::getFileDir($data, $file_dir);
        $params['gzip'] = false; //true;
        $params['contenttype'] = $data['filetype'];

        return WebUtil::sendFile($params, $data['filename'], $attachment);
    }
    

    static function getFileDir($data, $file_dir) {
        
        $files = array();
        
        $directory = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
        $filename = (!empty($data['filename_disk'])) ? $data['filename_disk'] : $data['filename'];
        
        $files[1] = $directory . '/' . $filename;
        $files[2] = $file_dir . $filename;
        
        foreach($files as $file) {
            if(file_exists($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    
/*
    function isFileOpenable($filetype) {
        
        $types = array(
            'application/pdf',
            'text/plain'
        );
        
        return (in_array($filetype, $types));
    }*/

    
}

?>