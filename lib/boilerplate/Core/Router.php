<?php

namespace boilerplate\Core;

use boilerplate\Utility\ConfigurationOption;
use boilerplate\Utility\ProvidesResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router {
    const ALLOWED_METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

    protected static $routes = array();
    protected static $controller_namespace = '';
    protected static $route_prefix = '';
    protected static $current_route = array();
    protected static $current_request;

    /**
     * @param Request|null $request If no Request object is passed, the current request is used
     */
    public static function handle(Request $request = null) : void {
        if($request === null) $request = Request::createFromGlobals();

        Router::$current_request = $request;

        $route = Router::matchRequest($request);
        Router::$current_route = $route;

        $content = null;
        if(is_callable($route['callback'])) $content = $route['callback'](...$route['parameters']);
        elseif(is_string($route['callback'])) {
            $preg = array();
            if(preg_match('/^(.+)@(.+)$/', $route['callback'], $preg) == 1) {
                list(,$class, $method) = $preg;

                if(class_exists($class)) {
                    $instance = new $class();
                    if(method_exists($instance, $method)) {
                        $content = $instance->$method(...$route['parameters']);
                    }
                }
            }
        }

        $response = Router::makeResponseForContent($content);

        $response->prepare($request);
        $response->send();
    }

    protected static function makeResponseForContent($content) : ?Response {
        $response = null;

        if(is_string($content)) $response = new Response($content, Response::HTTP_OK, array('Content-Type' => 'text/html'));
        elseif(is_array($content)) $response = new JsonResponse($content);
        elseif($content instanceof ProvidesResponse) $response = $content->getResponse();
        elseif($content instanceof Response) $response = $content;
        else {
            // TODO: 500
            $response = new Response('500', Response::HTTP_INTERNAL_SERVER_ERROR, array('Content-Type' => 'text/html'));
        }

        return $response;
    }

    /**
     * This function should not be called directly. Instead, use the helper functions provided for the different HTTP verbs
     * (like get(), post(), any(), match() etc.).
     *
     * @param string $method The HTTP verb (method) the route should match. Available options are defined in
     *          `Router::ALLOWED_METHODS`. Case-insensitive.
     * @param string $uri The URI the route should match. You can include parameters in curly brackets. (e.g. `home` or
     *          `post/{post_id}/comment/{comment_id}`)
     * @param callable|string $callback A function that returns the value for the response. This can either be a callable
     *          (an anonymous function, the name of a function, `'ClassName::staticMethodName'` etc.) or a string in the
     *          format `'ClassName@nonStaticFunction'` if you want to call a controller. It the latter case, the class will
     *          be instantiated automatically but its constructor *cannot* have any arguments.
     *          The function can return a string (which will be outputted as text/html), an array (which will be outputted
     *          as JSON) or a Response object (which will be outputted directly).
     * @param string|null $name An (optional) name for the route that can be used later to generate a URL. If none is given,
     *          `uniqid()` is used.
     * @return bool Whether the creation of the route was successful or failed (e.g. because the route name is taken)
     */
    protected static function addRoute(string $method, string $uri, $callback, string $name = null) : bool {
        $method = strtoupper($method);
        if(!in_array($method, Router::ALLOWED_METHODS)) {
            Application::instance()->logger->debug('Tried to setup route using invalid method: "' . $method . '"', array('route' => func_get_args()));
            return false;
        }

        $uri = trim($uri, '/');
        $uri = Router::$route_prefix == '' ? $uri : Router::$route_prefix . '/' . $uri;

        $regex = '%^' . preg_replace('/\\\{.+?\\\}/', '([^/]+)', preg_quote($uri, '%')) . '$%';

        if(is_string($callback)) $callback = (Router::$controller_namespace == '' ? '' : Router::$controller_namespace . '\\') . $callback;

        if(empty($name)) $name = uniqid();
        if(@in_array($name, array_keys(array_merge(...array_values(Router::$routes))))) {
            Application::instance()->logger->debug('Tried to setup named route "' . $name . '" but name already exists.', array('route' => func_get_args()));
            return false;
        }
        Router::$routes[$method][$name] = array('regex' => $regex, 'uri' => $uri, 'callback' => $callback);

        return true;
    }

    protected static function matchRequest(Request $request) : array {
        $path = urldecode(trim($request->getPathInfo(), '/'));
        $parameters = array();

        foreach(Router::$routes[$request->getMethod()] as $name => $route) {
            if(preg_match($route['regex'], $path, $parameters) === 1) {
                array_shift($parameters);
                $route['parameters'] = $parameters;
                $route['name'] = $name;
                return $route;
            };
        }

        // TODO: 404
        return array('callback' => function() { return new Response('404', Response::HTTP_NOT_FOUND); }, 'parameters' => array());
    }

    protected static function getRouteWithName(string $name) : ?array {
        return @array_merge(...array_values(Router::$routes))[$name];
    }

    /**
     * @param string $route_name The name given to the route on definition
     * @param array $parameters An array of all the parameters the route requires. It has to be structured like this:
     *          array('param_1' => $value_1, 'param_2' => $value_2) and `param_n` has to be the exact name given in the route definition
     * @param bool $relative Whether to create a relative URL (like `/home`) or to include the base URL (like `https://example.com/home`)
     * @return string
     */
    public static function getRouteUrl(string $route_name, array $parameters = array(), bool $relative = true) : string {
        $route = Router::getRouteWithName($route_name);
        $url = $route['uri'];
        foreach($parameters as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }
        return $relative ? '/' . $url : Application::instance()->config->get(ConfigurationOption::BASE_URL) . '/' . $url;
    }

    public static function getRoutes() : array { return Router::$routes; }

    /**
     * All routes defined after this function was called and before stopRoutePrefix() is called, will have their URI
     * prefixed with $prefix.
     *
     * @param string $prefix
     */
    public static function startRoutePrefix(string $prefix) : void { Router::$route_prefix = empty(trim($prefix)) ? '' : trim($prefix, '/'); }
    public static function stopRoutePrefix() : void { Router::$route_prefix = ''; }

    /**
     * Set a namespace for controllers (and static class functions) that are used as route callbacks.
     *
     * Calling this function without an argument will reset the namespace to none.
     *
     * @param string $namespace
     */
    public static function setControllerNamespace(string $namespace = '') : void { Router::$controller_namespace = empty(trim($namespace)) ? '' : rtrim($namespace, '\\'); }

    public static function currentRoute() : array { return Router::$current_route; }
    public static function currentRouteName() : ?string { return @Router::$current_route['name']; }
    public static function currentRouteUri() : ?string { return @Router::$current_route['uri']; }
    public static function currentRouteParameters() : ?array { return @Router::$current_route['parameters']; }

    public static function getCurrentRequest() : Request { return Router::$current_request ?? Request::createFromGlobals(); }

    /*
     * Helper methods for adding routes
     */
    public static function get(string $uri, $callback, string $name = null) : bool { return Router::addRoute('GET', $uri, $callback, $name); }
    public static function post(string $uri, $callback, string $name = null) : bool { return Router::addRoute('POST', $uri, $callback, $name); }
    public static function put(string $uri, $callback, string $name = null) : bool { return Router::addRoute('PUT', $uri, $callback, $name); }
    public static function patch(string $uri, $callback, string $name = null) : bool { return Router::addRoute('PATCH', $uri, $callback, $name); }
    public static function delete(string $uri, $callback, string $name = null) : bool { return Router::addRoute('DELETE', $uri, $callback, $name); }
    public static function options(string $uri, $callback, string $name = null) : bool { return Router::addRoute('OPTIONS', $uri, $callback, $name); }

    /**
     * Let the route match all methods.
     * You cannot pass a $name to this function because route names have to be unique for every method.
     *
     * @param string $uri
     * @param callable|string $callback
     * @return bool
     */
    public static function any(string $uri, $callback) : bool { return Router::many(Router::ALLOWED_METHODS, $uri, $callback); }

    /**
     * Let the route match all methods given in $methods.
     * You cannot pass a $name to this function because route names have to be unique for every method.
     *
     * @param string[] $methods An array of all methods to match (e.g. array('GET', 'POST'))
     * @param string $uri
     * @param callable|string $callback
     * @return bool
     */
    public static function many(array $methods, string $uri, $callback) : bool {
        foreach($methods as $method) if(!Router::addRoute($method, $uri, $callback)) return false;
        return true;
    }
}
