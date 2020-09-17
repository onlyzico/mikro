<?php

namespace Mikro;

class Mikro
{
    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var array
     */
    protected $autoloadPaths = [];

    /**
     * @var array
     */
    protected $autoloadAliases = [];

    /**
     * @var mixed
     */
    protected $viewsPath = 'views';

    /**
     * @var string
     */
    protected $requestPath;

    /**
     * @var array
     */
    protected $httpMethods = ['GET', 'POST', 'ANY'];

    /**
     * @var callable
     */
    protected $beforeStart;

    /**
     * @var callable
     */
    protected $afterStart;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @var array
     */
    protected $routePatterns = [
        '{any}' => '([^/]+)',
        '{num}' => '(\d+)',
        '{all}' => '(.*)'
    ];

    /**
     * @var array
     */
    protected $route = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var \Mikro\Mikro
     */
    protected static $instance;

    /**
     * @return \Mikro\Mikro
     */
    public static function create()
    {
        if (is_null(static::$instance)) {
            return new static;
        }
        
        return static::$instance;
    }

    /**
     * @return void
     */
    public function __construct()
    {
        static::$instance = $this;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setBaseDir(string $path)
    {
        $this->baseDir = $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getBaseDir(string $path = null)
    {
        if (is_null($this->baseDir)) {
            $this->baseDir = getcwd();
        }

        return rtrim($this->baseDir . '/' . $path, '/');
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setBasePath(string $path)
    {
        $this->basePath = $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getBasePath(string $path = null)
    {
        if (is_null($this->basePath)) {
            $this->basePath = '/';
        }

        if ($this->basePath === '/' && is_null($path)) {
            return $this->basePath;
        }

        return rtrim($this->basePath . '/' . $path, '/');
    }

    /**
     * @return void
     */
    public function autoloadRegister()
    {
        spl_autoload_register([$this, 'autoloadClass']);
    }

    /**
     * @param string $class
     *
     * @return class|false
     */
    public function autoloadClass(string $class)
    {
        $class = str_replace('\\', '/', ltrim($class, '\\'));
        $lower = strtolower($class);

        if (array_key_exists(strtolower($class), array_change_key_case($this->autoloadAliases))) {
            return class_alias($this->autoloadAliases[$class], $class);
        }

        foreach ($this->autoloadPaths as $path) {
            if (is_readable($filePath = realpath($path . "/{$class}.php"))) {
                return require $filePath;
            } elseif (is_readable($filePath = realpath($path . "/{$lower}.php"))) {
                return require $filePath;
            }
        }

        return false;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setAutoloadPath($path)
    {
        $this->autoloadPaths[] = $path;
    }

    /**
     * @param string[] $paths
     *
     * @return void
     */
    public function setAutoloadPaths($paths)
    {
        $this->autoloadPaths = array_merge($this->autoloadPaths, $paths);
    }

    /**
     * @param array $aliases
     *
     * @return void
     */
    public function setAutoloadAliases(array $aliases)
    {
        $this->autoloadAliases = array_merge($this->autoloadAliases, $aliases);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function autoloadFiles($path)
    {
        foreach (glob($path . '/*.php') as $filePath) {
            file_load($filePath);
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setViewsPath(string $path)
    {
        $this->viewsPath = $path;
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }

    /**
     * @param string $method
     *
     * @return void
     */
    public function addHttpMethod(string $method)
    {
        $this->httpMethods[] = $method;
    }

    /**
     * @param string[] $methods
     *
     * @return void
     */
    public function addHttpMethods(array $methods)
    {
        foreach ($methods as $method) {
            $this->addHttpMethod($method);
        }
    }

    /**
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->httpMethods;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return arr_get($_SERVER, 'REQUEST_METHOD');
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isHttpMethodExists(string $method)
    {
        return in_array(strtoupper($method), $this->getHttpMethods());
    }

    /**
     * @param string $method
     *
     * @return void
     */
    public function removeHttpMethod(string $method)
    {
        if (($index = array_search($method, $this->httpMethods)) !== false) {
            unset($this->httpMethods[$index]);
        }
    }

    /**
     * @param string[] $methods
     *
     * @return void
     */
    public function removeHttpMethods(array $methods)
    {
        foreach ($methods as $method) {
            $this->removeHttpMethod($method);
        }
    }

    /**
     * @return string
     */
    public function getRequestUri()
    {
        $uri = parse_url(arr_get($_SERVER, 'REQUEST_URI'), PHP_URL_PATH);
        $path = $this->getBasePath();

        return $path !== '/' ? str_replace($path, '', $uri) : $uri;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setRequestPath(string $path)
    {
        $this->requestPath = $path;
    }

    /**
     * @param bool $clean
     *
     * @return string
     */
    public function getRequestPath(bool $clean = true)
    {
        return preg_replace('/\/\/+/', '/', trim(str_replace(trim($this->getBasePath(), '/'), '', $this->getRequestUri()), '/'));
    }

    /**
     * @param string|null $key
     * @param mixed|null $fallback
     *
     * @return mixed|null
     */
    public function getQuery(string $key = null, $fallback = null)
    {
        return is_null($key) ? arr_get($_SERVER, 'QUERY_STRING') : arr_get($_GET, $key, $fallback);
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return \Mikro\Mikro
     *
     * @throws \ErrorException
     */
    public function __call(string $method, array $args)
    {
        if ($this->isHttpMethodExists($method) && count($args) >= 2 && is_string($args[0])) {
            return $this->addRoute((array) strtoupper($method), array_shift($args), $args);
        }
        
        throw new \ErrorException("Invalid HTTP method [{$method}].");
    }

    /**
     * @param string[] $methods
     * @param string $pattern
     * @param array $args
     *
     * @return \Mikro\Mikro
     *
     * @throws \ErrorException
     */
    public function match(array $methods, string $pattern, ...$args)
    {
        $methods = array_map('strtoupper', $methods);

        foreach ($methods as $method) {
            if ( ! $this->isHttpMethodExists($method)) {
                throw new \ErrorException("Invalid route method [{$method}].");
            }
        }

        return $this->addRoute($methods, $pattern, $args);
    }
    
    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function addRoutePattern(string $key, string $value)
    {
        $this->routePatterns[$key] = $value;
    }

    /**
     * @param string[] $method
     * @param string $pattern
     * @param array $args
     *
     * @return \Mikro\Mikro
     */
    public function addRoute(array $method, string $pattern, ...$args)
    {
        $args = array_shift($args);
        $name = count($args) === 2 ? $args[0] : null;
        $callback = count($args) === 2 ? $args[1] : $args[0];
    
        $this->routes[] = compact('method', 'pattern', 'name', 'callback');

        return $this;
    }

    /**
     * @return array
     *
     * @throws \ErrorException
     */
    public function getRoutes()
    {
        $routes = $this->routes;

        foreach ($routes as $index => &$route) {
            preg_match_all('~{.*?}~', $route['pattern'], $matches);

            $segmentsCount = count(arr_trim(explode('/', $route['pattern'])));
            
            if ($segmentsCount > 1 && $segmentsCount === count($matches[0])) {
                $route['priority'] = (77777 - $index);
            } else {
                $route['priority'] = $index;
            }

            if (preg_match('~{(.*?)\:.*?}$~', $route['pattern'], $matches)) {
                $route['pattern'] = preg_replace('~{.*?:(.*?)}~', '{$1}', $route['pattern']);
                $route['priority'] = is_numeric(end($matches)) ? 88888 + end($matches) : 100000;
            }
        }

        usort($routes, function($a, $b) {
            return strnatcmp($a['priority'], $b['priority']);
        });

        return $routes;
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getRoute($name)
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                return $route;
            }
        }
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $full
     *
     * @return string|null
     */
    public function getRoutePath(string $name, array $params = [], $full = true)
    {
        if ($route = $this->getRoute($name)) {
            $path = $route['pattern'];

            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $path = preg_replace("~\{{$key}\}~", $val, $path, 1);
                    }
                } else {
                    $path = preg_replace("~\{{$key}\}~", $value, $path, 1);
                }
            }

            return $full ? url_path($path) : $path;
        }
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @return string|null
     */
    public function getRouteUrl(string $name, array $params = [])
    {
        if ($path = $this->getRoutePath($name, $params, false)) {
            return site_url($path);
        }
    }

    /**
     * @param string $method
     * @param callback $pattern
     * @param bool $global
     *
     * @return void
     */
    public function action(string $name, $callback, bool $global = false)
    {
        $this->actions[] = compact('name', 'callback', 'global');
    }

    /**
     * @param string|string[] $name
     *
     * @return \Mikro\Mikro
     */
    public function withAction($name)
    {
        $route = array_pop($this->routes);
        $route['actions'] = array_merge(arr_get($route, 'actions', []), (array) $name);

        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param string|string[] $name
     *
     * @return \Mikro\Mikro
     */
    public function exceptAction($name)
    {
        $route = array_pop($this->routes);
        $route['except_actions'] = array_merge(arr_get($route, 'except_actions', []), (array) $name);

        $this->routes[] = $route;

        return $this;
    }
    
    /**
     * @return array
     */
    public function matchRoute()
    {
        $routes = $this->getRoutes();
        $requestPath = $this->requestPath ?: $this->getRequestPath();

        foreach ($routes as $route) {
            if (trim($route['pattern'], '/') === $requestPath) {
                return $route;
            }

            if (preg_match('~^' . strtr($route['pattern'], $this->routePatterns) . '$~', $requestPath, $matches)) {
                array_shift($matches);

                $route['params'] = $matches;

                return $route;
            }
        }

        throw new \ErrorException('No route matched.');
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function nextRoute($name)
    {
        if ($route = $this->getRoute($name)) {
            $this->route = array_replace($this->route, $route);
            
            return call_user_func_array($this->route['callback'], [$this]);
        }
    }

    /**
     * @return array
     */
    public function getCurrentRoute()
    {
        return $this->route;
    }

    /**
     * @return string|null
     */
    public function getRouteName()
    {
        return $this->route['name'];
    }

    /**
     * @param string|string[] $name
     *
     * @return bool
     */
    public function isRoute($name)
    {
        return in_array($this->getRouteName(), (array) $name);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return arr_get($this->route, 'params');
    }

    /**
     * @param string|int $key
     * @param mixed|null $fallback
     *
     * @return mixed|null
     */
    public function getParam($key, $fallback = null)
    {
        return arr_get($this->getParams(), $key, $fallback);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasParam(string $key)
    {
        return in_array($key, array_keys($this->getParams()));
    }

    /**
     * @param string[] $keys
     *
     * @return bool
     */
    public function haveParams(array $keys)
    {
        foreach ($keys as $key) {
            if ( ! $this->hasParam($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function isParam(string $key, string $value)
    {
        return $this->getParam($key) === $value;
    }

    /**
     * @param array $vars
     *
     * @return bool
     */
    public function isParams(array $vars)
    {
        foreach ($vars as $key => $value) {
            if ($this->getParam($key) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $status
     *
     * @return \Mikro\Mikro
     */
    public function status(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $encoding
     *
     * @return \Mikro\Mikro
     */
    public function encoding(string $encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @param string|int $key
     * @param string|null $value
     *
     * @return \Mikro\Mikro
     */
    public function header($key, string $value = null)
    {
        if (is_int($key) || is_null($value)) {
            $this->headers[] = is_int($key) ? $value : $key;
        } else {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return \Mikro\Mikro
     */
    public function headers(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    /**
     * @param string $url
     * @param int $status
     *
     * @return \Mikro\Mikro
     */
    public function redirect(string $url, int $status = 302)
    {
        return $this->status($status)->header('location', $url);
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @return \Mikro\Mikro
     */
    public function routeRedirect(string $name, array $params = [])
    {
        if ($url = $this->getRouteUrl($name, $params)) {
            return $this->redirect($url);
        }
    }

    /**
     * @param string $type
     *
     * @return \Mikro\Mikro
     */
    public function type(string $type)
    {
        return $this->header('content-type', $type);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getHeader($key)
    {
        return arr_get($this->getHeaders(), $key);
    }

    /**
     * @param string|string[] $keys
     *
     * @return void
     */
    public function removeHeader($keys)
    {
        foreach ((array) $keys as $key) {
            if (($index = array_search($key, $this->headers)) !== false) {
                arr_erase($this->headers, $key);
            }
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return \Mikro\Mikro
     */
    public function data(string $key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $fallback
     *
     * @return mixed|null
     */
    public function getData(string $key, $fallback = null)
    {
        return arr_get($this->data, $key, $fallback);
    }

    /**
     * @param string $key
     *
     * @return \Mikro\Mikro
     */
    public function removeData(string $key)
    {
        arr_erase($this->data, $key);

        return $this;
    }

    /**
     * @param mixed $body
     * @param int $status
     *
     * @return \Mikro\Mikro
     */
    public function body($body, int $status = 200)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param array $payload
     * @param int $status
     *
     * @return \Mikro\Mikro
     */
    public function json(array $payload, int $status = 200)
    {
        return $this->type('application/json')->body(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $xml
     * @param int $status
     *
     * @return \Mikro\Mikro
     */
    public function xml(string $xml, int $status = 200)
    {
        return $this->type('text/xml')->body($xml);
    }

    /**
     * @param string $path
     * @param array $data
     *
     * @return string
     *
     * @throws \ErrorException
     */
    public function view(string $path, array $data = [])
    {
        if ( ! is_readable($filePath = $this->getBaseDir() . "/" . $this->getViewsPath() . "/{$path}.php")) {
            throw new \ErrorException("View template [{$path}] doesn't exists.");
        }

        $this->data = array_replace_recursive($this->data, $data);

        ob_start();

        $this->template($path, $data);

        return $this->body(ob_get_clean());
    }

    /**
     * @param string $path
     * @param array $data
     *
     * @return void|null
     */
    public function template(string $path, array $data = [])
    {
        if (is_readable($filePath = $this->getBaseDir() . "/" . $this->getViewsPath() . "/{$path}.php")) {
            $data = array_replace_recursive($this->data, $data);

            extract($data);

            include $filePath;
        }
    }

    /**
     * @return mixed|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return \Mikro\Mikro
     */
    public function removeBody()
    {
        return $this->body(null);
    }

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function beforeStart($callback)
    {
        $this->beforeStart = $callback;
    }

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function afterStart($callback)
    {
        $this->afterStart = $callback;
    }

    /**
     * @return void
     */
    public function start()
    {
        if (is_callable($this->beforeStart)) {
            call_user_func_array($this->beforeStart, [$this]);
        }

        $this->route = $this->matchRoute();

        if ( ! in_array('ANY', $this->route['method']) &&  ! in_array($this->getHttpMethod(), $this->route['method'])) {
            $response = $this->status(405);
        }

        if ( ! isset($response)) {
            $response = call_user_func_array($this->route['callback'], [$this]);

            if (is_array($response)) {
                $response = $this->json($response);
            } elseif (is_string($response)) {
                $response = $this->body($response);
            }

            if ($actions = $this->actions) {
                foreach ($actions as $action) {
                    if ( ! in_array($action['name'], arr_get($this->route, 'except_actions', [])) && ($action['global'] || in_array($action['name'], arr_get($this->route, 'actions', [])))) {
                        if ( ! is_null($actionResponse = call_user_func_array($action['callback'], [$this]))) {
                            $response = $actionResponse;
                        }
                    }
                }
            }
        }

        if (is_callable($this->afterStart)) {
            call_user_func_array($this->afterStart, [$this]);
        }
    }

    /**
     * @return void
     */
    public function run()
    {
        if (($uri = $this->getRequestUri()) !== '/' && (preg_match('/\/\/+/', $uri) || preg_match('/\/$/i', $uri))) {
            $this->redirect(full_url(), 301);
        } else {
            $this->start();
        }

        http_response_code($this->status);

        if ( ! array_key_exists('content-type', $this->headers)) {
            $this->headers['content-type'] = 'text/html; charset=' . $this->encoding;
        }

        foreach ($this->headers as $key => $value) {
            if (is_int($key)) {
                header($value);
            } else {
                header("{$key}:{$value}");
            }
        }

        if ( ! is_null($body = $this->body)) {
            echo $body;
        }
    }
}