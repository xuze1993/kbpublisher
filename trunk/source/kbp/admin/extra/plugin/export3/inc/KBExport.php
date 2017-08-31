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

require_once 'File/Archive.php';
require_once 'eleontev/Dir/mime_content_type.php';
require_once 'eleontev/Util/FileUtil.php';
require_once 'eleontev/Array2XML.php';


class KBExport
{
        
    var $config = array(
        'temp_dir'            => '',
        'document_root'       => '',
        'http_host'           => '',
        'include_images'      => false,
        'fields_terminated'   => ',',
        'optionally_enclosed' => '"',
        'lines_terminated'    => "\n",
        'header_row'          => false,
        'article_info'        => false
    );
    
    var $archive_type = 'zip';
    
    var $convert_links = true; // whether to convert internal links
    var $convert_https = true; // whether to convert https to http
    
    var $manager = false;
    var $client_manager = false;
    var $controller = false;
    var $view = false;
    
    var $is_curl = false;
    var $allow_url_fopen = false;
    
    var $iso_charset;
    var $decode_utf = false;
    
    var $log = array();
    
    var $entries_limit = 2;
    
    
    function __construct() {

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $this->iso_charset = (!empty($conf['lang']['iso_charset'])) ? $conf['lang']['iso_charset'] : null;
        
        // only if not en and ISO-8859-1 
        if($conf['lang']['meta_content'] != 'en') {
           if($this->iso_charset && strtoupper($this->iso_charset) == 'ISO-8859-1') {
               // $this->decode_utf = true;
           } 
        }
        
        
        $config = array(
            'document_root' => (isset($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : '',
            'http_host'     => (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : ''
        );
        
        $this->setConfig($config);
        
        $this->is_curl = function_exists('curl_version');
        $this->allow_url_fopen = ini_get('allow_url_fopen');
    }
    
    
    function setComponents($manager, $client_manager, $controller, $view) {
        $this->manager = $manager;
        $this->client_manager = $client_manager;
        $this->controller = $controller;
        $this->view = $view;
    }
    
    
    function setConfig($config) {
        foreach($config as $k => $v) {
            $this->config[$k] = $v;
        }
    }

    
    function getConfig($key) {
        return $this->config[$key];
    }
    
    
    function log($msg) {
        $this->log[] = $msg;
    }
    
    
    function getLog() {
        $data = implode($this->log, "\n");
        $this->log = array();
        return $data;
    }
    
    
    function getExportData($result) {
        $data = array();
            
        if ($this->getConfig('header_row')) {
            $headline = array();
            
            foreach ($this->getConfig('columns') as $field_msg_key) {
                $headline[] = ExportColumnHelper::getColumnTitle($field_msg_key);
            }
            
            $data[] = $headline;
        }
        
        $full_path = $this->manager->getCategorySelectRangeFolow();
        $status = $this->manager->getEntryStatusData('article_status');
        
        $entry_ids = array();
        $empty = true;
        
        while ($row = $result->FetchRow()) {
            $empty = false;
            $this->parseEntry($row, $full_path);
            
            $line = array();
            foreach ($this->getConfig('columns') as $field) {
                
                if ($field == 'category') {
                    $line[$field] = ''; // will be filled later
                    
                } elseif ($field == 'active') {
                    $line[$field] = $status[$row['active']]['title'];
                    
                } else {
                    $line[$field] = $row[$field];
                }
            }
            
            $data[$row['id']] = $line;
            
            $entry_ids[] = $row['id'];
            if (count($entry_ids) >= $this->entries_limit) {
                $this->setCategories($entry_ids, $data);
                $this->setCustomData($entry_ids, $data);
                
                $entry_ids = array();                
            }
        }
        
        if ($empty) {
            return false;
        }
        
        if (!empty($entry_ids)) {
            $this->setCategories($entry_ids, $data);
            $this->setCustomData($entry_ids, $data);
        }
        
        return $data;
    }
    
    
    function setCategories($entry_ids, &$data) {
        $columns = array('category', 'category_id');
        $columns_in_use = array_intersect($columns, $this->getConfig('columns'));
        
        if (empty($columns_in_use)) {
            return;
        }
        
        $entry_ids = implode(',', $entry_ids);
        $entry_to_categories = $this->manager->getCategoryByIds($entry_ids);
        foreach ($entry_to_categories as $entry_id => $categories) {
            $category_ids = array();
            $category_msg = array();
            foreach ($categories as $category_id => $category) {
                $category_ids[] = $category_id;
                $category_msg[] = $category['title'];
            }
            
            if (in_array('category_id', $columns_in_use)) {
                $data[$entry_id]['category_id'] = implode(',', $category_ids);    
            }
            
            if (in_array('category', $columns_in_use)) {
                $data[$entry_id]['category'] = implode(',', $category_msg);    
            }
        }
    }
    
    
    function setCustomData($entry_ids, &$data) {
        if (!in_array('body', $this->getConfig('columns'))) {
            return;    
        }
            
        $entry_ids = implode(',', $entry_ids);
        $custom_data = $this->client_manager->getCustomDataByEntryId($entry_ids, true);
        
        foreach ($custom_data as $entry_id => $custom) {
            $custom_tmpl_top = '';
            $custom_tmpl_bottom = '';
            
            $v = $this->view->getCustomData($custom);
            $custom_tmpl_top = $this->view->parseCustomData($v[1], 1);
            $custom_tmpl_bottom = $this->view->parseCustomData($v[2], 2);
            
            // decode
            if($this->decode_utf) {
                $custom_tmpl_top = $this->decodeUTF8($custom_tmpl_top);
                $custom_tmpl_bottom = $this->decodeUTF8($custom_tmpl_bottom);
            }
            
            $data[$entry_id]['body'] = $custom_tmpl_top . $data[$entry_id]['body'] . $custom_tmpl_bottom; 
        }
    }
    
    
    function getCsvFile($data, $title) {
        $opts = array(
            'ft' => $this->config['fields_terminated'],
            'oe' => $this->config['optionally_enclosed'],
            'lt' => $this->config['lines_terminated']);
        
        $data = RequestDataUtil::parseCsv($data, $opts);
        
        $size = WebUtil::getFileSize(strlen($data));
        $msg = 'Generated successfully, Size: %s';
        $msg = sprintf($msg, $size);
        $this->log($msg);
        
        $data = $this->compress($data, $title);
        return $data;
    }
    
    
    function getXmlFile($data, $title) {
        
        $_data = array('article' => array());
        foreach ($data as $id => $entry) {
            $a = array();
            $a['@attributes'] = array('id' => $id);
            foreach ($entry as $node => $value) {
                if ($node == 'body') {
                    $a[$node] = array('@cdata' => $value);
                    
                } else {
                    $a[$node] = $value;
                }
            }
            $_data['article'][] = $a;
        }
        
        $xml = Array2XML::createXML('articles', $_data);
        $data = $xml->saveXML();
        
        $size = WebUtil::getFileSize(strlen($data));
        $msg = 'Generated successfully, Size: %s';
        $msg = sprintf($msg, $size);
        $this->log($msg);
        
        $data = $this->compress($data, $title, 'xml');
        return $data;
    }

    
    function parseEntry(&$row, $full_path) {
                        
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($this->client_manager, 'getTemplate'));
        }
        
        if(strpos($row['body'], '[link:article') !== false) {
            $search = "#\[link:article\|(\d+)\]#";
            $row['body'] = preg_replace_callback($search, array($this, 'getLinkToRelated'), $row['body']);  
        }

        // attachments
        if(DocumentParser::isLinkFile($row['body'])) {
            $this->parseFileLink($row['body'], $row['id']);
        }
                   
        $row['body'] = $this->parseBody($row['id'], $row['body']);
    
        //$cat_type = $manager->getCategoryType($cat_id);
        //$nobreak_types = array('faq', 'faq2');

        // decode
        if($this->decode_utf) {
            $row['title'] = $this->decodeUTF8($row['title']);
            $row['body'] = $this->decodeUTF8($row['body']);
        }
        
        if ($this->config['article_info']) {
            $row['formated_date'] = $this->view->getFormatedDate($row['date_updated']);
            $row['revision'] = $this->manager->getRevisionNum($row['id']);
            $row['category_title_full'] = $full_path[$row['category_id']];
            
            $r = new Replacer();
            $r->s_var_tag = '[';
            $r->e_var_tag = ']';
            $r->strip_var_sign = '--';
            
            $updater = $this->manager->getUserInfo($row['updater_id']);
            @$updater['short_first_name'] = _substr($updater['first_name'], 0, 1);
            @$updater['short_last_name'] = _substr($updater['last_name'], 0, 1); 
            @$updater['short_middle_name'] = _substr($updater['middle_name'], 0, 1);
            $row['updater'] = $r->parse($this->manager->setting['show_author_format'], $updater);
            
            $link = $this->controller->getLink('entry', $row['category_id'], $row['id']);
            $row['entry_link'] = $this->controller->_replaceArgSeparator($link);
        }
    }
    
    
    function getLinkToRelated($matches) {
        $related_id = $matches[1];
        $link = $this->controller->getLink('entry', false, $related_id);
        return $link;
    }
    
    
    function parseFileLink(&$str, $article_id) {
        $func = array($this->controller, 'getLink');
        $search = "#\[link:file\|(\d+)\]#";
        $str = preg_replace_callback(
            $search, 
            function ($matches) use($func, $article_id) {
                return call_user_func_array($func, 
                    array('afile', false, $article_id, false, 
                        array('AttachID' => $matches[1]), 1)
                    );
            },
            $str);
    }
    
    
    function parseBody($id, $body) { 

        // add br to <ul class="extraMargin">
        $search = '#<(ul|ol) class="extraMargin">.*?<\/\1>#is';
        preg_match_all($search, $body, $match);

        if(!empty($match[0])) {
            foreach(array_keys($match[0]) as $k) {
                $ul = str_replace('</li>', '<br /><br /></li>', $match[0][$k]);
                $body = str_replace($match[0][$k], $ul, $body);
            }
        }
        
        if ($this->getConfig('include_images')) {
            $body = $this->parseImage($id, $body);
        }
        
        return $body;
    }
    
    
    function parseImage($id, $output) {

        $search = array();
        $replace = array();
                                              
        $images_dir = $this->config['temp_dir'] . '/output/images/';
        
        preg_match_all('/<img[^>]+>/i', $output, $result);
                             
        foreach ($result[0] as $tag) {

            $image = array();
            $new_tag = $tag;
            
            preg_match_all('/src="([^"]*)"/i', $tag, $image);
            
            // $src = $image[1][0];
            $src = rawurldecode($image[1][0]);
            $is_remote = (strpos($src, 'http://') !== false || strpos($src, 'https://') !== false);
            $is_embedded = (strpos($src, 'data:image') !== false);
            
            if ($is_embedded) { // we don't touch encoded images
                continue;
            }
            
            if (!$is_remote) { // it's a local image
                $src = APP_CLIENT_DIR . $src;
                $src = str_replace('//', '/', $src);
                
                $image = FileUtil::read($src);
                if ($image) {
                    $mime_type = mime_content_type($src);
                    $encoded_image = base64_encode($image);
                    $src = sprintf('data:%s;base64,%s', $mime_type, $encoded_image);
                    $new_tag = preg_replace('/src="([^"]*)"/i', 'src="' . $src . '"', $tag);
                }  
            }            
            

            $search[] = $tag;    
            $replace[] = $new_tag;
        }
                              
        if($search) {  
            $output = str_replace($search, $replace, $output);
        }

        return $output;
    }
    
    
    function compress($data, $title, $extension = 'csv') {
        if(extension_loaded('zip')) {
            return $this->zipFiles($data, $title, $extension); // php zip extension
            
        } else {
            return $this->zipFiles2($data, $title, $extension); // pear
        }
    }
    
    
    function zipFiles2($data, $title, $extension) {
        
        $file_path = '%s/export.%s';
        $file_path = sprintf($file_path, APP_CACHE_DIR, $this->archive_type);
        $file_path = str_replace('//', '/', $file_path);
        
        File_Archive::setOption('zipCompressionLevel', 0);
        
        $title = 'export';
        $file_path2 = sprintf('%s%s.%s', APP_CACHE_DIR, $title, $extension);
        FileUtil::write($file_path2, $data);
        
        $source = File_Archive::read($file_path2);
        
        File_Archive::extract(
            $source,
            File_Archive::toArchive(
                $file_path,
                $writer
            )
        );
        
        $data = FileUtil::read($file_path);
        
        unlink($file_path);
        unlink($file_path2);
        
        return $data;
    }
    
    
    function zipFiles($data, $title, $extension) {
        
        $file_path = '%s/export.%s';
        $file_path = sprintf($file_path, APP_CACHE_DIR, $this->archive_type);
        $file_path = str_replace('//', '/', $file_path);
        
        $zip = new ZipArchive;
        
        if (!$zip->open($file_path, ZipArchive::CREATE)) {
            exit("Cannot create $filename\n");
        }
        
        $title = 'export';
        $zip->addFromString(sprintf('%s.%s', $title, $extension), $data);
        $zip->close();
        
        $data = FileUtil::read($file_path);
        unlink($file_path);
        
        return $data;
    }


	function decodeUTF8($string) {

        $from = 'UTF-8';
        $to = strtoupper($this->iso_charset);

		// from UTF-8 to ISO-8859-1 only
		if($to == "ISO-8859-1") {
			$string = utf8_decode($string);

		} elseif(function_exists('iconv')) {
			$string2 = iconv($from, $to.'//IGNORE', $string); 	//$to.'//TRANSLIT'
			$string = &$string2;
            
		} elseif(function_exists('mb_convert_encoding')) {
			$string = mb_convert_encoding($string, $to, $from);
            
		}

		return $string;	
	}
    
}
?>