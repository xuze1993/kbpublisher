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


class KBClientView_pdf extends KBClientView_common
{

    function execute($manager) {
        
        $is_plugin = BaseModel::isPluginPdf($manager->setting); // true or 'demo'
        
        if(!$is_plugin) {
            $this->controller->go('index', $this->category_id, false, 'no_export_plugin');
        }
        
        $plugin = BaseModel::getPluginPdf($manager->setting);
        
        $category = ($this->view_id == 'pdf-cat');
        $list = (!empty($_GET['id']));
        
        if($category) {
            return $this->getCategory($manager, $plugin);
            
        } elseif($list) {
            return $this->getEntryList($manager, $plugin);
        
        } else {
            return $this->getEntry($manager, $plugin);
        }
    }
    
    
    
    function getCategory($manager, $plugin) {
        if($plugin == 'wkhtmltopdf') {
            return $this->getCategoryWkHtmlToPdf($manager);
        
        } elseif($plugin == 'htmldoc') {
            return $this->getCategoryHtmlDoc($manager);
        }
        
        return false;
    }
    

    function getCategoryHtmlDoc($manager) {

        require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExport.php';
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExportHtmldoc.php';


        $cats = KBExport::getData($manager, $this->category_id);
        $full_cats = $manager->getCategorySelectRangeFolow();
        $cat_name = $full_cats[$this->category_id];
        
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir'      => KBExportHtmldoc::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path'     => $manager->getSetting('plugin_htmldoc_path'),
            'http_host'     => $_SERVER['HTTP_HOST']
        );
        
        $settings = array(
            'book'        => true,
            'no-title'    => true,
            'fontsize'    => $manager->getSetting('htmldoc_fontsize'),
            'bodyfont'    => $manager->getSetting('htmldoc_bodyfont')
            // 'fontspacing' => 1.2
        );

        $export = KBExport::factory('pdf');
        $export->setConfig($config);
        $export->setSettings($settings);
        $export->createTempDir();

        $data = $export->export($cats, $manager, $this->controller, $this);

        $export->removeTempDir();
                               
