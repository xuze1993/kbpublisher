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
 
require_once APP_MODULE_DIR . 'setting/setting/inc/SettingModel.php'; 

 
class SettingParserModelCommon extends AppModel
{
    var $smodel;
	
	
	function getSettingModelObj() {
		if(empty($this->smodel)) {
			$this->smodel = new SettingModel();
		}
	
		return $this->smodel;
	}
	
	
    function getSettings($module_id, $setting_key = false, $ignore_parser = true) {
		$m = $this->getSettingModelObj();
		return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }

    
    function setSettings($data) {
		$m = $this->getSettingModelObj();
        return $m->setSettings($data);
    }
	
	
	function getSettingIdByKey($key) {
		$m = $this->getSettingModelObj();
        return $m->getSettingIdByKey($key);
	}
    
    
    function resetAuthSetting($values) {
        
        $auth_setting = array(
            'remote_auth' => 232,
            'remote_auth_script' => 233,
            'saml_auth' => 347
        );
        
        $data = array();
        foreach($auth_setting as $k => $id) {
            $data[$id] = 0;
            if(!empty($values[$k])) {
                $reset = true;
                unset($data[$id]);
            }
        }
        
        if(isset($reset)) {
            $this->setSettings($data);
        }
    }
    
    
    // if we need to call someting on settings save
    function callOnSave($values, $old_values) {
        return true;
    }
}
?>