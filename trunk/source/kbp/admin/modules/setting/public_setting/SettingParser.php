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

class SettingParser extends SettingParserCommon
{

    function parseIn($key, $value, &$values = array()) {
        $page_to_load_keys = array('page_to_load', 'page_to_load_mobile');

        if($key == 'contact_attachment_ext') {
            $value = str_replace(' ', '', $value);
            $value = str_replace(array(';','.'), ',', $value);

        } elseif(in_array($key, $page_to_load_keys) && empty($value)) {
            $value = 'Default';
        
        // disabled download section, do not search in it
        } elseif($key == 'search_default' && $value == 'file') {
            if(empty($values['module_file'])) {
                $value = 'all';
            }
        }
        
        
        // header = 1 in intranet view 
        if($values['view_format'] == 'fixed' && empty($values['view_header'])) {
            $values['view_header'] = 1;
        }
        
        return $value;
    }


    function parseOut($key, $value) {
        return $value;
    }


    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        if($key == 'view_format') {
            $options = array('option_1', 'option_2');
            $values = array_diff_key($values, $options);

        } elseif($key == 'register_user_priv') {
            $options = $this->manager->getPrivSelectRange();
            $options[0] = $values['option_1'];
            unset($values['option_1']);
            unset($options[1]); // unset admin
            ksort($options);
            $values = &$options;

        } elseif($key == 'register_user_role') {
            $options = $this->manager->getRoleSelectRange();
            $i = 1;
            foreach($options as $k => $v) {
                if($i == 1) {
                    $values[0] = $values['option_1'];
                    unset($values['option_1']);
                }

                $values[$k] = $v;
                unset($options[$k]);
                $i++;
            }

        // remove "allowed for all" for subscription
        // to enable comment it and add value to the table 1,2,3,4
        } elseif($key == 'allow_subscribe_news'
              || $key == 'allow_subscribe_entry') {
            unset($values['option_2']);

        // remove "allowed for all" and "with priv only" for comment subscription
        } elseif($key == 'allow_subscribe_comment') {
            unset($values['option_2']);
            unset($values['option_4']);

        } elseif($key == 'subscribe_news_time' || $key == 'subscribe_entry_time') {

            $reg =& Registry::instance();
            $conf = $reg->getEntry('conf');
            $time_format = $conf['lang']['time_format'];

            foreach(range(0, 23) as $v) {
                $ts = mktime($v, 0, 0, 1, 1, 2014);
                $hour = sprintf('%02d', $v);
                $hours[$hour] = strftime($time_format, $ts);
            }
            $values = $hours;
        }

        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }


    // when display
    function skipSettingDisplay($key, $value = false) {
        $ret = false;

        $keys = array(
            'search_spell_pspell_dic',
            'search_spell_custom',
            'search_spell_bing_spell_check_key',
            'search_spell_bing_spell_check_url',
            'search_spell_bing_autosuggest_key',
            'search_spell_bing_autosuggest_url',
            'search_spell_enchant_provider',
            'search_spell_enchant_dic',
            'page_design_index'
        );

        if(in_array($key, $keys)) {
            $ret = true;
        
        } elseif($key == 'module_forum' && !BaseModel::isModule('forum')) {
            $ret = true;
        }

        return $ret;
    }


    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(
            8=>'2.1', 9=>'4.1', 10=>'4.6', 11=>'2.3', 12=>'4.8',
            14=>'4.4', 15=>'5.0', 16=>'4.6', 17=>'2.2', 18=>'4.5',19=>'4.7');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }


    // any special rule to parse form field
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {

        $setting_keys = array('view_template');
        if(in_array($setting_key, $setting_keys)) {

            if($val == 'check') {
                return true;
            }

            return SettingParseForm::$setting_key($this, $val, $field, $setting_msg);
        }

        return false;
    }

}



class SettingParseForm
{

    static function view_template($obj, $values, $field, $setting_msg) {

        $format_values['1'] = 'default';
        $format_values['2'] = 'left';
        $format_values['3'] = 'fixed';

        $options_range = array('template', 'menu_type');


        $options_values['template']['1.1'] = 'default';
        // $options_values['template']['1.2'] = 'blocks';

        $options_values['template']['2.1'] = 'default'; // blocks
        // $options_values['template']['2.2'] = 'full';
        // $options_values['template']['2.3'] = 'default_left';

        $options_values['template']['3.1'] = 'default';

        $options_values['menu_type']['2.1'] = 'tree';
        $options_values['menu_type']['2.2'] = 'top_tree';

        $options_values['menu_type']['2.3'] = 'tree_55';
        $options_values['menu_type']['2.4'] = 'top_tree_55';
        $options_values['menu_type']['2.5'] = 'followon';

        // prev values for left view 2016-10-12 eleontev
        // $options_values['menu_type']['2.1'] = 'tree';
        // $options_values['menu_type']['2.2'] = 'followon';
        // $options_values['menu_type']['2.3'] = 'top_tree';

        $options_values['menu_type']['3.1'] = 'tree';
        $options_values['menu_type']['3.2'] = 'top_tree';


        foreach ($options_range as $option) {
            $json = array();

            $options_text = $setting_msg['view_' . $option];
            unset($options_text['title'], $options_text['descr']);
			
            $selected = $obj->manager->getSettings(2, 'view_' . $option);

            foreach($format_values as $format_key => $format_value) {
                $json_body = array();

                foreach($options_values[$option] as $k => $v) {
                    if($k[0] == $format_key) {

                        $val  = $options_values[$option][$k];
                        $text = $options_text['option_' . $k];

                        $s    = ($v == $selected) ? 'true' : 'false';
                        $json_body[] = sprintf('{"val": "%s", "text": "%s", "s": %s}', $val, $text, $s);
                    }
                }

                $json[] = sprintf("\"%s\": [\n%s\n]", $format_value, implode(",\n", $json_body));
            }

            $json2[] = sprintf("\"%s\": {\n%s\n}", $option, implode(",\n", $json));
        }

        $json = implode(",\n", $json2);

        // echo "<pre>"; print_r($json); echo "</pre>";;
        //echo "<pre>"; print_r($setting_msg); echo "</pre>";

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'setting/setting/template/form_view_template.js');

        $tpl->tplAssign('myOptionsJson', $json );
        $tpl->tplAssign('field', $field);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>