        $params['data'] = &$data[0];
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, $cat_name . '.pdf', false);
    }
    
    
    function getCategoryWkHtmlToPdf($manager) {

        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2.php';
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2_pdf.php';


        $cats = KBExport2::getData($manager, $this->category_id);
        $full_cats = $manager->getCategorySelectRangeFolow();
        $cat_name = $full_cats[$this->category_id];
		
		$config = $this->getWkHtmlToPdfConfig($manager->setting);		
		$config['category_id'] = $this->category_id;
        $config['print_entry_info'] = false; // disabled in client for category

        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->export($cats);

        $export->removeTempDir();
                               
        $params['data'] = $data;
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, $cat_name . '.pdf', false);
    }
    
    
        
    function getEntryList($manager, $plugin) {
        if($plugin == 'wkhtmltopdf') {
            return $this->getEntryListWkHtmlToPdf($manager);
        }
        
        return false;
    }
    
    
    function getEntryListWkHtmlToPdf($manager) {
        
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2.php';
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2_pdf.php';
        
        $manager->setSqlParams(sprintf("AND e.id IN (%s)", implode(',', $_GET['id'])));
        $rows = $manager->getEntryList(-1, -1);
        
        foreach (array_keys($rows) as $k) {
            $rows[$k] = $this->_getEntryBody($manager, $rows[$k]);
        }
		
		$config = $this->getWkHtmlToPdfConfig($manager->setting);
		
        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->exportEntry($rows);
        $export->removeTempDir();
        
        $params['data'] = $data;
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, 'export.pdf', false);
    }


    function getEntry($manager, $plugin) {
    
        if($plugin == 'wkhtmltopdf') {
            return $this->getEntryWkHtmlToPdf($manager);
    
        } elseif($plugin == 'htmldoc') {
            return $this->getEntryHtmlDoc($manager);
        }
    
        return false;
    }


    function getEntryHtmlDoc($manager) {
                   
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExport.php';
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export/inc/KBExportHtmldoc.php';

                
           // get entry
        $row = ($this->view_id == 'pdf-trouble') ? $this->_getTroubleBody($manager) 
                                                 : $this->_getEntryBody($manager);    
        if(empty($row)) { return; }

        $config = array(
            'document_root'    => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir'         => KBExportHtmldoc::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path'        => $manager->getSetting('plugin_htmldoc_path'),
            'http_host'        => $_SERVER['HTTP_HOST'],
            'print_entry_info' => $manager->getSetting('show_pdf_link_entry_info')
        );
        
        $settings = array(
            'webpage'     => true,
            'no-title'    => true,
            'no-toc'      => true,
            'fontsize'    => $manager->getSetting('htmldoc_fontsize'),
            'bodyfont'    => $manager->getSetting('htmldoc_bodyfont')
            // 'fontspacing' => 1.2
        );

        $export = KBExport::factory('pdf');
        $export->setConfig($config);
        $export->setSettings($settings);
        $export->createTempDir();

        $data = $export->exportEntry($row, $this->msg); 
                              
        $export->removeTempDir();

        $params['data'] = &$data[0];
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, $row['title'] . '.pdf', false); 
    }
    
    
    function getEntryWkHtmlToPdf($manager) {
                   
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2.php';
        require_once APP_EXTRA_MODULE_DIR . 'plugin/export2/inc/KBExport2_pdf.php';
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);
        
        if(empty($row)) {
            return;
        }
        
        $row['full_path'] = $manager->getCategorySelectRangeFolow();
        $row['custom'][$this->entry_id] = $manager->getCustomDataByEntryId($this->entry_id);
        
        if(DocumentParser::isLink($row['body'])) {
            $related = &$manager->getEntryRelatedInline($this->entry_id);
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                      $related, $row['id'], $this->controller);
        }
        
        $config = $this->getWkHtmlToPdfConfig($manager->setting);
        $config['title'] = $row['title'];
		
        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->exportEntry(array($row));
        $export->removeTempDir();
        
        $params['data'] = $data;
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, $row['title'] . '.pdf', false); 
    }

	
	function getWkHtmlToPdfConfig($setting) {
		
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir' => KBExport2::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path' => $setting['plugin_wkhtmltopdf_path'],
            'http_host' => $_SERVER['HTTP_HOST'],
            'print_entry_info' => $setting['show_pdf_link_entry_info'],
            'title' => '',
            'settings' => array(
				'orientation' => 'portrait',
                'fontsize' => $setting['htmldoc_fontsize'],
                'font' => $setting['htmldoc_bodyfont'],
				'dpi' => $setting['plugin_wkhtmltopdf_dpi'],
				'margin_top' => $setting['plugin_wkhtmltopdf_margin_top'],
				'margin_bottom' => $setting['plugin_wkhtmltopdf_margin_bottom']
			)
        );
		
        $keys = array('header', 'footer');
        foreach ($keys as $key) {
            $param = 'plugin_export_' . $key;
            if ($setting[$param]) {
                $config['settings'][$key] = $setting[sprintf('plugin_export_%s_tmpl', $key)];
            }
        }
		
		return $config;
	}


    function &_getEntryBody($manager, $row = false) {
        
        if (!$row) {
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
            $row = $this->stripVars($row);
            
            if(empty($row)) { return; }
        }
        
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$row['category_id']];
        
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }        
        
        if(DocumentParser::isLink($row['body'])) {
            $related = &$manager->getEntryRelatedInline($this->entry_id);
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                      $related, $row['id'], $this->controller);
        }    
        
        if(DocumentParser::isCode($row['body'])) {
            DocumentParser::parseCodePrint($row['body']);    
        }
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom    
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows);

        $row['custom_tmpl_top'] = $this->parseCustomData($custom_data[1], 1);
        $row['custom_tmpl_bottom'] = $this->parseCustomData($custom_data[2], 2);                


        $row['body'] = $this->controller->_replaceArgSeparator($row['body']);
        
        $row['category_title_full'] = $full_path;
        $row['formated_date'] = $this->getFormatedDate($row['date_updated']);
        $row['revision'] = $manager->getRevisionNum($this->entry_id);

        $link = $this->controller->getLink('entry', $this->category_id, $this->entry_id);
        $row['entry_link'] = $this->controller->_replaceArgSeparator($link);
        
        $updater = $manager->getUserInfo($row['updater_id']);
        $row['updater'] = $updater['first_name'] . ' ' . $updater['last_name'];
        
        return $row;
    }
    
    
    function &_getTroubleBody($manager) {
                                                         
        $entry = &$manager->getEntry($this->entry_id);
        $rows = &$manager->getSteps($this->entry_id); 
        if(empty($rows)) { return; }

        
        $tpl = new tplTemplatez($this->template_dir . 'trouble_entry_print.html');
        $tpl->strip_vars = true;
        $tpl->strip_double = true;
        
    
        $manager->tbl->related_to_entry = $manager->tbl->article_to_step;
        $manager->tbl->entry = $manager->tbl->kb_entry; 
        $manager->tbl->category = $manager->tbl->kb_category;
                                                                  
        //$related = &$manager->getEntryRelatedInline(implode(',', array_keys($rows)));
        $related = &$manager->getEntryRelatedInline($this->entry_id);
        
        
        $tree = new TreeHelper();
        foreach($rows as $id => $row) {
            $tree->setTreeItem($id, $row['parent_id']);
        }
        
        $tree_helper = $tree->getTreeHelper(0);
                   
        $step_num = array();
        $padding = 25;  
        
        foreach($tree_helper as $id => $level) {
            
            // step num
            if ($rows[$id]['active']) {
                if (!empty($step_num)) {
                    $max_level = max(array_keys($step_num));
                    $levels_to_clear = $max_level - $level;
            
                    for ($i = 0;$i < $levels_to_clear; $i ++) {
                        unset($step_num[count($step_num) - 1]);
                    }  
                }                
                       
                
                if (!isset($step_num[$level])) {
                    $step_num[$level] = 0;
                }
                $step_num[$level] ++;
            
                $v['step_num'] = implode('.', $step_num);
            }
            

            $v['title'] = $rows[$id]['title'];
            $v['padding'] = $padding*$level;
            $v['padding_category'] = 5;
            
            if(DocumentParser::isLink($rows[$id]['body'])) {
                DocumentParser::parseLink($rows[$id]['body'], array($this, 'getLink'), $manager, 
                                            $related, $id, $this->controller);
            }
            
            if(DocumentParser::isTemplate($rows[$id]['body'])) {
                DocumentParser::parseTemplate($rows[$id]['body'], array($manager, 'getTemplate'));
            }            
            
            DocumentParser::parseCurlyBraces($rows[$id]['body']);
            
            $tpl->tplParse(array_merge($v, $rows[$id]), 'row_title');
            $tpl->tplParse(array_merge($v, $rows[$id]), 'row_body');  
        }

        $tpl->tplAssign('meta_charset', $this->conf['lang']['meta_charset']);
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$entry['category_id']];
        $tpl->tplAssign('category_title_full', $full_path);
        $tpl->tplAssign('entry_link', $this->getLink('trouble', $this->category_id, $this->entry_id));
        $tpl->tplAssign($this->css);       
        
        $tpl->tplParse($entry);
        
        $row = $entry;
        $full_path = &$manager->getCategorySelectRangeFolow();
        
        $row['category_title_full'] = $full_path[$entry['category_id']];
        $row['formated_date'] = $this->getFormatedDate($entry['date_updated']); 
        $row['body'] = $tpl->tplPrint(1);
        
        $link = $this->controller->getLink('trouble', $this->category_id, $this->entry_id);
        $row['entry_link'] = $this->controller->_replaceArgSeparator($link);
        
        return $row;
    }
    
    // rewrite 
    function getCustomDataCheckboxValue() {
        return 'image';
    }
}
?>