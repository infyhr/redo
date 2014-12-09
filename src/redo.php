<?php
namespace redo;

class redo {
    public $routes, $vars; // Holds routes and variables.
    public $request_method; // Holds the $_SERVER['REQUEST_METHOD'].
    public $unmatched; // Number of unmatched routes so far.
    public $path; // Holds the complete absolute path. Used for view loading.

    public function __construct($routes = array(), $options = array()) {
        $this->routes         = $routes;
        $this->request_method = $_SERVER['REQUEST_METHOD'];
        $this->path           = realpath(dirname(__FILE__));

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
        $this->unmatched = 0; // Store the number of unmatched routes. Before the foreach it's at 0.

        if(empty($this->routes)) { $this->throw_http(404); return; } // No routes added, call 404 and return.
        foreach($this->routes as $route => $entity) { // Foreach of the registered routes...
            $_GET['route'] = ($_GET['route'] == FALSE || $_GET['route'] == '') ? '/' : $_GET['route']; // If there is no route set then default to '/' which mimics home.

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
                        if(is_callable($entity)) { @$entity($args);return; }
                        throw new \Exception('Unable to call function "' . $entity . '" within the global namespace.');
                    break;

                    case is_array($entity):
                        // The number of array elements must be 2.
                        if(count($entity) != 2) { throw new \Exception('The number of arguments needed to call a method for route ' . $route . ' is uneven to 2'); }

                        // Now check whether if it's static or not.
                        // infy@A780LM-M: Maybe add the same for final methods?
                        try {
                            $reflector = new \ReflectionMethod($entity[0], $entity[1]); // infy@A780LM-M: works with static.
                        }catch(\ReflectionException $e) { throw new \Exception($e); } // rethrow? This is probably bad practice...
                        if($reflector->isStatic()) $is_static = true;

                        // It exists, ok, is it private (callable?). If so, throw a 404.
                        if(!in_array($entity[1], get_class_methods($entity[0]))) { $this->throw_http(404); }

                        // All is well, then.
                        if(!$is_static) {
                            $obj = new $entity[0];
                            @$obj->$entity[1]($args);
                        }else {
                            @$entity[0]::$entity[1]($args);
                        }
                    break;

                    case is_callable($entity):
                        // Anonymous function!
                        @$entity($args); // pass arguments to use as so: $example = function()use ($args) { //code }
                    break;
                }
            }else { $this->unmatched++; } // Increment the unmatched by 1.
        }

        // If it itered through every possible case and did not match any then stop and throw a 404.
        if(count($this->routes) == $this->unmatched) { $this->throw_http(404); }
    }

    public function unregister($route) {
        // If it's there just unset it, otherwise silently fail.
        if(isset($this->routes[$route])) {
            unset($this->routes[$route]);
        }else { throw new \Exception('Unable to unregister "' . $route . '"'); }

        return $this; // chainable.
    }

    public function throw_http($code) {
        if(!in_array($code, array_keys($this->http_codes))) return false; // if it's not in the array then return false
        // Now all we need to do is just set the header
        header(sprintf('HTTP/1.1 %d %s', $code, $this->http_codes[$code]));
        $http_err_file = $this->path . '/views/http/' . $code . '.php';
        if(file_exists($http_err_file) && is_readable($http_err_file)) { // Try to load the http error template.
            // Load!
            echo $this->render($http_err_file);
        }else {
            // Throw an exception about the error itself and then let the user handle the way they want to handle exceptions.
            throw new \Exception(sprintf('HTTP/1.1 %d %s', $code, $this->http_codes[$code]));
        }
    }

    // You shouldn't probably use this but like blade standalone instance for rendering proper templates.
    // Generally, this should be only used for throw_http.
    public function render($file, $vars = array()) {
        if(!file_exists($file) || !is_readable($file)) { throw new \Exception('Unable to read the template file ("' . $file . '")'); }

        // Start the buffering.
        ob_start();
            if(!empty($vars)) extract($vars); // $vars must be a 2d array.
            require_once $file; // Require the file.
            $output = ob_get_contents(); // Grab the output
        ob_end_clean();

        return $output; // Should, of course, be echoed.
    }
}

?>