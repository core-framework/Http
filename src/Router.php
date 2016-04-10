<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 19/03/16
 * Time: 6:38 PM
 */

namespace Http\Router;

use Http\Response\Response;
use Http\Route\Route;
use Http\Contracts\Config;
use Http\Request\Request;
use Http\Contracts\Middleware;
use Http\Exceptions\ControllerMethodNotFoundException;
use Http\Exceptions\ControllerNotFoundException;
use Http\Exceptions\PageNotFoundException;
use Http\Parameters\Parameters;


class Router
{
    protected $currentRoute;

    protected $routes = [];

    protected $middlewares = [];

    protected $request;

    protected $currentOptions = [];

    protected $config;

    protected $basePath;

    /**
     * Router constructor.
     * @param $basePath
     * @param Config $config
     */
    public function __construct($basePath, Config $config)
    {
        $this->basePath = $basePath;
        $this->config = $config;

        $this->init();
    }

    protected function init()
    {
        $this->loadRoutes();
        $this->loadMiddlewares();
    }


    /**
     * Set whether to use Aesthetic Routing (/{controller}/{method}/{param1}/{param2})
     *
     * @param bool $bool
     */
    public function useAestheticRouting($bool = true)
    {
        $this->config['useAestheticRouting'] = boolval($bool);
    }

    /**
     * Determine if Aesthetic Routing is set
     *
     * @return mixed
     */
    public function isAestheticRouting()
    {
        return $this->config['useAestheticRouting'];
    }

    /**
     * Get Controller namespace
     *
     * @return mixed
     */
    public function getControllerNamespace()
    {
        return $this->config['controllerNamespace'];
    }

    /**
     * Get Request
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Load routes from Config
     */
    protected function loadRoutes()
    {
        $file = $this->getAppPath() . $this->config->get('router:routeFile', '/routes.php');
        if (is_readable($file)) {
            require($file);
        }
    }

    protected function loadMiddlewares()
    {
        $this->middlewares = $this->config->get('router:middlewares', []);
    }

    /**
     * Get base path
     *
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return mixed
     */
    public function getAppPath()
    {
        return $this->basePath . '/app';
    }

    /**
     * Set Path prefix
     *
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->currentOptions['prefix'] = $prefix;
        return $this;
    }

    /**
     * Get Path prefix
     *
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->currentOptions['prefix'];
    }

    /**
     * Get current Route
     *
     * @return Route
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * Set current Route
     *
     * @param Route $currentRoute
     */
    public function setCurrentRoute($currentRoute)
    {
        $this->currentRoute = $currentRoute;
    }

    public function getRoutes($method = null)
    {
        if (is_null($method)) {
            return $this->routes;
        }
        return $this->routes[strtoupper($method)];
    }

    /**
     * Add route to routes (collection)
     *
     * @param $uri
     * @param $methods
     * @param $action
     * @param array $options
     * @return $this
     */
    protected function addRoute($uri, $methods, $action, $options = [])
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        if (!empty($this->currentOptions)) {
            $options = array_merge($options, $this->currentOptions);
        }

        foreach ($methods as $i => $method) {
            $this->routes[$method][$uri] = new Route($uri, $methods, $action, $options);
        }

