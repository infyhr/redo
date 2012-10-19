<?php

/**
 * @package redo
 * @license MIT
 * 
 * This is the framework itself. Duh.
 * */
Class redo {
    
    static $vars, $request_method, $routes, $http_codes;
    
    /**
     * redo::initialize()
     * 
     * Initializes the framework itself. Options can be set and are treated as values.
     * This should be called at the beginning of the `project`.
     * 
     * @param $options array The arguments you want to pass.
     * */
    public static function initialize($options = array()) {
        #if(empty($options)) { return false; } // Can't continue.
        
        // Adds up all the options to vars.
        if(!empty($options)) {
            foreach($options as $k => $v) {
                self::$vars[$k] = $v;
            }
        }
        
        // Sets debug.
        $debug_level = self::get('debug');
        if(!isset($debug_level)) { self::set(array('debug' => 0)); }
        
        // This is pretty obvious...
        if(self::get('debug') == 1) {
            error_reporting(E_ALL);
        }else {
            error_reporting(0);
        }
        
        $view_path = self::get('view.path');
        if(!isset($view_path)) { self::set(array('view.path' => './views/')); }
        
        // This sets the request method so you can manipulate POSTs, GETs etc.
        self::$request_method = $_SERVER['REQUEST_METHOD'];
        if(self::$request_method == 'xmlhttprequest') { self::$request_method = 'AJAX'; }
        
        // Define some HTTP Status Codes.
        self::$http_codes = [
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
    }
    
    /**
     * redo::run()
     * 
     * This handles routing. This should be called at the end of the `project`.
     * */
    public static function run() {
        $no_regex = array();
        if(empty(self::$routes)) { self::respond(404); }
        
        foreach(self::$routes as $url => $func) {
            // If the route isn't set -> index is requested.
            if(!isset($_GET['route']) || $_GET['route'] === '') { $_GET['route'] = '/'; }
            
            if(preg_match('#^' . $url . '/?$#i', $_GET['route'], $res)) {
                // We are on to something
                $pass = explode('/', $res[0]); // Split each arg by /
                unset($pass[0]); // Remove the first one, the first one is the function name -> useless.
                $pass = array_values($pass); // This just resets the keys since we removed the first one.
                
                // The second parameter, the function is in a class!
                if(is_array($func)) {
                    $class_called = new $func[0];
                    if(!$class_called) { self::respond(500); } // An error occured, we cannot load the class.

                    // This is quite obvious, 404 if the class does not exist.
                    if(!method_exists($class_called, $func[1])) { self::respond(404); }
                    
                    // Is it perhaps a private function?
                    if(!in_array($func[1], get_class_methods($class_called))) { self::respond(404); }
                    
                    // Everything is okay - call it. 
                    @$class_called->$func[1]($pass);
                    return;
                }else {
                    if(!function_exists($func)) { self::respond(404); }
                    @$func($pass); // It's just a function, no class.
                    return;
                }
                break;
            }else {
                $no_regex[] = $url;
            }
        }
        
        if(count(self::$routes) == count($no_regex)) { self::respond(404); }
    }
    
    /**
     * redo::route()
     * 
     * Adds up routes to the class $routes.
     * 
     * @param $routes array Array of routes supplied.
     * */
    public static function route(array $routes) {
        foreach($routes as $match => $function) {
            self::$routes[$match] = $function;
        }
    }
    
    /**
     * redo::func()
     * 
     * Executes function(s) anonymously.
     * Equivalent to call_user_func('function()...');
     * 
     * @param *
     * */
    public static function func() {
        foreach(func_get_args() as $arguments) {
            return call_user_func($arguments);
        }
    }
    
    /**
     * redo::set()
     * 
     * Sets one or more variables.
     * 
     * @param $var_name string The name of the variable.
     * @param $var_value string The value of the $var_name variable.
     * */
    public static function set($var_name, $var_value = '') {
        if(is_array($var_name)) {
            foreach($var_name as $k => $v) {
                self::$vars[$k] = $v;
            }
        }else {
            self::$vars[$var_name] = $var_value;
        }
    }
    
    /**
     * redo::get()
     * 
     * Returns the value of the $var_name variable.
     * 
     * @param $var_name string Name of the variable.
     * */
    public static function get($var_name) {
        if(array_key_exists($var_name, self::$vars)) { return self::$vars[$var_name]; }
    }
    
    /**
     * redo::clear()
     * 
     * Unsets one or more variables.
     * If the given $var_name is empty all the vars are unset.
     * Note: This also kills vars such as 'debug' or 'view.path', so use with cause.
     * 
     * @param $var_name string The name of the variable you'd like to unset.
     * */
    public static function clear($var_name) {
        if(!empty($var_name)) {
            unset(self::$vars[$var_name]);
            self::$vars = array_values(self::$vars);
        }else {
            self::$vars = array();
        }
    }
    
    /**
     * redo::respond()
     * 
     * Outputs various responses to different IDs given. Loads files from responses/$id.php
     * This can be overriden by a user function.
     * 
     * @param $id int The handle id of the response.
     * */
    public static function respond($id = 0) {
        if(isset(self::$vars['response.function']) && function_exists(self::$vars['response.function'])) {
            call_user_func(self::$vars['response.function'], $id);
            return;
        }
        
        require_once './responses/' . $id . '.php';
    }
    
    /**
     * redo::is_set()
     * 
     * Checks whether a variable has been set.
     * 
     * @param $var string The variable name.
     * */
    public static function is_set($var_name) {
        if(isset(self::$vars[$var_name])) { return true; }else { return false; }
    }
    
    /**
     * redo::throw_http()
     * 
     * Executes a header() call with different HTTP Status Codes.
     * If the $url is given, it redirects to.
     * 
     * @param $http_code int The HTTP code you'd like to display.
     * @param $url string The URL you'd like to redirect to (if any). 
     * */
    public static function throw_http($http_code = 301, $url = NULL) {
        header('HTTP/1.1 ' . $http_code . ' ' . self::$http_codes[$http_code]);
        if(!is_null($url)) {
            header('Location: ' . $location);
        }
    }
    
    /**
     * redo::view()
     * 
     * Renders a simple template file stored in 'view.path' directory.
     * The local variables are extracted automatically.
     * 
     * @param $file_name string The name of the template file you'd like to load. 
     * */
    public static function view($file_name) {
        if(!file_exists(self::$vars['view.path'] . $file_name)) { die('Can\'t find the file.'); }
        
        ob_start();
        extract(self::$vars);
        require_once self::$vars['view.path'] . $file_name;
        
        return ob_get_clean();
    }
    
    /**
     * redo::register()
     * 
     * Allows 3rd party class registration.
     * Afterwards, classes can be used via the local variables.
     * 
     * @param $class_name string The name of the class you'd like to register.
     * @param $construct_pass mixed Value passed to the constructor.
     * */
    public static function register($class_name, $construct_pass = '') {
        #self::$vars[$class_name] = new $class_name($construct_pass);
        $reflect = new ReflectionClass($class_name);
        self::$vars[$class_name] = $reflect->newInstanceArgs($construct_pass);
    }
    
    
    /**
     * redo::escape()
     * 
     * Escapes a string using filter_var & htmlspecialchars.
     * 
     * @param $data string The data you want to escape.
     * */
    public static function escape($data) {
        $data = filter_var($data, FILTER_SANITIZE_STRING);
        $data = htmlspecialchars($data);
        
        return $data;
    }
    
    /**
     * 
     * redo::cut()
     * 
     * Cuts text (shortens it).
     * 
     * @param $text string The text you would like to shorten.
     * @param $length int Maximum length of the text.
     * */
    public static function cut($text, $length) {
        if(strlen($text) > $length) {
            return substr_replace($text, '...', $length);
        }else { return $data; }
    }
    
    /**
     * redo::in()
     * 
     * Case insensitive check for a match between every character in $match.
     * For example, in('F0o', 'Foo') would check if there's 'F' in "Foo", '0' in "Foo" and so on.
     * If any of those fail, it's going to retun false, other than that it returns true.
     * 
     * @param $match string Set of characters to run the check through (qwertz~^žčš) etc.
     * @param $var string The variable used to check through.
     */
    public static function in($match, $var) {
        $var = str_split($var); // Split the var; output every character.
        
        foreach($var as $chars) {
            if(strstr($match, $chars)) {
                return true;
            }else { return false; }
        }
    }
    
    /**
     * redo::randomize()
     * 
     * Randomizes a string from the available characters ($characters).
     * So for example, randomize('ABC') could end up as BCA.
     * 
     * @param $chars string Available characters to use.
     * */
    public static function random_string($chars) {
        $chr = str_split($characters);
        
        $out = '';
        for($i = 0; $i < strlen($characters); $i++) {
            $random = rand(0, strlen($characters) - 1);
            $out .= $chr[$random];
        }
        
        return $out;
    }
    
    /**
     * redo::array_random()
     * 
     * Randomizes an array returning its true keys and values
     * (if they are set) instead of numbered keys.
     * 
     * @param $array array The array to randomize.
     * */
    public static function array_random($array) {
        uksort($array, function() { return rand(1, 2) >= rand(1, 2); });
        return $array;
    }
    
    /**
     * redo::match_any()
     * 
     * Performs a glob() like match. E.g.
     * match_any('hello.*.php', 'hello.anything.php') would return 'anything'.
     * Note: Do not use this within strings that contain a lot of non A-Z 0-9 characters,
     *       such as () [] * ? {} etc. This could break up the preg_match below.
     * 
     * @param $needle   string The match itself.
     * @param $haystack string The string in which the match is performed. 
     * */
    public static function match_any($needle, $haystack) {
        // Now do some regex fixes...
        $replace = array(
            '\\'    =>  '\\\\',
            '.'     =>  '\.',
            '+'     =>  '\+',
            '?'     =>  '\?',
            '^'     =>  '\^',
            '$'     =>  '\$',
            '['     =>  '\[',
            ']'     =>  '\]',
            '('     =>  '\(',
            ')'     =>  '\)',
            '|'     =>  '\|',
            '{'     =>  '\{',
            '}'     =>  '\}',
            '/'     =>  '\/',
            '\''    =>  '\\\'',
            '#'     =>  '\#',
        );
        
        foreach($replace as $k => $v) { $needle = str_replace($k, $v, $needle); }
        $needle = str_replace('*', '(.*?)', $needle); // This fixes the regex fixes...ugh..
        
        if(preg_match('/' . $needle . '/', $haystack, $res)) { return $res[1]; }else { return false; }
    }
    
    /**
     * TODO: Static class loading via routes.
     * */
}
