<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 23/01/16
 * Time: 2:43 PM
 */

namespace Http\Route;


use Http\Contracts\Cacheable;
use Http\Request\Request;

class Route implements Cacheable
{
    protected $uri;

    protected $parsedUri;

    protected $methods = [];

    protected $action;

    protected $controller;

    protected $classMethod;

    protected $parameters = [];

    protected $parameterValues = [];

    protected $parameterNames = [];

    protected $options = [];

    protected $middleware = [];

    protected $allowedOptions = ['middleware', 'prefix', 'cacheable', 'variables'];

    /**
     * Route constructor.
     * @param $uri
     * @param array $methods
     * @param $action
     * @param array $options
     */
    public function __construct($uri, $methods, $action, $options = [])
    {
        $this->setUri($uri);
        $this->setMethods($methods);
        $this->parseAction($action);
        if (!empty($options)) {
            $this->setOptions($options);
        }

        $this->parseUri();
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param $uri
     * @return Route
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParsedUri()
    {
        if (!isset($this->parsedUri)) {
            $this->parseUri($this->uri);
        }
        return $this->parsedUri;
    }

    /**
     * @param $parsedUri
     * @return $this
     */
    public function setParsedUri($parsedUri)
    {
        $this->parsedUri = $parsedUri;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param $methods
     * @return Route
     */
    public function setMethods($methods)
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }
        $this->methods = $methods;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $action
     * @return Route
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param mixed $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function getParameterNames()
    {
        return $this->parameterNames;
    }

    /**
     * @param mixed $parameterNames
     */
    public function setParameterNames(array $parameterNames)
    {
        $this->parameterNames = $parameterNames;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param $controller
     * @return Route
     */
    public function setController($controller)
    {
        if (!is_callable($controller) && !is_string($controller)) {
            throw new \InvalidArgumentException(
                "Invalid controller of type {${gettype($controller)}} given. Expect string or callable"
            );
        }

        $this->controller = $controller;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassMethod()
    {
        return $this->classMethod;
    }

    /**
     * @param mixed $classMethod
     */
    public function setClassMethod($classMethod)
    {
        $this->classMethod = $classMethod;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return Route
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        foreach ($options as $key => $val) {
            $method = 'set' . ucfirst($key);
            if (in_array($key, $this->allowedOptions) && method_exists($this, $method)) {
                $this->{$method}($val);
            }
        }

        return $this;
    }

    public function hasMiddleware()
    {
        return !empty($this->middleware);
    }

    public function setMiddleware($middleware)
    {
        $this->middleware = $middleware;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function isInteger($param)
    {
        return isset($this->parameters[$param]['isInt']) ? $this->parameters[$param]['isInt'] : false;
    }

    public function isAlpha($param)
    {
        return isset($this->parameters[$param]['isAlpha']) ? $this->parameters[$param]['isAlpha'] : false;
    }

    public function isOptional($param)
    {
        return isset($this->parameters[$param]['isOptional']) ? $this->parameters[$param]['isOptional'] : false;
    }

    public function issetDefault($param)
    {
        return isset($this->parameters[$param]['default']);
    }

    public function getDefault($param)
    {
        return isset($this->parameters[$param]['default']) ? $this->parameters[$param]['default'] : null;
    }

    public function parseUri($uri = null)
    {
        if (is_null($uri)) {
            $uri = $this->uri;
        }

        if (preg_match_all('/\{([a-zA-Z0-9\:\?\=]+)\}/', $uri, $matches)) {

            foreach ($matches[1] as $index => $match) {
                $pattern = '([^/]+)';
                $matchArr = explode(':', $match);
                $param = $matchArr[0];
                $this->parameterNames[] = $param;
                array_shift($matchArr);
                foreach ($matchArr as $option) {
                    if ($option === 'num' || $option === 'i') {
                        $this->parameters[$param]['isInt'] = true;
                        $pattern = '([0-9]+)';
                    }
                    if ($option === 'alpha' || $option === 'a') {
                        $this->parameters[$param]['isAlpha'] = true;
                        $pattern = '([\w]+)';
                    }
                    if ($option === '?' || strpos($option, 'default') !== false) {
                        $this->parameters[$param]['isOptional'] = true;
                        $pattern .= '?';
                    }
                    if (strpos($option, 'default') !== false) {
                        $this->parameters[$param]['default'] = explode('=', $option)[1];
                    }
                }
                $uri = preg_replace('#\{' . $matches[1][$index] . '\}#', '(?P<' . $param . '>' . $pattern . ')', $uri);
            }
        }

        $this->setParsedUri('#^' . $uri . '$#');

        return $this;
    }

    public function parseAction($action)
    {
        if (is_callable($action)) {
            $this->setController($action);
        } elseif (isset($action['controller']) && isset($action['method'])) {
            $this->setController($action['controller']);
            $this->setClassMethod($action['method']);
        } elseif (strpos($action, '@') !== false) {
            $parts = explode('@', $action);
            $this->setController($parts[0]);
            $this->setClassMethod($parts[1]);
        }

        $this->setAction($action);

        return $this;
    }

    public function setPrefix($prefix, $uri = null)
    {
        if (is_null($uri)) {
            $uri = $this->uri;
        }
        $this->uri = '/' . trim($prefix, '/') . '/' . ltrim($uri, '/');
        return $this;
    }

    protected function bindValues(array $matches)
    {
        foreach ($this->parameterNames as $key) {
            if (isset($matches[$key])) {
                if ($matches[$key] === "" && $this->isOptional($key)) {
                    $matches[$key] = $this->getDefault($key);
                }
                $value = $this->isInteger($key) ? intval($matches[$key]) : $matches[$key];
                $this->setParameterValue($key, $value);
            }
        }
    }

    /**
     * @return array
     */
    public function getParameterValues()
    {
        return $this->parameterValues;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setParameterValue($key, $value)
    {
        $this->parameters[$key]['value'] = $value;
        $this->parameterValues[$key] = $value;
        return $this;
    }

    public function isMatch(Request $request)
    {
        if (preg_match($this->getParsedUri(), $request->getPath(), $matches)) {
            $this->bindValues($matches);
            return true;
        }

        return false;
    }

    public function setCacheable($val = true)
    {
        $this->options['cacheable'] = $val;
    }

    public function isCacheable()
    {
        return isset($this->options['cacheable']) ? $this->options['cacheable'] : false;
    }

    public function setVariables(array $variables = [])
    {
        return $this->options['variables'] = $variables;
    }

    public function getVariables()
    {
        return isset($this->options['variables']) ? $this->options['variables'] : [];
    }
    
    public function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }
    
    public function __sleep()
    {
        return ['uri', 'parsedUri', 'methods', 'action', 'controller', 'classMethod', 'parameters', 'parameterValues', 'parameterNames', 'options', 'middleware', 'allowedOptions'];
    }
}