        return $this;
    }

    /**
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function get($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['GET'], $action, $options);
    }

    /**
     * Add POST Route to routes (collection)
     *
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function post($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['POST'], $action, $options);
    }

    /**
     * Add PUT Route to routes (collection)
     *
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function put($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['PUT'], $action, $options);
    }

    /**
     * Add PATCH Route to routes (collection)
     *
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function patch($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['PATCH'], $action, $options);
    }

    /**
     * Add DELETE Route to routes (collection)
     *
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function delete($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['DELETE'], $action, $options);
    }

    /**
     * Add ALL (GET, POST, PUT, PATCH, DELETE) Route to routes (collection)
     *
     * @param $uri
     * @param $action
     * @param array $options
     * @return Router
     */
    public function any($uri, $action, $options = [])
    {
        return $this->addRoute($uri, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $action, $options);
    }

    /**
     * Group Route(s) together
     *
     * @param array $options
     * @param \Closure $callback
     */
    public function group($options = [], \Closure $callback)
    {
        $this->setOptions($options);
        call_user_func($callback, $this);
        $this->deleteOptions();
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->currentOptions = $options;
    }

    /**
     *
     */
    public function deleteOptions()
    {
        $this->currentOptions = [];
    }

    // Request Handling
    /**
     * Handle Request
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        try {
            $route = $this->parse($request);
            $response = $this->run($route);
        } catch (\Exception $e) {
            $response = $this->makeExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Create Response from Exception
     *
     * @param \Exception $exception
     * @return Response
     */
    public function makeExceptionResponse(\Exception $exception)
    {
        return new Response($exception->getMessage(), $exception->getCode());
    }

    /**
     * Run Route parsed by Router
     *
     * @param Route $route
     * @return mixed
     * @throws \HttpRuntimeException
     */
    public function run(Route $route)
    {
        $next = $this->getNextCallable($route);
        if ($route->hasMiddleware()) {
            $middleware = $route->getMiddleware();
            return $this->executeMiddleware($middleware, $this, $next);
        }

        return $next();
    }

    /**
     * Executes bound middleware
     *
     * @param $middleware
     * @param Router $router
     * @param \Closure $next
     * @return mixed
     */
    protected function executeMiddleware($middleware, Router $router, \Closure $next)
    {
        if (class_exists($middleware, true)) {
            $middlewareObj = new $middleware();
            if (!$middlewareObj instanceof Middleware) {
                throw new \RuntimeException("Given Middleware does not comply with the MiddlewareContract!", 600);
            }
            return $middlewareObj->run($router, $next);
        }

        throw new \RuntimeException("Middleware - {$middleware} not found.", 604);
    }

    protected function getControllerArgs()
    {
        return [$this->getBasePath(), $this, Config::get()];
    }

    /**
     * Get next executions as Callable
     *
     * @param Route $route
     * @return \Closure
     * @throws \HttpRuntimeException
     */
    protected function getNextCallable(Route $route)
    {
        $controller = $route->getController();
        $classMethod = $route->getClassMethod();
        $namespace = $this->getControllerNamespace();
        $payload = $route->getParameterValues();
        $args = $this->getControllerArgs();
        
        if (is_callable($controller)) {
            $next = function () use ($controller, $payload) {
                return $controller($payload);
            };
        } else {
            $class = $namespace . '\\' . $controller;
            if (class_exists($class, true)) {
                $next = function () use ($class, $args, $classMethod, $payload) {
                    return $this->runController($this->makeController($class, $args), $classMethod, $payload);
                };
            } else {
                throw new ControllerNotFoundException;
            }
        }

        return $next;
    }

    public function makeController($class, array $args)
    {
        $reflection = new \ReflectionClass($class);
        return $reflection->newInstanceArgs($args);
    }

    protected function runController($obj, $classMethod, $payload = [])
    {
        if (!method_exists($obj, $classMethod)) {
            throw new ControllerMethodNotFoundException;
        }
        return $obj->{$classMethod}($payload);
    }

    /**
     * Parse Request to get Matching Route
     *
     * @param Request $request
     * @return mixed|null
     * @throws PageNotFoundException
     */
    public function parse(Request $request)
    {
        $this->request = $request;
        $method = $request->getHttpMethod();
        $routes = $this->getRoutes($method);
        $route = $this->findRoute($routes, $request);
        $this->setCurrentRoute($route);
        return $route;
    }

    /**
     * Find matching Route from Route(s)
     * 
     * @param array $routes
     * @param Request $request
     * @return mixed|null
     * @throws PageNotFoundException
     */
    protected function findRoute(array $routes, Request $request)
    {
        $path = $request->getPath();
        if (isset($routes[$path])) {
            $route = $routes[$path];
        } else {
            $route = Parameters::find(
                $routes,
                function ($key, $value) use ($request) {
                    /** @var $value Route */
                    return $value->isMatch($request);
                }
            );
        }

        if (!$route instanceof Route) {
            throw new PageNotFoundException;
        }

        return $route;
    }
    
    public function addPrefix($prefix, $uri)
    {
        $uri = '/' . trim($prefix, '/') . '/' . ltrim($uri, '/');
        return $uri;
    }
}