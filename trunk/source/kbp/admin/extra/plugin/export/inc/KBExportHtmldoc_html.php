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


class KBExportHtmldoc_html extends KBExportHtmldoc 
{
    
    // default settings
    var $setting = array(
        'format' => 'html'
    );
    
    // directory for htmldoc output
    var $tmp_subdir = ''; 
    

    function &export($tree_helper, $manager, $controller, $view) {
        
        $this->checkAvailability();

        $cats = &$this->getCategoriesArray($tree_helper);

        $data = &$this->getBookFile($cats);        
        $this->writeBookFile($data);

        $num = 0;
        foreach($cats as $top_category_id => $cats) {
            $num ++;
            $data = $this->getCategoryFile($num, $cats, $manager, $controller, $view);
            $this->writeTmpFile($data, $top_category_id);
        }
        
        $output = $this->execute('html');
        $output = array($this->compress(), $output[1], $output[2]);
        return $output;
    }

    
    function compress() {
        if(extension_loaded('zip')) {
            return $this->zipFiles(); // php zip extension
            
        } else {
            return $this->zipFiles2(); // pear
        }
    }
    

    function zipFiles2() {
    
        $files = array($this->tmp_subdir);
        
        if($this->config['demo_mode']) {
            $files[] = APP_EXTRA_MODULE_DIR . 'plugin/export/template/watermark.png';
        }
        
        // replace img in titlefile
        if ($this->title_image) {
            $titlefile_path = $this->config['temp_dir'] . 'output/index.html';
            $titlefile_data = FileUtil::read($titlefile_path);
            
            $src = '<img src="' . $this->title_image['filename'] . '" />';
            $titlefile_data = str_replace('[IMG_REPLACE]', $src, $titlefile_data);

            if (!FileUtil::write($titlefile_path, $titlefile_data)) {
                $this->error(1006, $this->getErrorMessage(1006, $titlefile_path)); 
            }
        }
        
        $archive = $this->config['temp_dir'] . '/export.' . $this->archive_type;
        $archive = str_replace('//', '/', $archive);
        
        File_Archive::setOption('zipCompressionLevel', 0);        
        
        File_Archive::extract(
            $files,
            File_Archive::toArchive(
                $archive,
                $writer
            )
        );
            
        return FileUtil::read($archive);    
    }
    
    
    function zipFiles() {
        
        // replace img in titlefile
        if ($this->title_image) {
            $titlefile_path = $this->config['temp_dir'] . 'output/index.html';
            $titlefile_data = FileUtil::read($titlefile_path);
            
            $src = '<img src="' . $this->title_image['filename'] . '" />';
            $titlefile_data = str_replace('[IMG_REPLACE]', $src, $titlefile_data);

            if (!FileUtil::write($titlefile_path, $titlefile_data)) {
                $this->error(1006, $this->getErrorMessage(1006, $titlefile_path)); 
            }
        }
        
        $file_path = $this->config['temp_dir'] . '/export.' . $this->archive_type;
        $file_path = str_replace('//', '/', $file_path);
        
        $zip = new ZipArchive;
        
        if (!$zip->open($file_path, ZipArchive::CREATE)) {
            exit("Cannot create $filename\n");
        }
        
        if($this->config['demo_mode']) {
            $zip->addFile(APP_EXTRA_MODULE_DIR . 'plugin/export/template/watermark.png', 'watermark.png');
        }
        
        require_once 'eleontev/Dir/MyDir.php';
        $d = new MyDir;
        
        $files = &$d->getFilesDirs($this->tmp_subdir);

        foreach ($files as $k => $v) {
            if (is_array($v)) {
                $zip->addEmptyDir($k);
                
                foreach ($v as $v1) {
                    $tmp_file_path = sprintf('%s%s/%s', $output_dir, $k, $v1);
                    $zip->addFromString(sprintf('%s/%s', $k, $v1), file_get_contents($tmp_file_path));
                }
                
            } else {
                $zip->addFromString($v, file_get_contents($this->tmp_subdir . $v));
            }
        }
        
        $zip->close();
        
        $data = FileUtil::read($file_path);
        return $data;
    }
    
    
    function parseImage($output) {

        $search = array();
        $replace = array();
        
        $bad_image_str = '<div style="background: #dddddd;text-align: center;">%s</div>';
                                              
        $images_dir = $this->config['temp_dir'] . '/images/';
        
        preg_match_all('/<img[^>]+>/i', $output, $result);
                             
        foreach ($result[0] as $tag) {

            $image = array();
            $new_tag = $tag;
            
            $bad_image = false;
            
            preg_match_all('/src="([^"]*)"/i', $tag, $image);
            
            // $src = $image[1][0];
            $src = rawurldecode($image[1][0]);
            $initial_src = $src;
            $is_remote = (strpos($src, 'http://') !== false || strpos($src, 'https://') !== false);
            $is_embedded = (strpos($src, 'data:image') !== false);
            
            if ($is_embedded) {
                
                list($type, $data) = explode(';', $src);
                
                $extension = substr($type, 11);
                $data = explode(',', $data);
                $data = $data[1];
                $filename = sprintf('%s%s.%s', $images_dir, md5($data), $extension);
                $data = base64_decode($data);
                
                if (!FileUtil::write($filename, $data)) {
                    $this->error(1006, $this->getErrorMessage(1006, $filename)); 
                }
                
                $src = basename($filename);
                
            } elseif (!$is_remote) { // it's a local image
                if ($this->copy_images == 1) {
                    $src = APP_CLIENT_PATH . $src;
                    $src = str_replace('//', '/', $src);
                                    
                } else {
                    $src = $this->config['document_root'] . $src;
                    $src = str_replace('//', '/', $src);   
                }
            }            
            
            if ($this->copy_images != 1 && !$is_embedded) { // copy an image, an embedded already's been copied in spite of settings
            
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
                
                if ($is_remote && $this->is_curl) {
                    if ($this->convert_https) {
                        $src = str_replace('https://', 'http://', $src);
                    }
                    
                    $src = str_replace(' ','%20', $src);
                    $ch = curl_init($src);
                    $fp = fopen($new_src, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    fclose($fp);
                    
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if($http_code == 200) {
                        if (getimagesize($new_src)) {
                            $remove_tmp_file = false;
                            
                            $src = basename($new_src);
                            
                        } else { // the file's not an image
                            $remove_tmp_file = true;
                        }
                        
                    } else { // got a bad response, remove the temp file anyway
                        $remove_tmp_file = true;
                    }
                    
                    curl_close($ch);
                    
                    if ($remove_tmp_file) {
                        //$this->error(1008, $this->getErrorMessage(1008, $src));
                        $bad_image = true;
                        
                        unlink($new_src);
                    }
                        
                } elseif (!$is_remote || $this->allow_url_fopen) {
                    if (file_exists($src)) {
                        if (copy($src, $new_src)) {
                            $src = basename($new_src);
                            
                        
                        } else {
                          //  $this->error(1008, $this->getErrorMessage(1008, $src));
                          $bad_image = true;
                        }
                        
                    } else {
                       // $this->error(1008, $this->getErrorMessage(1008, $src));
                       $bad_image = true;
                    }
                    
                } else {
                    //  $this->error(1008, $this->getErrorMessage(1008, $src));
                    $bad_image = true;
                }
                
            }
            
            
            if ($bad_image) {
                $this->log($this->getErrorMessage(1009, $initial_src));
                
                $text = ($is_embedded) ? 'Encoded Image' : basename($html_val['src']);
                $new_tag = sprintf($bad_image_str, $text);
                
            } else {
                $new_tag = preg_replace('/src="([^"]*)"/i', 'src="' . $src . '"', $tag);
            }
            
            $search[] = $tag;
            $replace[] = $new_tag;
        }
        //  echo '<pre>';die(var_dump($search, $replace));                    
        if($search) {  
            $output = str_replace($search, $replace, $output);
        }

        return $output;
    }

}
?>