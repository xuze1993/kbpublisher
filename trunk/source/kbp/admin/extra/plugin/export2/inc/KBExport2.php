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
require_once 'eleontev/Util/FileUtil.php';
require_once APP_CLIENT_DIR . 'client/inc/DocumentParser.php';


class KBExport2
{
        
    var $config = array(
        'temp_dir'          => '',
        'document_root'     => '',
        'http_host'         => '',
        'demo_mode'         => false,
        'print_entry_info'  => false
    );
    
    // errors
    var $codes = array(
        1001 => 'The temporary directory (%s) is not writeable or does not exist',
        1002 => 'Cannot create the directory: %s:',
        1003 => 'Cannot create or write to the file: %s',
        1004 => 'Cannot copy the file: %s. Article ID: %d', 
        1005 => 'Wrong plugin licence key',
        1006 => 'WKHTMLTOPDF - Exit with code %s',
        1007 => 'WKHTMLTOPDF error',
        -1   => 'Function proc_open() is not available',
        -2   => 'Test pdf (%s) file is not readable or does not exist',
        -3   => 'WKHTMLTOPDF is not installed',
        -4   => 'Wrong plugin licence key'
        );

    var $archive_type = 0;
    var $die_error = true;
    
    var $copy_images = 3; // 1 = don't copy, 2 = copy local only, 3 = copy all
    var $convert_links = true; // whether to convert internal links
    var $convert_https = true; // whether to convert https to http
    
    var $manager = false;
    var $controller = false;
    var $view = false;
    
    var $is_curl = false;
    var $allow_url_fopen = false;
    
    var $iso_charset;
    var $decode_utf = false;
    
    var $index_padding = 25;
    
    var $log = array();
    
    
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
    
    
    static function factory($type) {
        $class = 'KBExport2_' . $type;
        $file = $class . '.php';
        
        require_once $file;
        $obj = new $class;
        $obj->type = $type;
        
        return $obj;
    }
    
    
    static function getData($manager, $category_id) {
        
        $rows = &$manager->categories;
        $tree_helper = $manager->getTreeHelperArray($rows, $category_id);

        // add the main category
        if(in_array($category_id, array_keys($rows))) {
            foreach (array_keys($tree_helper) as $k) {
                $tree_helper[$k] += 1; 
            }
            
            $tree_helper = array($category_id => 0) + $tree_helper;
        }

        return $tree_helper;
    }
    
    
    function setComponents($manager, $controller, $view) {
        $this->manager = $manager;
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
        return (implode($this->log, "\n"));
    }
    

