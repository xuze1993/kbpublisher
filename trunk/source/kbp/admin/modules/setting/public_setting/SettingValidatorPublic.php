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

require_once 'eleontev/SpellSuggest.php';


class SettingValidatorPublic
{

    static function validatePspell($setting) {

        $is_ext = extension_loaded('pspell');
        if(!$is_ext) {
            return array('code' => 0, 'code_message' => 'extension is not loaded');
        }

        $dictionary = pspell_new($setting['search_spell_pspell_dic']);
        if (!$dictionary) {
            return array('code' => 1, 'code_message' => "could not open the dictionary");
        }

        return true;
    }


    static function validateBing($url, $key) {

        $url = str_replace('[search_query]', 'test', $url);

        list($body, $code) = SpellSuggest_bing::request($url, $key);
        if($code != 200) {
            return false;
        }

        return true;
    }


    static function validateEnchant($setting) {

        // $is_ext = function_exists('enchant_broker_init');
        $is_ext = extension_loaded('enchant'); // 2017-02-06 eleontev
        if(!$is_ext) {
            return array('code' => 0, 'code_message' => 'extension is not loaded');
        }

        return true;
    }
}
?>