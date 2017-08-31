<?php
require_once '../config.inc.php';
require_once '../config_more.inc.php';

require_once 'CompareLang.php';
require_once 'eleontev/Dir/MyDir.php';

$files = array(
    'custom_field_msg.ini',
    'knowledgebase/setting_msg.ini',
    'public_setting/setting_msg.ini'
    );


// get all files
$skip_files = array();
$skip_dirs = array(
    'trouble', 'forum'
);

$c = new CompareLang('pl', 'en');
$files = $c->setFiles($skip_files, $skip_dirs);

// echo '<pre>', print_r($c, 1), '</pre>';
// exit;



$search = array();
$search[] = '#\[\w+\]#m';
$search[] = '#^\w+\s+=#m';
$search[] = '#["\']#';
$search[] = '#<^>>#';

$words_total_orig = 0;
$words_total_translate = 0;

// $langs = array(
//     'original' => $c->'original_lang',
//     'compared' => $c->'compared_lang'
// );

foreach($files as $f) {

    // read into string
    $file = $c->original_lang . '/' . $f;
    $str = file_get_contents($file);
    $str = preg_replace($search, '', $str);
    $str = strip_tags($str);

	//$words_orig_arr = str_word_count($str, 1);
	//echo '<pre>', print_r($words_orig_arr, 1), '</pre>';

    // count words
    $words_orig = str_word_count($str);
    $words_total_orig += $words_orig;
    // echo sprintf('%s - %s words(s)', $f, $words_orig), "<br />";
    // echo '<pre>', print_r("==========", 1), '</pre>';


	$words_translated = 0;
    $words_translate = $words_orig;

    // read into string
    $file = $c->compared_lang . '/' . $f;
    if(file_exists($file)) {
        $str = file_get_contents($file);
        $str = preg_replace($search, '', $str);
        $str = strip_tags($str);

		//$words_translated_arr = str_word_count($str, 1);
		//echo '<pre>', print_r($words_translated_arr, 1), '</pre>';

        // count words
        $words_translated = str_word_count($str);
        $words_translate = $words_orig - $words_translated;
        $words_total_translate += $words_translate;
        // echo sprintf('%s - %s words(s)', $f, $words), "<br />";
        // echo '<pre>', print_r("==========", 1), '</pre>';
    }

	echo sprintf('%s - %s words(s) original', $f, $words_orig), "<br />";
    echo sprintf('%s - %s words(s) translated', $f, $words_translated), "<br />";
    echo sprintf('==============='), "<br />";
}

echo "----------------------- <br />";
// echo "Total characters: {$chars_total} <br />";
// echo "Total files: " . count($files) . "<br />";
echo "Total original words: {$words_total_orig} <br />";
echo "Total words to translate: {$words_total_translate} <br />";


?>