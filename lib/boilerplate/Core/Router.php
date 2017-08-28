<?php

namespace boilerplate\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router {
    const ALLOWED_METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

    protected static $routes = array();

    public static function handle($request = null) {
        if($request === null) $request = Request::createFromGlobals();

        $route = Router::matchRequest($request);
        if(!is_array(@$route['parameters'])) $route['parameters'] = array();
        $content = $route['callback'](...$route['parameters']);

        $response = null;

        if(is_string($content)) {
            $response = new Response($content, Response::HTTP_OK, array('Content-Type' => 'text/html'));
        }
        elseif(is_array($content)) {
            $response = new Response(json_encode($content), Response::HTTP_OK, array('Content-Type' => 'application/json'));
        }
        elseif($content instanceof Response) {
            $response = $content;
        }
        else {
            // TODO: 500
            $response = new Response('500', Response::HTTP_INTERNAL_SERVER_ERROR, array('Content-Type' => 'text/html'));
        }

        $response->prepare($request);
        $response->send();
    }

    protected static function addRoute(string $method, string $uri, \Closure $callback, string $name = null) : bool {
        $method = strtoupper($method);
        if(!in_array($method, Router::ALLOWED_METHODS)) {
            Application::instance()->logger->debug('Tried to setup route using invalid method: "' . $method . '"', array('route' => func_get_args()));
            return false;
        }

        $uri = trim($uri, '/');
        $regex = '%^' . preg_replace('/\\\{.+?\\\}/', '([^/]+)', preg_quote($uri, '%')) . '$%';

        if($name === null) $name = uniqid();
        if(@in_array($name, array_keys(array_merge(...array_values(Router::$routes))))) {
            Application::instance()->logger->debug('Tried to setup named route "' . $name . '" but name already exists.', array('route' => func_get_args()));
            return false;
        }
        Router::$routes[$method][$name] = array('regex' => $regex, 'uri' => $uri, 'callback' => $callback);

        return true;
    }

    protected static function matchRequest(Request $request) {
        $path = trim($request->getPathInfo(), '/');
        $parameters = array();

        foreach(Router::$routes[$request->getMethod()] as $route) {
            if(preg_match($route['regex'], $path, $parameters) === 1) {
                array_shift($parameters);
                $route['parameters'] = $parameters;
                return $route;
            };
        }

        // TODO: 404
        return array('callback' => function() { return new Response('404', Response::HTTP_NOT_FOUND); });
    }

    protected static function getRouteWithName(string $name) {
        return @array_merge(...array_values(Router::$routes))[$name];
    }

    // $parameters has to be: array('param_1' => $value_1, 'param_2' => $value_2) and `param_n` has to be the exact name given in the route definition
    public static function getRouteUrl(string $route_name, array $parameters = array(), bool $relative = true) : string {
        $route = Router::getRouteWithName($route_name);
        $url = $route['uri'];
        foreach($parameters as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }
        return $relative ? $url : Application::instance()->config->get(ConfigurationOption::BASE_URL) . '/' . $url;
    }

    /*
     * Helper methods for adding routes
     */
    public static function get(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('GET', $uri, $callback, $name); }
    public static function post(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('POST', $uri, $callback, $name); }
    public static function put(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('PUT', $uri, $callback, $name); }
    public static function patch(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('PATCH', $uri, $callback, $name); }
    public static function delete(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('DELETE', $uri, $callback, $name); }
    public static function options(string $uri, \Closure $callback, string $name = null) : bool { return Router::addRoute('OPTIONS', $uri, $callback, $name); }

    public static function any(string $uri, \Closure $callback) : bool { return Router::match(Router::ALLOWED_METHODS, $uri, $callback); }

    public static function match(array $methods, string $uri, \Closure $callback) : bool {
        foreach($methods as $method) if(!Router::addRoute($method, $uri, $callback)) return false;
        return true;
    }
}
