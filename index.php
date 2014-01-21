<?php
error_reporting(-1);
require_once './redo.php';

/*try {
    $redo = new redo\redo(array(
        '/'                      =>  'index',
        'testing/(.*)'           =>  function($r) { var_dump($r); },
        'user'                   =>  array('my_class', 'something'),
    ));
    $redo->route();
}catch(Exception $e) {
    // Oh no, something has terribly gone wrong!
    // You should probably throw a 500 here.
    echo $e->getMessage();
}*/


try {
    $redo = new redo\redo();
    #$redo->get('/' => 'func');
    #$redo->post('/' => 'func');
    $redo->route();
}catch(Exception $e) {
    // Oh no, something has terribly gone wrong!
    // You should probably throw a 500 here.
    echo $e->getMessage();
}



Class my_class {
    public function something($vars) {
        var_dump($vars);
    }
}

function index() {
    echo 'Hello, World!';
}
