<?php

class redo {
    public $options, $vars;
    public $unmatched, $routes;
    public $http_codes = array();

    public function __construct($routes = array(), $options = array()) {
        $this->routes  = $routes;
        $this->options = $options;

        if(!$routes || !is_array($this->routes)) { throw new Exception('Invalid array of routes supplied.'); }

        // Merge $options into $vars which can be later on used everywhere within redo
        if(isset($this->options)) {
            foreach($this->options as $k => $v) {
                $this->vars[$k] = $v;
            }
        }

        // Define some HTTP Status Codes.
        $this->http_codes = [
            200 =>  'OK',
            201 =>  'Created',
            202 =>  'Accepted',
            203 =>  'Non-Authoritative Information',
            204 =>  'No Content',
            205 =>  'Reset Content',
            206 =>  'Partial Content',

            301 =>  'Moved Permanently',
            302 =>  'Found',
            303 =>  'See Other',
            304 =>  'Not Modified',
            305 =>  'Use Proxy',
            306 =>  'Payment Required',
            307 =>  'Temporary Redirect',

            400 =>  'Bad Request',
            401 =>  'Unauthorized',
            402 =>  'Payment Required',
            403 =>  'Forbidden',
            404 =>  'Not Found',
            405 =>  'Method Not Allowed',
            406 =>  'Not Acceptable',
            407 =>  'Proxy Authentication Required',
            408 =>  'Request Timeout',
            409 =>  'Conflict',
            410 =>  'Gone',
            411 =>  'Length Required',
            412 =>  'Precondition Failed',
            413 =>  'Requested Entity Too Large',
            414 =>  'Requested URI Too Long',
            415 =>  'Unsupported Media Type',
            416 =>  'Request Not Satisfiable',
            417 =>  'Expectation Failed',
            418 =>  'I\'m a teapot',
        ];

        $this->router(); // Call the router!
    }

    private function router() {
        // For each of the routes...
        $this->unmatched = 0;
        foreach($this->routes as $k => $v) {
            if(!isset($_GET['route']) || $_GET['route'] == '') { $_GET['route'] = '/'; }

            if(preg_match('#^' . $k . '/?$#i', $_GET['route'], $res)) { // The regex.
                $args = explode('/', $res[0]); // Split each argument by a forward slash, it's how they're passed.
                unset($args[0]); // We don't need the first one, it's just the function name.
                $args = array_values($args); // Reset the keys since they are going to be changed when you call unset.

                // Check out the second parameter.
                switch($v) {
                    case is_array($v): // It's an array which means it calls an object -> ('class', 'function', 'params');.
                        if(!isset($v[1])) { throw new Exception('Expected a function next to the class "' . $v[0] . '".'); }
                        $class = new $v[0]; // Try to init the class.

                        // Unable to init the class.
                        if(!$class) { throw new Exception('Unable to init the class "' . $class . '"');  }

                        // If the method does not exists, we should probably throw a 404 rather than an Exception.
                        if(!method_exists($class, $v[1])) { $this->http(404); }

                        // Same goes here, only for a private function.
                        if(!in_array($v[1], get_class_methods($class))) { $this->http(404); }

                        // Check if it accepts any arguments and then call the corresponding function.
                        if(isset($v[2])) { // It does.
                            $class->$v[1]($v[2]);
                        }else {
                            $class->$v[1]; // It doesn't.
                        }
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

        if($this->unmatched == count($this->routes)) { $this->http(404); }
    }

    private function http($code) {
        echo $code;
        // Set the header:
        if(!array_key_exists($code, $this->http_codes)) { return false; }
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $this->http_codes[$code], true, $code);
        // Try to load the appropriate view
        // $location = './views/' . $code . '.php'
        $location = (isset($this->vars['view.dir'])) ? $this->vars['view.dir'] . $code . '.php' : './views/' . $code . '.php';
        if(file_exists($location) && is_readable($locaton)) {
            require_once $locaton;
        }else {
            echo 'does not exists finish this later.';
        }
    }

}

?>
