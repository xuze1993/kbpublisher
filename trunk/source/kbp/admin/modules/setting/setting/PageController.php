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

$controller->loadClass('Setting');
$controller->loadClass('SettingAction');
$controller->loadClass('SettingModel');
$controller->loadClass('SettingModelUser');

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Setting;

$action = new SettingAction($rq, $rp);

if($controller->page == 'account_setting') {
    $manager = new SettingModelUser(AuthPriv::getUserId());
} else {
    $manager = new SettingModel();
    $_action = (isset($rp->set_default) || isset($rp->submit)) ? 'update' : $controller->action;
    $manager->checkPriv($priv, $_action, @$rq->id);    
}


$setting_ar = array(
    'kbc_setting'    => 'public_setting',
    'kba_setting'    => 'knowledgebase',
    'kbf_setting'    => 'file',
    'kbt_setting'    => 'trouble',
    'kbforum_setting'=> 'forum',
    'export_setting' => 'export',
    'sphinx_setting' => 'sphinx',
    'ldap_setting'   => 'ldap_setting',
    'saml_setting'   => 'saml_setting',
    'rauth_setting'  => 'rauth_setting'
);
                    
$s = (isset($setting_ar[$controller->sub_page])) ? $setting_ar[$controller->sub_page] : $controller->page;
if($s == 'admin_setting') {
    $manager->module_id = 1;
    $manager->module_name = $s;

} elseif($s == 'public_setting') {
    $manager->module_id = 2;
    $manager->module_name = $s;
    
} elseif($s == 'export') {
    $manager->module_id = 140;
    $manager->module_name = 'export_setting';
    
} elseif($s == 'sphinx') {
    $manager->module_id = 141;
    $manager->module_name = 'sphinx_setting';
    
} else {
    $manager->setModuleId($s);
}

$manager->loadParser();

// form for template
if($s == 'plugin_setting') {
    // $manager->separate_form = true;
}

$stored_settings_keys = array(
    'ldap' => array(
        'remote_auth_map_group_to_priv',
        'remote_auth_map_group_to_role',
        'remote_auth_map_priv_id',
        'remote_auth_map_role_id'
    )
);


$popup = $controller->getMoreParam('popup');

switch ($popup) {
    
case 'menu_extra':
case 'nav_extra':
    $view = $action->getExtraItemsPopup($obj, $manager, $controller, $popup);
    break;
    
case 'page_to_load':
case 'page_to_load_mobile':    
    
    $view = $action->getPageTemplatePopup($obj, $manager, $controller);
    break;
    
case 'ldap_debug':
    
    $view = $action->getLdapDebugPopup($obj, $manager, $controller, $stored_settings_keys['ldap']);
    break;

case 'saml_debug':
    
    $view = $action->startSamlDebug($manager);
    break;
    
case 'saml_debug_slo':
    
    $view = $action->startSamlLogoutDebug();
    break;
    
case 'saml_metadata':
    $view = $action->getSamlMetadataPopup($obj, $manager, $controller);
    break;

case 'remote_auth_map_group_to_priv':
case 'remote_auth_map_group_to_role':
	
    $view = $action->getLdapGroupPopup($obj, $manager, $controller, $popup);
    break;
    
case 'saml_map_group_to_priv':
case 'saml_map_group_to_role':
	
    $view = $action->getSamlGroupPopup($obj, $manager, $controller, $popup);
    break;

case 'saml_idp_certificate':
case 'saml_sp_certificate':
case 'saml_sp_private_key':
    $view = $action->getSamlCertPopup($obj, $manager, $controller, $popup);
    break;
    
case 'header_logo':
case 'header_logo_mobile':
    
    $view = $action->getHeaderPopup($obj, $manager, $controller, $popup);
    break;
    
case 'search_spell_suggest':
    
    $view = $action->getSpellSuggestPopup($obj, $manager, $controller);
    break;
    
case 'plugin_export_cover':
case 'plugin_export_header':
case 'plugin_export_footer':
    
    $view = $action->getExportPopup($obj, $manager, $controller, $popup);
    break;
    
case 'plugin_export_test':
    
    $view = $action->getExportTestPopup($obj, $manager, $controller);
    break;
    
case 'item_share_link':
    
    $view = $action->getSharePopup($obj, $manager, $controller);
    break;
    
case 'plugin_sphinx_index':
	
	$view = $action->getSphinxIndexPopup($obj, $manager, $controller);
    break;
    
    
default:
    
        if(isset($rp->submit) || isset($rp->submit1)) {
    
        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true);
        }
        

		$values = $obj->prepareValues($rp->values, $manager);
        $is_error = $obj->validate($values, $manager);
    
        if($is_error) {
            
            if ($manager->module_id == 160) {
                $stored_settings = $manager->getSettings(160);
                foreach ($stored_settings_keys['ldap'] as $key) {
                    $values[$key] = $stored_settings[$key];
                }    
            }
            
            $rp->stripVarsValues($values, true);
            $obj->set($values);
        
        } else {
            
            $old_values = &$manager->getSettings();
            $rp->stripVarsValues($old_values);
            
            $rp->stripVarsValues($values, false);
            $manager->save($values);
            
            $parser = $manager->getParser();
            $parser->manager->callOnSave($values, $old_values);
        
            $controller->go();
        }

    
    } elseif(isset($rp->set_default)) {
        
        $old_values = &$manager->getSettings();
        $rp->stripVarsValues($old_values);
        
        if(APP_DEMO_MODE) { 
            $controller->go('not_allowed_demo', true); 
        }    
        
        $manager->setDefaultValues();
        
        $values = &$manager->getSettings();
        $rp->stripVarsValues($values);
        
        $parser = $manager->getParser();
        $parser->manager->callOnSave($values, $old_values);
            
        $controller->go();

    } else {

        $data = $manager->getSettings();
        $rp->stripVarsValues($data);
        $obj->set($data);
    }


    $view = $controller->getView($obj, $manager, 'SettingView_form');
    
}
?>