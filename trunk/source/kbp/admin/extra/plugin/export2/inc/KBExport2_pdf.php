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


class KBExport2_pdf extends KBExport2
{
    
    var $toc_entry_link_str = '#entry_%d_%d';
    
    static $wkpdftohtml_vars = array(
        'page', 'frompage', 'topage',
        'section', 'subsection',
        'date', 'isodate',
        'time', 'title'
    );
       

    function &export($tree_helper) {
        
        $this->checkAvailability();
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_page.html');
        
        $tpl->tplAssign('title', $this->config['title']);
        
        // cover file
        if (!empty($this->config['settings']['cover'])) {
            $this->writeCoverFile();
        }
        
        if (!empty($this->config['settings']['header'])) {
            $this->writeCustomFile('header');
        }
        
        if (!empty($this->config['settings']['footer'])) {
            $this->writeCustomFile('footer');
        }
        
        
        list($entries, $content) = $this->proceedCategories($tree_helper);
        $tpl->tplAssign('content', $content);
        
        $tpl->tplParse();
        $this->writeTmpFile('index', $tpl->tplPrint(1));
        
        $this->copyCssFile();
        
        $output_file = $this->config['temp_dir'] . 'export.pdf';
        $cmd = $this->getCmd($output_file);
        
        exec($cmd, $output, $return);
        if ($return) {
            $this->error(1004, $this->getErrorMessage(1006, array($return)));
        }
        
        $files_num = 1;
        $filesize = WebUtil::getFileSize($this->config['temp_dir'] . 'export.pdf');
        $msg = 'Files generated: %d, Size: %s';
        $msg = sprintf($msg, $files_num, $filesize);
        $this->log($msg);
        
        $data = FileUtil::read($output_file);
        return $data;
    }
    
    
    function getCmd($output_file, $toc = true) {
		
        // options
        $str = ' --%s-%s "%s"';
        $params = '';
        $temp_dir = $this->config['temp_dir'];
        
        $params = '-q';
        
        if (!empty($this->config['settings']['header'])) {
            $params .= sprintf(' --header-html "%soutput/header.html"', $temp_dir);
	        if (!empty($this->config['settings']['margin_top'])) {
	            $params .= sprintf(' --margin-top %d', $this->config['settings']['margin_top']);
	        }
		}
		
        if (!empty($this->config['settings']['footer'])) {
            $params .= sprintf(' --footer-html "%soutput/footer.html"', $temp_dir);
	        if (!empty($this->config['settings']['margin_bottom'])) {
	            $params .= sprintf(' --margin-bottom %d', $this->config['settings']['margin_bottom']);
	        }
		}
        
		// $params .= sprintf(' --margin-left %d', 0);
		// $params .= sprintf(' --margin-right %d', 0);
		
        if (!empty($this->config['settings']['dpi'])) {
            $params .= sprintf(' --dpi %d', $this->config['settings']['dpi']);
        }
        
        if (!empty($this->config['settings']['cover'])) {
            $params .= sprintf(' cover "%soutput/cover.html"', $temp_dir);
        }
        
        if ($toc) {
            $params .= ' toc --toc-text-size-shrink 1';
        }
        

        $title = @$this->manager->categories[$this->config['category_id']]['name'];
        $cmd_str = '"%swkhtmltopdf" -O %s %s "%soutput/index.html" "%s"';
        $cmd = sprintf(
            $cmd_str,
            $this->config['tool_path'],
            $this->config['settings']['orientation'],
            $params,
            $temp_dir,
            $output_file);
       
        // echo '<pre>', print_r($this->config	,1), '<pre>';
        // echo '<pre>', print_r($cmd, 1), '</pre>';
        // exit;
        
        return $cmd;
    }
    
    
    function parsePlaceholders(&$html) {
		
        $v = (empty($this->config['placeholders'])) ? array() : $this->config['placeholders'];
        
        if (!empty($this->config['category_id'])) { // plugin
            $cat_id = $this->config['category_id'];
            
            $v['top_category_title'] = $this->manager->categories[$cat_id]['name'];
            $v['top_category_description'] = $this->manager->categories[$cat_id]['description'];
            $v['export_title'] = $this->config['title'];
            
        } elseif (!empty($this->view->category_id)) { // client
            $v['article_title'] = $this->config['title'];
            
            $cat_id = $this->view->category_id;
            $v['category_title'] = $this->manager->categories[$cat_id]['name'];
            $v['category_description'] = $this->manager->categories[$cat_id]['description'];
        }
        
        foreach(self::$wkpdftohtml_vars as $var) {
            $v[$var] = sprintf('<span class="%s"></span>', $var);
        }
        
        $r = new Replacer();
        $r->s_var_tag = '[';
        $r->e_var_tag = ']';
        //$r->strip_var_sign = '--';
        
        $html = $r->parse($html, $v);
    }
    
    
    function writeCoverFile() {
        $html = $this->config['settings']['cover'];
        $this->parsePlaceholders($html);
        $this->writeTmpFile('cover', $html);
    }
    
    
    function writeCustomFile($type) {
		
        $html = $this->config['settings'][$type];
        $this->parsePlaceholders($html);
		
        $tpl2 = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_pdf_header.html');
        $tpl2->tplAssign('content', $html);
        $tpl2->tplParse();
        
        $this->writeTmpFile($type, $tpl2->tplPrint(1));
    }
    
    
    function copyCssFile() {
		
        $source = APP_EXTRA_MODULE_DIR . 'plugin/export2/template/style.css';
        $dest = $this->config['temp_dir'] . 'output/style.css';
        
        if (!copy($source, $dest)) {
            $this->error(1004, $this->getErrorMessage(1004, array($source, 0)));
        }
        
        $body_css = sprintf('font-family: %s; font-size: %spt;', $this->config['settings']['font'], 
															     $this->config['settings']['fontsize']);
        if ($this->config['demo_mode']) {
            $body_css .= sprintf('background: url(%s);', APP_EXTRA_MODULE_DIR . 'plugin/export/template/watermark.png');
        }
        
        $data = FileUtil::read($dest);
        // $data = preg_replace('#font-family:[\w, ];#');
        $data = str_replace(
            'font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;',
            $body_css,
            $data);
			
        FileUtil::write($dest, $data);
    }
    
    
    function proceedCategories($tree_helper) {
        
        $full_path = $this->manager->getCategorySelectRangeFolow();
        
        $entries_names = array();
        
        $tpl = new tplTemplatez(APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_html.html');
        
        $entries_with_related = array();

        foreach($tree_helper as $cat_id => $level) {
            
            //if ($this->config['category_id'] != $cat_id) {
                $tpl->tplSetNeeded('category_row/category_info');
            //}

            $hnum = $level + 1;
            $v['hnum'] = $hnum;

            // get entries
            $entries = $this->getEntries($cat_id);

            // custom
            if($entries) {
                $ids = $this->manager->getValuesString($entries, 'id');
                $custom =  $this->manager->getCustomDataByEntryId($ids, true);                
            }

            foreach(array_keys($entries) as $k) {
                $row = $entries[$k];
                
                $this->parseEntry($row, $custom, $full_path, $entries_with_related);
            
                $cat_type = $this->manager->getCategoryType($cat_id);
                $nobreak_types = array('faq', 'faq2');                
                
                // entry info
                // if($this->config['print_entry_info']) {
                //     $tpl->tplSetNeeded('entry_row/entry_info');
                //     $tpl->tplAssign($this->view->msg);
                // }
                
                $row['anchor'] = sprintf('entry_%d_%d', $row['id'], $cat_id);
                $row['hnum'] = $hnum + 1;
                
                $tpl->tplParse($row, 'category_row/entry_row');
                
                $entries_names[$row['category_id']][$row['id']] = $row['title'];
            }
                

            $v['title'] = $this->manager->categories[$cat_id]['name'];
            $v['description'] = $this->manager->categories[$cat_id]['description'];
            $v['anchor'] = sprintf('cat_%d', $cat_id);
            
            // decode
            if($this->decode_utf) {
                $v['title'] = $this->decodeUTF8($v['title']);
                $v['description'] = $this->decodeUTF8($v['description']);
            }
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse($v, 'category_row');
            
            
            // $tpl->tplParse();
            $tpl->tplPostParse(array($this, 'replaceMSWordCharacters'));
        }
        
        $data = $tpl->tplPrint(1);
        
        if ($this->convert_links) {
            $entry_to_cats = $this->getEntryToCatsArray($entries_names);
            $this->parseRelated($data, $entry_to_cats);   
        }
        
        return array($entries_names, $data);   
    }
    
    
    function exportEntry($rows) {
		
        $this->checkAvailability();
        
        if (!empty($this->config['settings']['cover'])) {
            $this->writeCoverFile();
			// $this->writeCustomFile('cover');
        }
        
        if (!empty($this->config['settings']['header'])) {
            $this->writeCustomFile('header');
        }
        
        if (!empty($this->config['settings']['footer'])) {
            $this->writeCustomFile('footer');
        }
        
        $data = array();
        foreach ($rows as $row) {
            $entries_with_related = array();
            $this->parseEntry($row, $row['custom'], $row['full_path'], $entries_with_related);
            
            $data[] = $this->getEntryFile($row, false, false);
        }
        
        $data = implode('<div style="page-break-after: always;"></div>', $data);
        $this->writeTmpFile('index', $data);
        
        $this->copyCssFile();
        
        $output_file = $this->config['temp_dir'] . 'export.pdf';
        $cmd = $this->getCmd($output_file, false);
        
        exec($cmd, $output, $return);
        if ($return) {
            $this->error(1004, $this->getErrorMessage(1006, array($return)));
        }
        
        $data = FileUtil::read($output_file);
        return $data;
    }
    
    
    function validate($tool_path, $check_license = false) {
                                                            
        $test_file = APP_EXTRA_MODULE_DIR . 'plugin/export2/template/export_test.html';
        
        if (!file_exists($test_file) || !is_readable($test_file)) { 
            return $this->error(-2, $this->getErrorMessage(-2, $test_file));
        }
        
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'tool_path' => $tool_path,
            'http_host' => $_SERVER['HTTP_HOST'],
            'temp_dir' => KBExport2::getTempDir(APP_CACHE_DIR, 'pdf_test'),
            'settings' => array(
                'orientation' => 'Portrait'
            )
        );
        
        $this->die_error = false;
        $this->setConfig($config);                
        $this->createTempDirs();
        
        
        // check if WKHTMLTOPDF installed
        // $wkhtmltopdf = BaseModel::isPluginExport2Pdf();
        // if($check_license === true && !$wkhtmltopdf) {
        //     return $this->error(-3);
        // }
        
        if($ret = $this->checkAvailability($check_license)) {
             return $ret;
        }
        
        
        $tpl = new tplTemplatez($test_file);
        
        $tpl->tplParse();
        $this->writeTmpFile('index', $tpl->tplPrint(1));
        
        
        $output_file = $this->config['temp_dir'] . 'export.pdf';
        $cmd = $this->getCmd($output_file, false);
        
        exec($cmd, $output, $return);
        if ($return) {
            return $this->error($return, $this->getErrorMessage(1007, $return));
        }
                
        return true;
    }
    
}
?>