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


class KBPReportView_default extends AppView
{
        
    
    function execute(&$obj, &$manager, $data) {

        $this->addMsg('kbpsetup_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . 'form.html');


        $file_setting_link = $this->controller->getLink('setting', 'admin_setting');
        $admin_setting_link = $this->controller->getLink('setting', 'admin_setting');
        $plugin_setting_link = $this->controller->getLink('setting', 'plugin_setting', 'export_setting');
        $sphinx_setting_link = $this->controller->getLink('setting', 'plugin_setting', 'sphinx_setting');
        $public_setting_link = $this->controller->getLink('setting', 'public_setting', 'kbc_setting');
        
        $setting_links = array(
            'file_dir' => $file_setting_link,
            'html_editor_upload_dir' => $admin_setting_link,
            'xpdf' => $file_setting_link,
            'catdoc' => $file_setting_link,
            'antiword' => $file_setting_link,
            'htmldoc' => $plugin_setting_link,
            'wkhtmltopdf' => $plugin_setting_link,
            'spell' => $public_setting_link,
            'sphinx' => $sphinx_setting_link
        );
        
        $setting_info = array(
            'cache_dir' => '$conf["cache_dir"] in [kbp_dir]/admin/config.inc.php'
        );

        $instruction_links = array(
            'cron' => 'http://www.kbpublisher.com/kb/Setting-up-scheduled-tasks_238.html',
            'zip' => 'http://php.net/manual/en/zip.setup.php',
            'xpdf' => 'http://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'catdoc' => 'http://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'antiword' => 'http://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'htmldoc' => 'http://www.kbpublisher.com/kb/Enable-exporting-to-PDF_303.html',
            'spell' => 'http://www.kbpublisher.com/kb/enable-searching-spell-suggest_402.html'
        );

        
        if (empty($data)) {
            $tpl->tplSetNeeded('/no_report');
        } else {
    
            $rows = unserialize($data['data_string']);

            foreach($rows as $k => $val) {
                            
                $v['title'] = $this->msg[$k]['title'];
                $v['descr'] = $this->msg[$k]['descr'];
                
                $code_msg = $manager->code[$val['code']];
                $sign = ($val['code'] == 0 || $val['msg']) ? $sign = ': ' : '';
                $status_msg = '<b>%s</b>%s%s';
                $v['msg'] = sprintf($status_msg, $this->msg[$code_msg . '_msg'], $sign, $val['msg']);
                

                $v['setting'] = '';
                if(isset($setting_links[$k])) {
                    $str = '<a href="%s">%s</a>';
                    $v['setting'] = sprintf($str, $setting_links[$k], $this->msg['setting_msg']);
                }

                if(isset($setting_info[$k])) {
                    $v['setting'] = $setting_info[$k];
                }

                $v['instruction'] = '';
                if(isset($instruction_links[$k])) {
                    $delim = ($v['setting']) ? ' | ' : '';
                    $str = '%s<a href="%s">%s</a>';
                    $v['instruction'] = sprintf($str, $delim, $instruction_links[$k], $this->msg['instruction_msg']);
                }

                $tpl->tplAssign($this->getViewListVarsCustom($k, $val['code']));
                
                $tpl->tplParse($v, 'row'); 
            }
        }

        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/run_test');
        }

        $tpl->tplAssign($this->msg);                              
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsCustom($id, $code) {
        $row = parent::getViewListVarsRow($id, $code);
        
        if($code == 0) {
            $row['style'] = 'color: red;';
        }
        
        $row['style2'] = '';
        if($code == 1) {
            $row['style2'] = 'color: green;';
        }
        
        return $row;
    }
    
}
?>