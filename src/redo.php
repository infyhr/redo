<?php
namespace redo;

class redo {
    public $routes, $vars; // Holds routes and variables.
    public $request_method; // Holds the $_SERVER['REQUEST_METHOD'].
    public $unmatched; // Number of unmatched routes so far.

    public function __construct($routes = array(), $options = array()) {
        $this->routes         = $routes;
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        // Each one of the options element is actually a variable itself.
        if(!isset($options)) { throw new \Exception('Cannot continue without an options array.'); }
        foreach($options as $k => $v) { $this->vars[$k] = $v; } // Store the options array values into $this->options.

        // Rename xmlhttprequest to AJAX in order to simply things a little bit for us.
        if(strtolower($this->request_method) == 'xmlhttprequest') { $this->request_method = 'AJAX'; }

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

        return $this; // In order to be able to chain stuff later on.
    }

    public function register($route = array()) {
        foreach($route as $match => $entity) { // Iter through each router element and add it into the class var.
            $this->routes[$object] = $entity;
        }

        return $this; // chainable.
    }

    public function run() {
        $this->unmatched = 0; // Store unmatched routes. Before the foreach it's at 0.

        foreach($this->routes as $route => $entity) { // Foreach of the registered routes...
            $_GET['route'] = (!isset($_GET['route'] || $_GET['route'] == '') ? '/' : $_GET['route']); // If there is no route set then default to '/' which mimics home.

            if(preg_match('#^' . $route . '/?$#i', $_GET['route'], $res)) { // The regex.
                $args = explode('/', $_GET['route']); // Get all the arguments.
                unset($args[0]); // Remove the first argument, unneeded, it's the route name anyway.
                $args = array_keys($args); // Reset array keys since they get changed once unset is called.

                // Sometimes when an empty backslash is left at the end of the URL the last array value results to "" (empty string).
                // This is simply fixed by calling array_pop to pop the last element off the array in case it stands empty.
                if(empty(end($args))) array_pop($args);

                /* Check out the second parameter...
                if it's a string then it's a global function(), if it's an array it's in the form of 'class' => 'function name'
                and last but not least if it's actually callable (ergo, a function itself), it's a lambda function or an anonymous function. */

                switch($entity) {
                    default:
                        throw new \Exception('Ambiguous variable type.');
                    break;

                    case is_string($entity):
                        // It's a string, meaning it's just a function (not in a class)
                        if(is_callable($entity)) { @$entity($args); }
                    break;

                    case is_array($entity):
                        // The number of array elements must be 2.
                        if(count($entity) != 2) { throw new \Exception('The number of arguments needed to call a method for route ' . $route . ' is uneven to 2'); }

                        // Try to initiate the class.
                        $obj = new $entity[0]; // infy@A780LM-M: check whether this actually works with namespaces lol otherwise bye bye psr-4
                        if(!$obj) { throw new \Exception('Unable to initiate the object for the route ' . $route); }

                        // Does the function even exist in the class?
                        if(!method_exists($obj, $entity[1])) { /* infy@A780LM-M: TODO 404 here */ }

                        // It exists, ok, is it private (callable?)
                        if(!in_array($entity[1], get_class_methods($obj))) { /* infy@A780LM-M: TODO: 404 here */ }

                        // Seems all is well... just run it?
                        @$obj->$entity[1]($args);
                    break;

                    case is_callable($entity):
                        // Anonymous function!
                        @$entity($args); // pass arguments to use as so: $example = function()use ($args) { //code }
                    break;
                }
            }else { $this->unmatched++; } // Increment the unmatched by 1.
        }

        // If it itered through every possible case and did not match any then stop.
        if(count($this->routes) == $this->unmatched) { /* infy@A780LM-M: 404 HERE */ }
    }

    public function unregister($route) {
        // If it's there just unset it, otherwise silently fail.
        if(isset($this->routes[$route])) {
            unset($this->routes[$route]);
        }else { throw new \Exception('Unable to unregister "' . $route . '"'); }

        return $this; // chainable.
    }
}

?>