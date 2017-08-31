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

require_once 'eleontev/Util/FileUtil.php';


class KBExportHtmldoc
{
            
    var $setting_rule  = array(
        'format' => '-t %s', // output format
        'path' => '--path "%s"',
        'd' => '-d "%s"', // output dir
        'webpage' => '--webpage',
        'book' => '--book',
        'compression' => '--compression=%s', // compression level (1-9)
        'jpeg' => '--jpeg=%s', // JPEG compression (0-100)
        'titleimage' => '--titleimage %s', // image at title page
        'titlefile' => '--titlefile "%s"', // image at title page 
        'logoimage' => '--logoimage "%s"', // header/footer logo
        'bodyimage' => '--bodyimage "%s"', // watermark                
        'header' => '--header %s', // header data
        'footer' => '--footer %s', // footer data
        'tocheader' => '--tocheader %s', // header of table of contents
        'tocfooter' => '--tocfooter %s', // footer of table of contents
        'toclevels' => '--toclevels %s', // heading levels to include in the table of contents
        'left' => '--left %s', // left margin
        'right' => '--right %s', // right margin
        'top' => '--top %s', // top margin  
        'bottom' => '--bottom %s', // bottom margin
        'size' => '--size %s', // size of document
        'firstpage' => '--firstpage %s', // first page to start
        'bodyfont' => '--bodyfont %s', // default font
        'fontsize' => '--fontsize %s', // document font size
        'fontspacing' => '--fontspacing %s', // line spacing as a multiplier of the base font size
        'title' => '--title "%s"',
        'password' => '--user-password "%s"' // user password  
    );
        
    var $config = array(
        'book_file_name'    => 'config.book',
        'temp_dir'          => '',
        'tool_path'         => '',
        'document_root'     => '',
        'http_host'         => '',
        'demo_mode'         => false,
        'site_url'          => '',
        'print_entry_info'  => true
    );
    

    // we need to assign right values
    var $codes = array(
        0    => 'No error',
        1001 => 'Fatal HTMLDOC error',
        1002 => 'HTMLDOC error',
        1003 => 'Temporary directory (%s) is not writeable or does not exist',
        1004 => 'Book file (%s) was not found',
        1005 => 'Cannot create directory: %s:',
        1006 => 'Cannot create or write to file: %s',
        1007 => 'Other error, no pdf file was generated',
        1008 => 'Cannot copy file: %s',
        1009 => 'Image not found: "%s"', 
        99   => 'Other error',
        127  => 'Filesystem error',
        -1   => 'Function proc_open() is not available',
        -2   => 'Test pdf (%s) file is not readable or does not exist',
        -3   => 'HTMLDOC is not installed',
        -4   => 'Wrong plugin licence key'
        );
    

    var $forbidden_custom_setting = array('d', 'f', 't', 'path');
    var $custom_setting;

    var $archive_type = 0;
    var $die_error = true;
    
    var $copy_images = 3; // 1 = don't copy, 2 = copy local only, 3 = copy all
    var $image_width = 670;
    var $image_height = 900;
    var $convert_https = true; // whether to convert https to http

    var $is_curl = false;
    var $allow_url_fopen = false;
    
    var $title;
    var $title_image = false;
    var $iso_charset;
    var $decode_utf = false;
    
