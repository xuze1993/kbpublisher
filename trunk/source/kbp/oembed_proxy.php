<?php

// require 'admin/lib/Embera/Autoload.php';
//
// $callback = $_GET['callback'];
// $url = $_GET['url'];
//
// if (empty($callback) || empty($url)) {
//     exit;
// }
//
// $embera = new \Embera\Embera();
// $data = $embera->getUrlInfo($url);
//
// if ($embera->hasErrors()) {
//     echo $embera->getLastError();
//
// } elseif (!empty($data)) { // jsonp
//     $json = json_encode($data[$url]);
//
//     $js_str = '%s && %s(%s);';
//     echo sprintf($js_str, $callback, $callback, $json);
// }

?>