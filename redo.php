<?php

class redo {
    public $unmatched, $routes;

    public function __construct($routes) {
        $this->routes = $routes;

        if(!$routes || !is_array($this->routes)) { throw new Exception('Invalid routes array supplied.'); }

        // For each of the routes...
        $this->unmatched = 0;
        foreach($this->routes as $k => $v) {
            if(!isset($_GET['route']) || $_GET['route'] == '') { $_GET['route'] = '/'; }

            if(preg_match('#^' . $k . '/?$#i', $_GET['route'], $res)) {
                $args = explode('/', $res[0]);
                unset($args[0]);
                $args = array_values($args);

                // Check out the second parameter
                switch($v) {
                    case is_array($v):
                        echo 'array';
                        break;
                    case is_callable($v):
                        echo 'callable';
                        break;
                    case is_string($v):
                        echo 'string';
                        break;
                    default:
                        echo '???';
                        break;
                }
                // if(is_callable($v)) {
                //     $v();
                // }
                // var_dump($res);
                // var_dump($k);
            }else { $this->unmatched++; }
        }

        if($this->unmatched == count($routes)) { echo '404 here...'; }
    }
}

?>