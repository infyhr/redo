<?php
require_once './redo.php';

$redo = new redo(array(
    '/'                      =>  'index',
    'user/([0-9]*)/profile'  =>  function() { echo 'works'; },
));

function index() {
    echo 'Hello, World!';
}