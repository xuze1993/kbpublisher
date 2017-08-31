<?php

class SettingAction extends AppAction
{

    function getExtraItemsPopup($obj, $manager, $controller, $popup) {
        
        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(2, $popup);
            $items = explode('||', $data);
            
            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }
            
            $sorted_items = implode('||', $sorted_items);
            
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
        }
        
        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewExtraItems_popup');

        return $view;
    }
    
    
    function getPageTemplatePopup($obj, $manager, $controller) {

        $manager->module_id = 10; // hidden, popup setting
        $html_keys = array('page_to_load_tmpl', 'page_to_load_tmpl_mobile');

        if(isset($this->rp->submit)) {

            if(APP_DEMO_MODE) {
                $controller->go('not_allowed_demo', true);
            }

            $delim = '--delim--';

            $html_ids = array_values($manager->getSettingIdByKey($html_keys));
            foreach ($html_ids as $v) {
                if (!empty($this->rp->values[$v])) {
                    ksort($this->rp->values[$v]);
                    $this->rp->values[$v] = implode($delim, $this->rp->values[$v]);

                     // (int) to be parsed as skipped as we do strict in_array comparsion
                    $this->rp->setCurlyBracesValues((int) $v);
                    $this->rp->setHtmlValues((int) $v);
                }
            }

            $this->rp->stripVarsValues($this->rp->values, false);


            $setting_keys = $manager->getSettingKeys();
            $non_color_keys = array('page_to_load_tmpl', 'page_to_load_tmpl_mobile', 'left_menu_width');
            $color_pattern = '/^#[0-9A-Fa-f]+$/';

            $values = array();
            foreach($this->rp->values as $setting_id => $v) {
                if (!empty($setting_keys[$setting_id])) {

                    if (!in_array($setting_keys[$setting_id], $non_color_keys)) { // it's a color
                        if (!preg_match($color_pattern, $v)) {
                            $v = '';
                        }
                    }

                    if ($setting_keys[$setting_id] == 'left_menu_width') { // left block
                        $v = (int) $v;
                        if ($v < 230) {
                            $v = 230;
                        }
                    }
                }

                $values[$setting_id] = array($setting_id, $v);
            }

            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go('success', true);

        } else {

            $data = $manager->getSettings();
            foreach ($html_keys as $v) {
                $this->rp->setCurlyBracesValues($v);
                $this->rp->setHtmlValues($v);
            }

            $this->rp->stripVarsValues($data);
            $obj->set($data);
        }

        $view = $controller->getView($obj, $manager, 'SettingView_popup');
        return $view;
    }


    function getLdapDebugPopup($obj, $manager, $controller, $stored_settings_keys) {

        $manager->module_id = 160;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $stored_settings = $manager->getSettings(160);
        foreach ($stored_settings_keys as $key) {
            $values[$key] = $stored_settings[$key];
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewAuthDebug_popup');

        return $view;
    }


    function startSamlDebug($manager) {
        AuthProvider::loadSaml();
        
        // submitted from form
        $values = array();
        $setting_keys = $manager->getSettingKeys();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }
        
        // stored
        $stored_settings = $manager->getSettings(162);
        $stored_settings_keys = array(
            'saml_map_group_to_priv', 'saml_map_group_to_role',
            'saml_idp_certificate', 'saml_sp_certificate',
            'saml_sp_private_key'
        );
        
        foreach ($stored_settings_keys as $key) {
            $values[$key] = $stored_settings[$key];
        }

        try {
            
            // initiating a request
            $ol_auth = AuthSaml::getOneLogin($values, $values['saml_sso_binding']);
            
            $necessary_keys = array(
                'saml_issuer', 'saml_sso_endpoint',
                'saml_sso_binding', 'saml_slo_endpoint',
                'saml_slo_binding', 'saml_map_fname',
                'saml_map_lname', 'saml_map_email', 
                'saml_map_username', 'saml_map_remote_id', 
                'saml_algorithm'
            );
            
            // for later use
            foreach ($necessary_keys as $k) {
                $_SESSION['saml_settings_'][$k] = $values[$k];
            }
            
            $ol_auth->login('debug');
            
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        exit;
    }
    
    
    function startSamlLogoutDebug() {
        AuthProvider::loadSaml();
        
        if (empty($_SESSION['saml_settings_'])) {
            exit;
        }

        try {
            // initiating a request
            $ol_auth = AuthSaml::getOneLogin($_SESSION['saml_settings_'], $_SESSION['saml_settings_']['saml_slo_binding']);
            $ol_auth->logout('debug', array(), $_SESSION['saml_settings_']['name_id']);

        } catch (Exception $e) {
            echo $e->getMessage();
        }

        exit;
    }


    function getSamlCertPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            
            $values = array();
            $values[$setting_id] = array($setting_id, $this->rp->values[$setting_id]);
            $manager->saveQuery($values);
        
            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go();
        }

        $data = $manager->getSettings(162, $popup);
        $this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewSamlCert_popup');

        return $view;
    }


    function getSamlMetadataPopup($obj, $manager, $controller) {

        $manager->module_id = 162;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewSamlMetadata_popup');

        return $view;
    }


    function getLdapGroupPopup($obj, $manager, $controller, $popup) {

        $manager->module_id = 160;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = ($popup == 'remote_auth_map_group_to_priv') ? 'SettingViewAuthMapPriv_popup' : 'SettingViewAuthMapRole_popup';
        $view = $controller->getView($obj, $manager, $view);

        return $view;
    }


    function getSamlGroupPopup($obj, $manager, $controller, $popup) {

        $manager->module_id = 162;

        $setting_keys = $manager->getSettingKeys();
        
        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(162, $popup);
            $items = explode("\n", $data);
            
            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }
            
            $sorted_items = implode("\n", $sorted_items);
            
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
        }

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewSamlMap_popup');

        return $view;
    }


    function getHeaderPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit) && !empty($_FILES)) {

            $is_error = false;

            if (empty($_FILES['logo_1']['name'])) {
                $is_error = true;

                $msgs = AppMsg::getMsgs('error_msg.ini', false, 'nothing_to_upload', 1);
                $obj->errors['formatted'][]['msg'] = BoxMsg::factory('error', $msgs);

            } else {
                $upload = $manager->upload();
                if(!empty($upload['error_msg'])) {
                    $is_error = true;
                    $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
                }
            }

            if (!$is_error) {
                require_once 'eleontev/Util/FileUtil.php';
                require_once 'eleontev/Dir/mime_content_type.php';

                $image_data = FileUtil::read($upload['filename']);
                $mime_type = mime_content_type($upload['filename']);
                $encoded_image = base64_encode($image_data);

                $value = sprintf('data:%s;base64,%s', $mime_type, $encoded_image);

                $values = array();
                $header_logo_id = $manager->getSettingIdByKey($popup);

                $values[$header_logo_id] = array($header_logo_id, $value);
                $manager->saveQuery($values);

                $_GET['saved'] = 1;
                $controller->setMoreParams('saved');
                $controller->setMoreParams('popup');
                $controller->go();
            }

        } elseif(isset($this->rp->submit_delete)) {
            $values = array();
            $header_logo_id = $manager->getSettingIdByKey($popup);
            $values[$header_logo_id] = array($header_logo_id, '');
            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go();
        }

        $data = $manager->getSettings(2, $popup);
        $this->rp->stripVarsValues($data);
        $obj->set(array('header_logo' => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewHeaderLogo_popup');

        return $view;
    }


    function getSpellSuggestPopup($obj, $manager, $controller) {

        require_once 'eleontev/SpellSuggest.php';

        $source = '';
        if(!empty($this->rq->source)) {
            $source = $this->rq->source;
            if(!in_array($source, SpellSuggest::$sources)) {
                echo 'Wrong Spell Suggest Source!';
                exit;
            }
        }

        if(isset($this->rp->submit)) {

            $spell_check = 0;
            if (isset($this->rp->primary)) {
                $spell_check = $source;
            }


            if ($source == 'bing') {
                require_once APP_MODULE_DIR . 'setting/public_setting/SettingValidatorPublic.php';

                $val = array(
                    'search_spell_bing_spell_check_key' => $this->rp->bing_spell_check_key,
                    'search_spell_bing_spell_check_url' => $this->rp->bing_spell_check_url,
                    'search_spell_bing_autosuggest_key' => $this->rp->bing_autosuggest_key,
                    'search_spell_bing_autosuggest_url' => $this->rp->bing_autosuggest_url
                );

                if ($spell_check) {
                    $ret = SettingValidatorPublic::validateBing($this->rp->bing_spell_check_url, $this->rp->bing_spell_check_key);
                    $ret2 = SettingValidatorPublic::validateBing($this->rp->bing_autosuggest_url, $this->rp->bing_autosuggest_key);
                    if (!$ret || !$ret2) {
                        $spell_check = 0;
                        $more = array('popup' => 'search_spell_suggest', 'bad_url' => 1);
                        $controller->goPage('this', 'this', 'this', false, $more);
                    }
                }

            } elseif ($source == 'pspell') {

                $val = array(
                    'search_spell_pspell_dic' => $this->rp->dictionary
                );

            } else {

                $val = array(
                    'search_spell_enchant_provider' => $this->rp->provider,
                    'search_spell_enchant_dic' => $this->rp->dictionary
                );
            }

            $val['search_spell_custom'] = $this->rp->custom_words;
            $val['search_spell_suggest'] = $spell_check;

            $values = array();
            $keys = $manager->getSettingIdByKey(array_keys($val));
            foreach($keys as $k => $id) {
                $values[$id] = array($id, $val[$k]);
            }

            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go();
        }


        $manager->module_id = 2;
        $data = $manager->getSettings();
        $this->rp->stripVarsValues($data);
        $obj->set($data);

        $view_name = (empty($source)) ? 'SettingViewSpellCheck_list_popup' : 'SettingViewSpellCheck_popup';
        $view = $controller->getView($obj, $manager, $view_name);

        return $view;
    }


    function getExportPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit) || isset($this->rp->submit_disable)) {

            $is_error = false;
            $on = (isset($this->rp->submit));

            if (!$is_error) {
                $this->rp->setCurlyBracesValues('body');
                $this->rp->setHtmlValues('body'); 
                
                $val = array(
                    $popup => ($on) ? 1 : '',
                    $popup . '_tmpl' => $this->rp->body
                );
                
                if ($popup == 'plugin_export_header') {
                    $val['plugin_wkhtmltopdf_margin_top'] = $this->rp->margin;
                }
                
                if ($popup == 'plugin_export_footer') {
                    $val['plugin_wkhtmltopdf_margin_bottom'] = $this->rp->margin;
                }

                $values = array();
                $keys = $manager->getSettingIdByKey(array_keys($val));
                foreach($keys as $k => $id) {
                    $values[$id] = array($id, $val[$k]);
                }

                $manager->saveQuery($values);

                if ($on) {
                    $_GET['saved'] = 1;
                    $controller->setMoreParams('saved');

                } else {
                    $_GET['disabled'] = 1;
                    $controller->setMoreParams('disabled');
                }

                $controller->setMoreParams('popup');
                $controller->go();
            }

        }

        $data = $manager->getSettings(140);

        $this->rp->setCurlyBracesValues($popup . '_tmpl');
        $this->rp->setHtmlValues($popup . '_tmpl');

        $this->rp->stripVarsValues($data);
        $obj->set($data);

        $view = $controller->getView($obj, $manager, 'SettingViewPluginExport_popup');

        return $view;
    }


    function getExportTestPopup($obj, $manager, $controller) {

        $manager->module_id = 140;

        $setting_keys = $manager->getSettingKeys();
        
        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (isset($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }
        
        $options = array(
            'check_setting' => true,
            'config' => array(
                'tool_path' => $values['plugin_wkhtmltopdf_path']
            )
        );

        $keys = array('cover', 'header', 'footer');
        $stored_settings = $manager->getSettings(140);
        foreach ($keys as $key) {
            if (isset($this->rp->vars['plugin_export_' . $key])) {
                $values['plugin_export_' . $key] = 1;
                $values['plugin_export_' . $key . '_tmpl'] = $this->rp->vars['plugin_export_' . $key];

            } else {
                $values['plugin_export_' . $key] = $stored_settings['plugin_export_' . $key];
                $values['plugin_export_' . $key . '_tmpl'] = $stored_settings['plugin_export_' . $key . '_tmpl'];
            }
        }
        
        
        $keys2 = array('top', 'bottom');
        foreach ($keys2 as $key) {
            if (isset($this->rp->vars['plugin_wkhtmltopdf_margin_' . $key])) {
                $values['plugin_wkhtmltopdf_margin_' . $key] = $this->rp->vars['plugin_wkhtmltopdf_margin_' . $key];

            } else {
                $values['plugin_wkhtmltopdf_margin_' . $key] = $stored_settings['plugin_wkhtmltopdf_margin_' . $key];
            }
        }
        
        $obj->set($values);
        
        foreach ($keys as $key) {
            $has_param = $obj->get('plugin_export_' . $key);
            if ($has_param) {
                $options['config']['settings'][$key] = $obj->get(sprintf('plugin_export_%s_tmpl', $key));
            }
        }
    
        $view = $controller->getView($obj, $manager, 'SettingViewPluginExportTest_popup', $options);

        return $view;
    }


    function getSharePopup($obj, $manager, $controller) {

        $manager->module_id = 2;

        $data = $manager->getSettings(2, 'item_share_link');
        $this->rp->stripVarsValues($data);
        $obj->set(array('item_share_link' => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewShareLink_popup');

        return $view;
    }

}

?>