<?php
require 'vendor/autoload.php';

$routes = array(
    '/'     => function() { echo 'root! :o -> ' . var_dump($args); },
    'test' => 'test_function',
    'test_reflector' => ['test_class', 'test_static'],
    // 'namespace' => '\testing\sub_testing'
    // 'namespace' => ['\testing\testing', 'sub_testing'],
);

function test_function() { echo 'test_function()!'; }
class test_class { public static function test_static() { echo 'test_static()!'; } }

try {
    $redo = new \redo\redo($routes);

    $redo->run();
}catch(\Exception $e) {
    var_dump($e);
}
?>