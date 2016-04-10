<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This file is part of the Core Framework package.
 *
 * (c) Shalom Sam <shalom.s@coreframework.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\Request;

use Http\Contracts\Cacheable;

/**
 * The class that handles the incoming request to server
 *
 * <code>
 *  $request = Container::get('Request');
 * </code>
 *
 * @package Core\Request
 * @version $Revision$
 * @license http://creativecommons.org/licenses/by-sa/4.0/
 * @link http://coreframework.in
 * @author Shalom Sam <shalom.s@coreframework.in>
 */
class Request implements Cacheable
{
    public static $validHttpMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'COPY',
        'HEAD',
        'OPTIONS',
        'LINK',
        'UNLINK',
        'PURGE',
        'LOCK',
        'UNLOCK',
        'PROPFIND',
        'VIEW'
    ];

    /**
     * @var string The URL/query string (relative path)
     */
    protected $path;

    /**
     * @var string The request httpMethod .i.e. GET, POST, PUT and DELETE
     */
    public $httpMethod;
    /**
     * @var array Contains the sanitized array of the global $_GET variable
     */
    public $GET;
    /**
     * @var array Contains the sanitized array of the global $_POST variable
     */
    public $POST;
    /**
     * @var array Contains the $_SERVER data from the request
     */
    public $server;
    /**
     * @var array Request Headers
     */
    public $headers;
    /**
     * @var array Contains the cookie data from the request
     */
    public $cookies;
    /**
     * @var array Contains $_FILES data
     */
    public $files;
    /**
     * @var null|string Contains the Request Body
     */
    public $body;
    /**
     * @var bool Defines if operating in development mode
     */
    public $devMode = false;
    /**
     * @var bool Defines if Request is Ajax
     */
    protected $isAjax = false;

    protected static $http_header_prefix = 'HTTP_';
    protected static $http_nonprefixed_headers = array(
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'CONTENT_MD5',
    );

    /**
     * @var array An array of illegal characters
     */
    private $illegal = [
        '$',
        '*',
        '"',
        '\'',
        '<',
        '>',
        '^',
        '(',
        ')',
        '[',
        ']',
        '\\',
        '!',
        '~',
        '`',
        '{',
        '}',
        '|',
        '%',
        '+',
        '?php'
    ];

    /**
     * Request constructor.
     *
     * @param array $GET
     * @param array $POST
     * @param array $server
     * @param array $cookies
     * @param array $files
     * @param string $body
     */
    public function __construct(
        array $GET = [],
        array $POST = [],
        array $server = [],
        array $cookies = [],
        array $files = [],
        $body = null
    ) {
        $this->GET = $GET;
        $this->POST = $POST;
        $this->server = $server;
        $this->headers = $this->getHeaders();
        $this->cookies = $cookies;
        $this->files = $files;
        $this->body = isset($body) ? (string)$body : null;

        $this->getServerRequest();
    }

    /**
     * @return Request
     */
    public static function createFromGlobals()
    {
        return new static($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES, null);
    }

    /**
     * Gets the request body
     *
     * @return string
     */
    public function body()
    {
        // Only get it once
        if (null === $this->body) {
            $this->body = @file_get_contents('php://input');
        }
        return $this->body;
    }

    /**
     * Get our headers from our server data collection
     *
     * PHP is weird... it puts all of the HTTP request
     * headers in the $_SERVER array. This handles that
     *
     * @return array
     */
    public function getHeaders()
    {
        // Define a headers array
        $headers = array();
        foreach ($this->server as $key => $value) {
            // Does our server attribute have our header prefix?
            if (self::hasPrefix($key, self::$http_header_prefix)) {
                // Add our server attribute to our header array
                $headers[substr($key, strlen(self::$http_header_prefix))] = $value;
            } elseif (in_array($key, self::$http_nonprefixed_headers)) {
                // Add our server attribute to our header array
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Returns true if domain is secure
     *
     * @return bool
     */
    public function isSecure()
    {
        return ($this->server['HTTPS'] == true);
    }

    /**
     * Returns the requester's IP
     *
     * @return mixed
     */
    public function ip()
    {
        return isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * Returns the User Agent string
     *
     * @return mixed
     */
    public function userAgent()
    {
        return isset($this->server['USER_AGENT']) ? $this->server['USER_AGENT'] : false;
    }

    /**
     * Builds the $_GET, $_POST, $_SERVER and $_COOKIE object properties
     */
    private function getServerRequest()
    {
        //get httpMethod
        if (isset($this->server['REQUEST_METHOD'])) {
            $this->setHttpMethod($this->server['REQUEST_METHOD']);
        } elseif (isset($this->server['HTTP_X_HTTP_METHOD'])) {
            $this->setHttpMethod($this->server['HTTP_X_HTTP_METHOD']);
        } else {
            $this->setHttpMethod("GET");
        }

        if (filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') === 'xmlhttprequest') {
            $this->isAjax = true;
        }

        $this->checkInput();

        //path
        $rawPath = isset($this->GET['page']) ? $this->GET['page'] : '';
        str_replace($this->illegal, '', $rawPath);
        $this->path = isset($rawPath) && $rawPath != 'index.php' ? '/' . $rawPath : '';

    }


    /**
     * @deprecated
     */
    public function sanitizeGlobals()
    {
        $this->GET = $this->inputSanitize($_GET);

        $this->POST = $this->inputSanitize($_POST);

        $this->server = $_SERVER;

        foreach ($_COOKIE as $key => $value) {
            $this->cookies[$key] = htmlentities(
                filter_var(trim($value), FILTER_SANITIZE_STRING),
                ENT_COMPAT,
                'UTF-8',
                false
            );
        }
    }

    /**
     * @param array $array
     * @return string
     */
    public function sanitizeArray(array $array)
    {
        $sanitized = [];
        foreach ($array as $key => $val) {

            if (is_array($val)) {
                $sanitized[$key] = $this->sanitizeArray($val);
                continue;
            }

            $sanitized[$key] = $this->sanitize($key, $val);
        }

        return $sanitized;
    }

    /**
     * @param $type
     * @param $value
     * @return string
     */
    public function sanitize($type, $value)
    {
        switch ($type) {
            case 'email':
                return htmlentities(
                    filter_var(trim($value), FILTER_SANITIZE_EMAIL),
                    ENT_COMPAT,
                    'UTF-8',
                    false
                );
                break;

            case 'phone':
            case 'mobile':
                return htmlentities(
                    filter_var(trim($value), FILTER_SANITIZE_NUMBER_INT),
                    ENT_COMPAT,
                    'UTF-8',
                    false
                );
                break;

            case 'data':
                return htmlentities(filter_var(trim($value), FILTER_UNSAFE_RAW));
                break;

            default:
                return htmlentities(
                    filter_var(trim($value), FILTER_SANITIZE_STRING),
                    ENT_COMPAT,
                    'UTF-8',
                    false
                );
                break;
        }
    }

    /**
     * Sanitize inputs
     *
     * @param $data
     * @return array
     */
    public function inputSanitize($data)
    {
        $sanitizedData = [];
        foreach ($data as $key => $val) {

            if (is_array($val)) {
                $sanitizedData[$key] = $this->sanitizeArray($val);
                continue;
            }

            $sanitizedData[$key] = $this->sanitize($key, $val);
        }

        return $sanitizedData;
    }

    /**
     * check for input (support for angular POST)
     */
    public function checkInput()
    {
        $postData = file_get_contents("php://input");
        if (!empty($postData) && is_array($postData)) {
            $postData = $this->inputSanitize($postData);
            $this->POST['json'] = json_decode($postData);
        } elseif (!empty($postData) && is_string($postData)) {
            if ($this->httpMethod === 'put') {
                parse_str($postData, $this->POST['put']);
                $this->POST['put'] = $this->inputSanitize($this->POST['put']);
            }
        }
    }

    public function isAjax()
    {
        return $this->isAjax;
    }

    /**
     * Returns an array of server info
     *
     * @return array
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Returns an array of Cookies set
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Returns the httpMethod used for the current request
     *
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * Sets the http Method
     *
     * @param $httpMethod
     * @return $this
     */
    public function setHttpMethod($httpMethod)
    {
        if (!in_array(strtoupper($httpMethod), static::$validHttpMethods)) {
            throw new \InvalidArgumentException("Unknown method: {$httpMethod} given.");
        }

        $this->httpMethod = strtoupper($httpMethod);
        return $this;
    }

    /**
     * Returns the url path/query string
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Find variable value in global $_GET
     *
     * @param null|string $variable
     * @return string|bool
     */
    public static function _GET($variable = null)
    {
        if (is_null($variable)) {
            return $_GET;
        }

        return isset($_GET[$variable]) ? $_GET[$variable] : false;
    }

    /**
     * Find variable value in global $_POST
     *
     * @param null|string $variable
     * @return string|bool
     */
    public static function _POST($variable = null)
    {
        if (is_null($variable)) {
            return $_POST;
        }

        return isset($_POST[$variable]) ? $_POST[$variable] : false;
    }

    /**
     * Quickly check if a string has a passed prefix
     *
     * @param string $string The string to check
     * @param string $prefix The prefix to test
     * @return boolean
     */
    public static function hasPrefix($string, $prefix)
    {
        if (strpos($string, $prefix) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Sleep magic method
     *
     * @return array
     */
    public function __sleep()
    {
        return ['path', 'isAjax', 'illegal', 'devMode', 'config'];
    }

    /**
     * Magic wakup method. Initializes on unserialize
     */
    public function __wakeup()
    {
        $this->getServerRequest();
    }

}