    var $log = array();
    
    
    function __construct() {

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $this->iso_charset = (!empty($conf['lang']['iso_charset'])) ? $conf['lang']['iso_charset'] : null;
        
        // only if not en and ISO-8859-1 
        if($conf['lang']['meta_content'] != 'en') {
           if($this->iso_charset && strtoupper($this->iso_charset) == 'ISO-8859-1') {
               $this->decode_utf = true;
           } 
        }
        
        $this->setSettings($this->setting);
        // $this->setSetting('charset', $conf['lang']['meta_charset']);
        
        $config = array(
            'document_root' => (isset($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : '',
            'http_host'     => (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '',
            'ssl'           => ($conf['ssl_client'])
        );
        
        $this->setConfig($config);
        
        $this->is_curl = function_exists('curl_version');
        $this->allow_url_fopen = ini_get('allow_url_fopen');
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
            // $msg = 'KBExportHtmldoc error: ' . $msg;
            echo $msg;
            $this->removeTempDir();
            die($code);
        }

        return array('code'=>$code, 'code_message'=>$msg);
    }
    
    
    function getErrorMessage($code, $value) {
        $msg = (isset($this->codes[$code])) ? sprintf($this->codes[$code], $value) : $this->codes[98];
        return $msg;
    }

    
    function checkAvailability($check_license = true) {

        if($check_license) {

            // check if htmldoc installed
            $htmldoc = BaseModel::isPluginExport();
            if($check_license === true && !$htmldoc) {
                return $this->error(-3);
            }
            
            // check kbp license key
            if(strtolower($htmldoc) !== 'demo') {
                if(!KBValidateLicense::isPluginExportKeysMatch()) {
                    return $this->error(-4);
                }
            } elseif(strtolower($htmldoc) == 'demo') {
                $this->setConfig(array('demo_mode' => 1));
                $this->setSetting('bodyimage', APP_EXTRA_MODULE_DIR . 'plugin/export/template/watermark.png');
            }            
        }
    
        // check php.ini disabled functions
        if (!function_exists('proc_open')) {
            return $this->error(-1);
        }
  
        // writeable
        if (!is_writable($this->config['temp_dir'])) {
            return $this->error(1003, $this->getErrorMessage(1003, $this->config['temp_dir']));
        }
    }

        
    // get categories array
    function &getCategoriesArray($tree_helper) {
        
        $cats = array();
        foreach($tree_helper as $cat_id => $level) {
            if($level == 0) {
                $top_cat_id = $cat_id;
            }
        
            $cats[$top_cat_id][$cat_id] = $level;
        }
    
        return $cats; 
    }


    // parse body, replace h-tags, etc
    function &parseBody($content) {
    
        $search[1] = '#<h\d[^>]*>(.*?)<\/h\d>#i'; 
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
        
        $output = $this->parseImage($output); 
        
        // echo '<pre>', print_r(htmlspecialchars($output), 1), '</pre>';
        // exit;
        
        return $output;
    }
    

    // default
    function parseImage($html) {
        return $html;
    }

    
    // get data for temp html file      
    function getCategoryFile($num, $categories, $manager, $controller, $view) {
                                                        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export/template/export_book_category.html');

        $tpl->tplAssign('document_title', $this->title);

        foreach($categories as $cat_id => $level) {

            $hnum = $level + 1;
            $v['hnum'] = $hnum;
            
            // get entries
            $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
            $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
            $manager->setSqlParams("AND cat.id = '{$cat_id}'", 'export'); 
            $manager->setSqlParamsOrder('ORDER BY ' . $manager->getSortOrder());

            $limit = ($this->config['demo_mode']) ? 3 : -1;  // limit 3 in  demo mode
            $entries = $manager->getEntryList($limit, 0, 'category');
            
            
            // custom
            if($entries) {
                $ids = $manager->getValuesString($entries, 'id');
                $custom =  $manager->getCustomDataByEntryId($ids, true);                
            }

            foreach(array_keys($entries) as $k) {
                $row = $entries[$k];
                
                if(DocumentParser::isTemplate($row['body'])) {
                    DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
                }
    
                // replace article link
                if(strpos($row['body'], '[link:article') !== false) {
                    $search = "#\[link:article\|(\d+)\]#";
                    $replace = "#entry_$1";
                    $row['body'] = preg_replace($search, $replace, $row['body']);    
                }

                // all articles replaced, replace files link only 
                if(DocumentParser::isLink($row['body'])) {
                    DocumentParser::parseLink($row['body'], array($controller, 'getLink'), 
                                              $manager, array(), $row['id'], $controller);    
                }
                
                if(DocumentParser::isCode($row['body'])) {
                    DocumentParser::parseCodePrint($row['body']);    
                }
                
                DocumentParser::parseCurlyBraces($row['body']);
        
                $row['hnum'] = $hnum + 1;           
                $row['body'] = &$this->parseBody($row['body']);
    
                // custom
                $row['custom_tmpl_top'] = '';
                $row['custom_tmpl_bottom'] = '';
                if(!empty($custom[$row['id']])) {
                    $custom_data = $view->getCustomData($custom[$row['id']]);
                    $row['custom_tmpl_top'] = $view->parseCustomData($custom_data[1], 1);
                    $row['custom_tmpl_bottom'] = $view->parseCustomData($custom_data[2], 2);
                    
                    // decode
                    if($this->decode_utf) {
                        $row['custom_tmpl_top'] = $this->decodeUTF8($row['custom_tmpl_top']);
                        $row['custom_tmpl_bottom'] = $this->decodeUTF8($row['custom_tmpl_bottom']);
                    }
                }
            
                $cat_type = $manager->getCategoryType($cat_id);
                $nobreak_types = array('faq', 'faq2');
                $row['page_break'] = (in_array($cat_type, $nobreak_types)) ? '' : '<!-- PAGE BREAK -->'; 
    
                // decode
                if($this->decode_utf) {
                    $row['title'] = $this->decodeUTF8($row['title']);
                    $row['body'] = $this->decodeUTF8($row['body']);
                }    
    
                $tpl->tplParse($row, 'category_row/entry_row');
            }
                

            // get description of category
            $cats_list = &$manager->getCategoryList($manager->categories[$cat_id]['parent_id']);
            $v['title'] = $manager->categories[$cat_id]['name'];
            $v['description'] = $cats_list[$cat_id]['description'];
            
            // decode
            if($this->decode_utf) {
                $v['title'] = $this->decodeUTF8($v['title']);
                $v['description'] = $this->decodeUTF8($v['description']);
            }
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse($v, 'category_row');
        }

        $tpl->tplParse();
        $tpl->tplPostParse(array($this, 'replaceMSWordCharacters'));
        $tpl->tplPostParse(array($this, 'replaceAccentsCharacters'));
        
        return $tpl->tplPrint(1);   
    }
    
    
    function getEntryFile($data, $msg) {
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export/template/export_book_entry.html');

        $data['body'] = &$this->parseBody($data['body']);

        // decode
        if($this->decode_utf) {
            $data['title'] = $this->decodeUTF8($data['title']);
            $data['body'] = $this->decodeUTF8($data['body']);
            $data['category_title_full'] = $this->decodeUTF8($data['category_title_full']);

            $data['custom_tmpl_top'] = $this->decodeUTF8($data['custom_tmpl_top']);
            $data['custom_tmpl_bottom'] = $this->decodeUTF8($data['custom_tmpl_bottom']);
            $data['formated_date'] = $this->decodeUTF8($data['formated_date']);

            $msg['entry_id_msg'] = $this->decodeUTF8($msg['entry_id_msg']);
            $msg['last_updated_msg'] = $this->decodeUTF8($msg['last_updated_msg']);
            $msg['revision_msg'] = $this->decodeUTF8($msg['revision_msg']);
        }

        if($this->config['print_entry_info']) {
            $tpl->tplSetNeeded('/entry_info');
        }

        $tpl->tplParse(array_merge($data, $msg));  
        $tpl->tplPostParse(array($this, 'replaceMSWordCharacters'));
        $tpl->tplPostParse(array($this, 'replaceAccentsCharacters'));
         
        return $tpl->tplPrint(1);  
    }
    
    
    // generate htmldoc Book file
    function &getBookFile($categories) {

        $data = array();
                            
        // htmldoc
        $version = $this->getVersion();
        $data[] = '#HTMLDOC ' . $version;
        // $data[] = '#HTMLDOC 1.8.13';
        $data[] = implode(' ', $this->setting) . ' ' . $this->custom_setting;  

        foreach(array_keys($categories) as $cat_id) {
            $data[] = $cat_id . '.html';
        }
                                            
        $data = implode("\n", $data);

        // echo '<pre>', print_r($data, 1), '</pre>';
        // exit;

        return $data;    
    }
    

    function setConfig($config) {
        foreach($config as $k => $v) {
            $this->config[$k] = $v;
        }
    }

    
    // function getConfig($key) {
    //     return $this->config[$key];
    // }

    
    // replace all default elements by user settings
    function setSettings($settings) {
        foreach($settings as $option => $param) {
            $this->setSetting($option, $param);
        }
    }
    
    
    // replace current default element by user setting
    function setSetting($option, $param) {
        $newParam = $this->parseSetting($option, $param);
        if($newParam) {
            $this->setting[$option] = $newParam;
        }
    }

    
    function parseSetting($option, $param) {
        if(isset($this->setting_rule[$option])) {
            $param = sprintf($this->setting_rule[$option], $param);
        } elseif($param) {
            $param = '--'. $option;
        } else {
            return null;
        } 
    
        return $param;
    }
    
    
    function deleteSetting($option) {
        unset($this->setting[$option]);
    }
     

    // for custom setting if user can set 
    // commented in database, htmldoc_params
    function setCustomSetting($cmd_str) {
        $custom = $this->parseCustomOptions($cmd_str);
                                                
        $data = array();
        foreach ($custom as $k => $v) {
            if (!in_array($k, $this->forbidden_custom_setting)) {
                $data[] = '--'. $v[0];
                $this->deleteSetting($k);
            }
        }       
 
        $this->custom_setting = implode(' ', $data);
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
    
    
    // write htmldoc Book file
    function writeBookFile($content) {
        $filename = $this->config['temp_dir'] . $this->config['book_file_name'];
        $ret = FileUtil::write($filename, $content);
        if(!$ret) {
            return $this->error(1006, $this->getErrorMessage(1006, $filename));
        }
    }


    // write html file for each top category
    function writeTmpFile($content, $id) {
        $filename = $this->config['temp_dir'] . $id . '.html';       
        $ret = FileUtil::write($filename, $content);        
        if(!$ret) {
            return $this->error(1006, $this->getErrorMessage(1006, $filename));
        }
    }
    
    
    // create output
    function execute($type) {
        
        // check path to book file
        $book_file = $this->config['temp_dir'] . $this->config['book_file_name'];
        
        // will die on normal operation, // return on test
        if (!file_exists($book_file)) {
            $error = $this->error(1004, $this->getErrorMessage(1004, $book_file));
            return array(array(), $error, arrat()); 
        }
                
        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w') // stderr
        );

        // call Htmldoc
        $cmd = '"' . $this->config['tool_path'] . 'htmldoc" --batch ' . $book_file;
        $process = proc_open($cmd, $descriptorspec, $pipes);

        $output = '';
        $result = '';

        if (is_resource($process)) {
            fclose($pipes[0]);
       
            // while(!feof($pipes[1])) {
            //     $output .= fgets($pipes[1], 1024);
            // }

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            

           // while(!feof($pipes[2])) {
           //      $error .= fgets($pipes[2], 1024);
           //  }
           //  $result = $error;

            $result = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }
        
        // if not image or it is not accessible in result we have 
        // KBExportHtmldoc error: ERR404: ... (http://domain/Feedback.gif)
        // KBExportHtmldoc error: ERR401: Unauthorized (http://domain/Feedback.gif)
        // it seems $exit_status = count(all errors);

        $exit_status = proc_close($process); // 0 is success

        $error = '';
        if($exit_status != 0) {
            $msg = ($result) ? $result : $this->codes[1002];
            $error = $this->error($exit_status, trim($msg));
        }
        
        
        // echo '<pre>cmd: ', print_r($cmd, 1), '</pre>';
        // echo '<pre>output: ', print_r($output, 1), '</pre>';
        // echo '<pre>error: ', print_r($error, 1), '</pre>';
        // echo '<pre>exit_status: ', print_r($exit_status, 1), '</pre>';
        // exit;

        // echo '<pre>', print_r(array($output, $error, $result), 1), '</pre>';
        // exit;

        return array($output, $error, $result);
    }
    

    function getVersion() {
        $cmd = $this->config['tool_path'] . 'htmldoc --version';
        $version = exec($cmd);
        return $version;
    }

    
    function setTitle($title = false, $stuff = false) {

        $this->title = $title;
        $image = false;
                                                       
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export/template/export_title.html');
        
        $tpl->tplAssign('title', ($title) ? $title : '');  
        
        if($stuff) {
            // $image = APP_CLIENT_PATH . 'file.php?type=image&id=' . $stuff['id'];
            $fmore = array('id'=>$stuff['id']);
            $image = AppController::getAjaxLinkToFile('image', $fmore);
            
            // save titleimage to file system
            if ($this->type == 'html' || $this->type == 'htmlsep') {
                $image_path = $this->config['temp_dir'] . '/output/' . $stuff['filename'];
            
                if (copy($image, $image_path)) {
                    $this->title_image = $stuff;
                } else {
                    $this->error(1008, $this->getErrorMessage(1008, $image));      
                }
            }
            
            $image_html = ($this->type == 'pdf') ? '<img src="' . $image . '" />' : '[IMG_REPLACE]';
                  
        } else {
            $image_html = '';
        }                                           
        
        $tpl->tplAssign('imagetitle', $image_html); 
               
        $tpl->tplParse();
        $data = $tpl->tplPrint(1);
                              
        if (FileUtil::write($this->config['temp_dir'] . 'title.html', $data)) {
            $this->setSetting('titlefile', $this->config['temp_dir'] . 'title.html');            
        }
    }
    
    
    function setArchiveType($type) {
        $this->archive_type = $type;
    }
    
    
    // EXPORT DIRECTORIES
    static function getTempDir($dir, $type) {
        $dir = preg_replace('#\\\\#', '/', $dir);     
        $sub_dir = 'export_' . md5('export' . $type . time());
        $export_dir = $dir . '/' . $sub_dir . '/';
        $export_dir = str_replace('//', '/', $export_dir);
        
        return $export_dir;
    }


    function createTempDir() {
        $this->createDir($this->config['temp_dir']);
    }


    function createDir($dir) {
        if(!is_dir($dir)) {
            $oldumask = umask(0);
            $r = mkdir($dir, 0777);
            umask($oldumask);
            
            if (!$r) {
                $this->error(1005, $this->getErrorMessage(1005, $dir));
            }
        }
        
        if(!is_writeable($dir)) {
            $this->error(1003, $this->getErrorMessage(1003, $dir));
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
    
    
    function replaceAccentsCharacters($string) {
        
        $table = array(
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'ç' => 'c', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u',
            'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'Ç' => 'C', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I',
            'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ÿ' => 'Y'
        );

        return strtr($string, $table);
    }
    
}
?>