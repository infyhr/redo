<?php
error_reporting(-1);
require_once './redo.php';

try {
    $redo = new redo(array(
        '/'                      =>  'index',
        'user/([0-9]*)/profile'  =>  array('my_class', 'something'),
    ));
}catch(Exception $e) {
    echo $e->getMessage();
}

Class my_class {
    public function something($a, $b) {
        echo '$a => ' . $a . '<br>';
        echo '$b => ' . $b;
    }
}

function index() {
    echo 'Hello, World!';
}