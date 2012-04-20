<?php
require_once './redo.php';
redo::initialize();


redo::route(array(
    '/' =>  'index'
));

function index() { echo 'Hello World.'; }

redo::run();