    function error($code, $msg = false) {
        
        if(!$msg) {
            $msg = $this->codes[$code];
        }
        
        if($this->die_error) {
            // $msg = 'KBExport2 error: ' . $msg;
            echo $msg;
            $this->removeTempDir();
            die($code);
        }

        return array('code'=>$code, 'code_message'=>$msg);
    }
    
    
    function getErrorMessage($code, $value) {
        if (isset($this->codes[$code])) {
            if (is_array($value)) {
                $msg = vsprintf($this->codes[$code], $value);
            } else {
                $msg = sprintf($this->codes[$code], $value);
            }
        } else {
            $msg = $this->codes[98];
        }
        
        return $msg;
    }

 
    function checkAvailability($check_license = true) {

        if($check_license) {
            
            $export = BaseModel::isPluginExport2();
            
            // check if WKHTMLTOPDF installed
            // we did not check for WKHTMLTOPDF installation here 
            // it does not required for HTML    
            // if($check_license === true && !$export) {
            // ... skipping, nothing to do 
            // }
            
            // check kbp license key
            if(strtolower($export) !== 'demo') {
                if(!KBValidateLicense::isPluginExportKeysMatch()) {
                    return $this->error(1005);
                }
            } elseif(strtolower($export) == 'demo') {
                $this->setConfig(array('demo_mode' => 1));
            }      
        }
  
        // writeable
        if (!is_writable($this->config['temp_dir'])) {
            return $this->error(1001, $this->getErrorMessage(1001, $this->config['temp_dir']));
        }
    }
    
    
    function getToc($tree_helper, $entries) {
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_toc.html');
        
        $num = array();
        
        foreach($tree_helper as $cat_id => $level) {
            $row['padding'] = $this->index_padding * $level;
            $row['title'] = $this->manager->categories[$cat_id]['name'];
            $row['link'] = sprintf($this->toc_cat_link_str, $cat_id);
            
            
            // numeration
            if (!empty($num)) {
                $max_level = max(array_keys($num));
                $levels_to_clear = $max_level - $level;
        
                for ($i = 0;$i < $levels_to_clear; $i ++) {
                    unset($num[count($num) - 1]);
                }
            }
                
            if (!isset($num[$level])) {
                $num[$level] = 0;
            }
            
            $num[$level] ++;
            $row['num'] = implode('.', $num);
            
            if (isset($entries[$cat_id])) {
                $num[$level + 1] = 0;
                
                foreach ($entries[$cat_id] as $entry_id => $entry_title) {
                    $v['link'] = sprintf($this->toc_entry_link_str, $entry_id, $cat_id);
                    $v['title'] = $entry_title;
                
                    $num[$level + 1] ++;
                    $v['num'] = implode('.', $num);
                
                    $tpl->tplParse($v, 'category_row/entry_row');
                }
            }
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse($row, 'category_row');
        }
        
        $tpl->tplParse($this->view->msg);
        return $tpl->tplPrint(1); 
    }
    
    
    function getEntryFile($row, $prev_id, $next_id) {
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_htmlsep_entry.html');
        
        $tpl->tplAssign('title', $row['title']);
        
        if ($prev_id || $next_id) {
            $tpl->tplSetNeededGlobal('/nav');
            
            if ($prev_id) {
                $tpl->tplSetNeeded('/prev_link');
                $tpl->tplAssign('prev_id', $prev_id);
            }     
                    
            if ($next_id) {
                $tpl->tplSetNeeded('/next_link');
                $tpl->tplAssign('next_id', $next_id);
            }
        }
            
        // entry info
        if($this->config['print_entry_info']) {
            $tpl->tplSetNeeded('/entry_info'); 
        }
        
        $tpl->tplAssign($this->view->msg);
    
        $tpl->tplParse($row);
        return $tpl->tplPrint(1); 
    }
    
    
    function getEntries($cat_id) {
        $this->manager->setSqlParams('AND ' . $this->manager->getPrivateSql(false));
        $this->manager->setSqlParams('AND ' . $this->manager->getCategoryRolesSql(false));
        
        $this->manager->setSqlParams("AND cat.id = '{$cat_id}'", 'export');
        $this->manager->setSqlParamsOrder('ORDER BY ' . $this->manager->getSortOrder());
        
        $limit = ($this->config['demo_mode']) ? 3 : -1;  // limit 3 in  demo mode
        return $this->manager->getEntryList($limit, 0, 'category');
    }

    
    function parseEntry(&$row, $custom, $full_path, &$entries_with_related) {
                        
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($this->manager, 'getTemplate'));
        }

        // related articles
        if(DocumentParser::isLinkArticle($row['body'])) {
            $entries_with_related[] = $row['id'];
        }

        // attachments
        if(DocumentParser::isLinkFile($row['body'])) {
            $this->parseFileLink($row['body'], $row['id']);
        }
        
        if(DocumentParser::isCode($row['body'])) {
            DocumentParser::parseCodePrint($row['body']);    
        }
        
        DocumentParser::parseCurlyBraces($row['body']);

        //$row['hnum'] = $hnum + 1;           
        $row['body'] = &$this->parseBody($row['id'], $row['body']);

        // custom
        $row['custom_tmpl_top'] = '';
        $row['custom_tmpl_bottom'] = '';
        if(!empty($custom[$row['id']])) {
            $custom_data = $this->view->getCustomData($custom[$row['id']]);
            $row['custom_tmpl_top'] = $this->view->parseCustomData($custom_data[1], 1);
            $row['custom_tmpl_bottom'] = $this->view->parseCustomData($custom_data[2], 2);
            
            // decode
            if($this->decode_utf) {
                $row['custom_tmpl_top'] = $this->decodeUTF8($row['custom_tmpl_top']);
                $row['custom_tmpl_bottom'] = $this->decodeUTF8($row['custom_tmpl_bottom']);
            }
        }
    
        //$cat_type = $manager->getCategoryType($cat_id);
        //$nobreak_types = array('faq', 'faq2');

        // decode
        if($this->decode_utf) {
            $row['title'] = $this->decodeUTF8($row['title']);
            $row['body'] = $this->decodeUTF8($row['body']);
        }
        
        if ($this->config['print_entry_info']) {
            $row['formated_date'] = $this->view->getFormatedDate($row['date_updated']);
            
            if (empty($row['revision'])) {
                $row['revision'] = $this->manager->getRevisionNum($row['id']);
            }
            
            if (empty($row['category_title_full'])) {
                $row['category_title_full'] = $full_path[$row['category_id']];
            }
            
            $r = new Replacer();
            $r->s_var_tag = '[';
            $r->e_var_tag = ']';
            $r->strip_var_sign = '--';
            
            if (empty($row['updater'])) {
                $updater = $this->manager->getUserInfo($row['updater_id']);
                @$updater['short_first_name'] = _substr($updater['first_name'], 0, 1);
                @$updater['short_last_name'] = _substr($updater['last_name'], 0, 1); 
                @$updater['short_middle_name'] = _substr($updater['middle_name'], 0, 1);
                $row['updater'] = $r->parse($this->manager->setting['show_author_format'], $updater);
            }
            
            $link = $this->controller->getLink('entry', $row['category_id'], $row['id']);
            $row['entry_link'] = $this->controller->_replaceArgSeparator($link);
        }
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
    
    
    function parseRelated(&$data, $names) {
        
        $_names = array_keys($names);
        
        $search = "#\[link:article\|(\d+)\]#";
        preg_match_all($search, $data, $matches);
        
        foreach ($matches[1] as $related_id) {
            $search = sprintf('[link:article|%d]', $related_id);
            
            if (in_array($related_id, $_names)) {    
                $link = sprintf($this->toc_entry_link_str, $related_id, $names[$related_id][0]);
                
            } else {
                $link = $this->controller->getLink('entry', false, $related_id);
            }
            
            $data = str_replace($search, $link, $data);
        }
    }
    
    
    // parse body, replace h-tags, etc
    function &parseBody($id, $content) {
    
        $search[1] = '#<h\d[^>]*>(.*?)<\/h\d>#is'; 
        $replace[1] = '<br /><font size="+1"><b>\\1</b></font></span><br />';
        $search[2] = '#href=["\']/(.*?)["\']#i'; // all related links to full (http)
        $replace[2] = 'href="http://' . $this->config['http_host'] . '/$1"';

        $output = preg_replace($search, $replace, $content);

        // add br to <ul class="extraMargin">
        $search = '#<(ul|ol) class="extraMargin">.*?<\/\1>#is';
        preg_match_all($search, $output, $match);

        if(!empty($match[0])) {
            foreach(array_keys($match[0]) as $k) {
                $ul = str_replace('</li>', '<br /><br /></li>', $match[0][$k]);
                $output = str_replace($match[0][$k], $ul, $output);
            }
        }
        
        $output = $this->parseImage($id, $output);
        
        $search = '#src=(["\'])/(.*?)\\1#i'; 
        $replace = 'src="http://' . $this->config['http_host'] . '/$2"';
        $output = preg_replace($search, $replace, $output);
        
        // echo '<pre>', print_r(htmlspecialchars($output), 1), '</pre>';
        // exit;
        
        return $output;
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
                if ($this->copy_images == 1) {
                    $src = APP_CLIENT_PATH . $src;
                    $src = str_replace('//', '/', $src);
                    $new_tag = preg_replace('/src="([^"]*)"/i', 'src="' . $src . '"', $tag);
                                    
                } else {
                    $src = $this->config['document_root'] . $src;
                    $src = str_replace('//', '/', $src);   
                }
            }            
            
            if ($this->copy_images != 1) { // copy an image
            
                if ($this->copy_images == 2 && $is_remote) {
                    continue;
                }
                
                $filename = basename($src);
                $new_src = $images_dir . $filename;
                if (file_exists($new_src)) {
                    $ext = substr($filename, strrpos($filename, '.'));
                    $name = substr($filename, 0, strpos($filename, '.'));
    
                    $i = 0;
                    while (file_exists($name . $i . $ext)) {
                        $i ++;
                    }
                    
                    $new_src = $images_dir . $name . $i . $ext;
                }
                
                if ($is_remote & $this->is_curl) {
                
                    if ($this->convert_https) {
                        $src = str_replace('https://', 'http://', $src);
                    }
                
                    $src = str_replace(' ','%20', $src);
                    $ch = curl_init($src);
                    
                    $fp = fopen($new_src, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    $curl_error = curl_error($ch);
                    fclose($fp);
                    
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if($http_code == 200) {
                        if (getimagesize($new_src)) {
                            $remove_tmp_file = false;
                            
                            $src = 'images/' . basename($new_src);
                            $new_tag = preg_replace('/src="([^"]*)"/i', 'src="' . $src . '"', $tag);
                            
                        } else { // the file's not an image
                            $remove_tmp_file = true;
                        }
                        
                    } else { // got a bad response, remove the temp file anyway
                        $remove_tmp_file = true;
                    }
                    
                    curl_close($ch);
                    
                    if ($remove_tmp_file) {
                        $error_msg = sprintf("%s\ncURL error: %s", $this->getErrorMessage(1004, array($src, $id)), $curl_error); 
                        $this->log($error_msg);
                            
                        unlink($new_src);
                    }
                        
                } elseif (!$is_remote || $this->allow_url_fopen) {
                    
                    if (copy($src, $new_src)) {
                        $src = 'images/' . basename($new_src);
                        $new_tag = preg_replace('/src="([^"]*)"/i', 'src="' . $src . '"', $tag);
                        
                    } else {
                        $this->log($this->getErrorMessage(1004, array($src, $id)));
                    }
                    
                } else {
                    $this->log($this->getErrorMessage(1004, array($src, $id)));    
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
    
    
    function parseCustomOptions($str) {
        
        $data = array();
        
        $pattern = '/\B--?/';
        $options = preg_split($pattern, $str);       
        
        $pattern = '/([\w-]+)[= ][\'|\"]?([\w-]*)[\'|\"]?/';
        foreach($options as $v) {
            preg_match($pattern, $v, $matches);
            if (isset($matches[1])) {
                $data[$matches[1]] = array($matches[0], $matches[2]);
            }
        }
        return $data;
    }
    
    
    function getEntryToCatsArray($arr) {
        $res = array();
        foreach ($arr as $cat_id => $cat) {
            foreach ($cat as $entry_id => $entry_name) {
                $res[$entry_id][] = $cat_id;
            }
        }
        
        return $res;
    }


    function writeTmpFile($name, $data) {
        $filename = sprintf('%soutput/%s.html', $this->config['temp_dir'], $name);
        $ret = FileUtil::write($filename, $data);
        if(!$ret) {
            return $this->error(1003, $this->getErrorMessage(1003, $filename));
        }
    }
    
    
    function readTmpFile($name) {
        $filename = sprintf('%soutput/%s.html', $this->config['temp_dir'], $name);       
        return FileUtil::read($filename);
    }
    
    
    function setArchiveType($type) {
        $this->archive_type = $type;
    }
    
    
    function compress() {
        if(extension_loaded('zip')) {
            return $this->zipFiles(); // php zip extension
            
        } else {
            return $this->zipFiles2(); // pear
        }
    }
    
    
    function zipFiles2() {
        
        if ($this->config['category_id']) {
            $title = $this->manager->categories[$this->config['category_id']]['name'];
        } else {
            $title = $this->view->msg['all_categories_msg']; 
        }
        
        $file_path = '%s/export.%s';
        $file_path = sprintf($file_path, $this->config['temp_dir'], $this->archive_type);
        $file_path = str_replace('//', '/', $file_path);
        
        //$dir_name = $title . '_' . date('Y-m-d'); // better not to use UTF symbols inside ZIP files
        
        File_Archive::setOption('zipCompressionLevel', 0);
        
        $source = File_Archive::readMulti(
            array(
                File_Archive::read($this->config['temp_dir'] . 'output'),
                File_Archive::read(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/style.css')
            )
        );
        
        File_Archive::extract(
            $source,
            File_Archive::toArchive(
                $file_path,
                $writer
            )
        );
        
        $data = FileUtil::read($file_path);
        return $data;
    }
    
    
    function zipFiles() {
        
        if ($this->config['category_id']) {
            $title = $this->manager->categories[$this->config['category_id']]['name'];
        } else {
            $title = $this->view->msg['all_categories_msg']; 
        }
        
        $file_path = '%s/export.%s';
        $file_path = sprintf($file_path, $this->config['temp_dir'], $this->archive_type);
        $file_path = str_replace('//', '/', $file_path);
        
        $zip = new ZipArchive;
        
        if (!$zip->open($file_path, ZipArchive::CREATE)) {
            exit("Cannot create $filename\n");
        }
        
        $zip->addFile(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/style.css', 'style.css');
        
        $output_dir = $this->config['temp_dir'] . 'output/';
        $output_dir = str_replace('\\', '/', $output_dir);
        
        require_once 'eleontev/Dir/MyDir.php';
        $d = new MyDir;
        
        $files = &$d->getFilesDirs($output_dir);

        foreach ($files as $k => $v) {
            if (is_array($v)) {
                $zip->addEmptyDir($k);
                
                foreach ($v as $v1) {
                    $tmp_file_path = sprintf('%s%s/%s', $output_dir, $k, $v1);
                    $zip->addFromString(sprintf('%s/%s', $k, $v1), file_get_contents($tmp_file_path));
                }
                
            } else {
                $zip->addFromString($v, file_get_contents($output_dir . $v));
            }
        }
        
        $zip->close();
        
        $data = FileUtil::read($file_path);
        return $data;
    }
    
    
    // EXPORT DIRECTORIES
    static function getTempDir($dir, $type) {
        $dir = preg_replace('#\\\\#', '/', $dir);     
        $sub_dir = 'export_' . md5('export' . $type . time());
        $export_dir = $dir . '/' . $sub_dir . '/';
        $export_dir = str_replace('//', '/', $export_dir);
        
        return $export_dir;
    }


    function createTempDirs() {
        $this->createDir($this->config['temp_dir']);
        $this->createDir($this->config['temp_dir'] . 'output/');
        
        if ($this->copy_images != 1) {
            $this->createDir($this->config['temp_dir'] . 'output/images/');
        }
    }


    function createDir($dir) {
        if(!is_dir($dir)) {
            $oldumask = umask(0);
            $r = mkdir($dir, 0777);
            umask($oldumask);
            
            if (!$r) {
                $this->error(1002, $this->getErrorMessage(1002, $dir));
            }
        }
        
        if(!is_writeable($dir)) {
            $this->error(1001, $this->getErrorMessage(1001, $dir));
        }
    }


    function removeTempDir() {
        require_once 'eleontev/Dir/MyDir.php';
        $d = new MyDir;
        $ret = $d->removeFilesDirs($this->config['temp_dir']);
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
	
	
    function replaceMSWordCharacters($string) {
        
        $table = array(
            '‘' => "'", '’' => "'", 
            '”' => '"', '“' => '"', 
            '–' => '-', '…' => '...' 
        );

        return strtr($string, $table);
    }
    
}
?>