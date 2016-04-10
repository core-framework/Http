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

namespace Http\Response;

use Http\Contracts\Cacheable;
use Http\Contracts\Response as ResponseInterface;
use Http\Contracts\View;

class Response extends BaseResponse implements ResponseInterface, Cacheable
{
    protected $file;

    public $do_gzip_compression = false;

    public function __construct($content = null, $statusCode = 200, array $headers = [])
    {
        parent::__construct($content, $statusCode, $headers);
        $this->setDefaults();
    }

    /**
     * set defaults
     */
    protected function setDefaults()
    {
        $this->setConnection();
    }

    /**
     * Set file
     *
     * @param $file
     */
    public function setFile($file)
    {
        if (is_readable($file) === false) {
            throw new \InvalidArgumentException("Given File should be a valid and readable file.");
        }

        $this->file = $file;
    }

    /**
     * Return file if set
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set Access Control header
     *
     * @param $domain
     */
    public function setAccessControl($domain = "*")
    {
        $this->addHeader('Access-Control-Allow-Origin', $domain);
    }

    /**
     * Set Accept-Patch header
     *
     * @param string $contentType
     * @param string $charset
     */
    public function setAcceptPatch($contentType = "text/html", $charset = "utf-8")
    {
        $this->addHeader("Accept-Patch", "{$contentType};charset={$charset}");
    }

    /**
     * Set Accept Range header
     *
     * @param null $range
     * @return $this
     */
    public function setAcceptRange($range = null)
    {
        if (is_null($range)) {
            $this->addHeader("Accept-Ranges", "none");
        } elseif (strpos($range, "-") !== false) {
            $this->addHeader("Accept-Ranges", "bytes " . $range);
        } elseif ($range === "") {
            $this->addHeader("Accept-Ranges", "bytes");
        } else {
            throw new \InvalidArgumentException("Incorrect value provided for Accept-Ranges header. Range must be string with '-' character or an empty string.");
        }

        return $this;
    }

    /**
     * Set proxy-cache Age header
     *
     * @param $seconds
     * @return $this
     */
    public function setAge($seconds)
    {
        if(!is_int($seconds)) {
            throw new \InvalidArgumentException("Seconds must be of type integer, ". gettype($seconds) . " given.");
        }
        $this->addHeader('Age', $seconds);

        return $this;
    }

    /**
     * Valid actions for a specified resource. To be used for a 405 Method not allowed
     *
     * @param array $allowedMethods
     * @return $this
     */
    public function setAllow(array $allowedMethods)
    {
        $supported = ['GET','HEAD', 'CHECKOUT', 'SHOWMETHOD', 'PUT', 'DELETE', 'POST', 'LINK', 'UNLINK', 'CHECKIN', 'TEXTSEARCH', 'SPACEJUMP', 'SEARCH'];
        $str = '';
        foreach($allowedMethods as $method) {
            $methodStr = strtoupper($method);
            if (in_array($methodStr, $supported)) {
                $str .= $methodStr . ', ';
            }
        }
        $methods = rtrim($str, ", ");

        $this->addHeader('Allow', $methods);

        return $this;
    }

    /**
     * Tells all caching mechanisms from server to client whether they may cache this object. It is measured in seconds
     *
     * @param int $maxAge
     * @param string $type
     * @return $this
     */
    public function setCacheControl($type = "public", $maxAge = 3600)
    {
        if (!is_int($maxAge)) {
            throw new \InvalidArgumentException("Max Age Argument must be of type Integer, " . gettype($maxAge) . " given.");
        }
        $cacheControlStr = 'max-age='.$maxAge;
        if (isset($type)) {
            $cacheControlStr .= ", ".$type;
        }
        $this->addHeader('Cache-Control', $cacheControlStr);

        return $this;
    }

    /**
     * Control options for the current connection and list of hop-by-hop response fields
     *
     * @param string $type
     * @return $this
     */
    public function setConnection($type = "close")
    {
        $valid = ["keep-alive", "close"];
        if (in_array($type, $valid)) {
            $this->addHeader('Connection', $type);
        } else {
            throw new \InvalidArgumentException("Connection only supports 'keep-alive' & 'close' values.");
        }

        return $this;
    }

    /**
     * An opportunity to raise a "File Download" dialogue box for a known MIME type with binary format or suggest a filename for dynamic content. Quotes are necessary with special characters.
     *
     * @param string $type
     * @param array $params
     * @return $this
     */
    public function setContentDisposition($type = "inline", array $params = [])
    {
        $validTypes = ["inline", "attachment", "form-data", "signal", "alert", "icon", "render", "recipient-list-history", "session", "aib", "early-session", "recipient-list", "notification", "by-reference", "info-package", "recording-session"];
        $validParams = ["filename", "creation-date", "modification-date", "read-date", "size", "name", "voice", "handling"];

        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Given Content Disposition Type is not valid! Valid Types are - " . implode(',',$validTypes) . ".");
        }

        if (!isset($type) || empty($type)) {
            $valueStr = $type;
        } else {
            $valueStr = $type . ";";
            foreach ($params as $key => $val) {
                if (in_array($key, $validParams)) {
                    if ($key === 'filename') {
                        $this->file = $val;
                    }
                    $valueStr .= " ".$key."=\"".$val."\";";
                } else {
                    throw new \InvalidArgumentException("Given Parameter is not valid! Valid Parameters are - ". implode(',', $validParams) . ".");
                }
            }
            $valueStr = rtrim($valueStr, ";");
        }

        $this->addHeader('Content-Disposition', $valueStr);
    }

    /**
     * The type of encoding used on the data. See HTTP compression.
     *
     * @link https://en.wikipedia.org/wiki/HTTP_compression
     * @param $type
     * @return $this
     */
    public function setContentEncoding($type = null)
    {
        if (is_null($type) && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $type = "gzip";
        }

        $this->do_gzip_compression = true;

        $this->addHeader('Content-Encoding', $type);

        return $this;
    }

    /**
     * The natural language or languages of the intended audience for the enclosed content
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.12
     * @param string $language
     * @return $this
     */
    public function setContentLanguage($language = "en")
    {
        $this->addHeader('Content-Language', $language);

        return $this;
    }

    /**
     * The length of the response body in octets (8-bit bytes)
     *
     * @param null $length
     * @return $this
     */
    public function setContentLength($length = null)
    {
        if (is_null($length) && isset($this->file) && is_readable($this->file)) {
            $length = (int) filesize($this->file);
        } elseif (is_null($length)) {
            throw new \InvalidArgumentException("Length not given. The file must be set using the setFile method before calling this method without a parameter");
        }

        if (!is_int($length)) {
            throw new \InvalidArgumentException("Length must be a valid Integer, " . gettype($length) . " given.");
        }

        $this->addHeader('Content-Length', $length);

        return $this;
    }

    /**
     * The Content-Location entity-header field MAY be used to supply the resource Location for the entity enclosed in the message when that entity is accessible from a Location separate from the requested resource’s URI.
     *
     * @param $path
     * @return $this
     */
    public function setContentLocation($path)
    {
        $this->addHeader('Content-Location', $path);

        return $this;
    }

    /**
     * Where in a full body message this partial message belongs
     *
     * @param $start
     * @param $end
     * @param $total
     * @param string $unit
     * @return $this
     */
    public function setContentRange($start, $end, $total, $unit = "bytes")
    {
        if (!is_int($start) || !is_int($end) || !is_int($total))
        {
            throw new \InvalidArgumentException("Argument passed must be of type Integer. One or more non-integers arguments were given.");
        }

        $this->addHeader('Content-Range', "{$unit} {$start}-{$end}/{$total}");

        return $this;
    }


    /**
     * The MIME type of the content
     *
     * @param string $mimeType
     * @param string $charset
     * @return $this
     */
    public function setContentType($mimeType = "text/html", $charset = "utf-8")
    {
        if ($mimeType === "" || $charset === "") {
            throw new \InvalidArgumentException("Given arguments cannot be empty.");
        }

        if (!is_string($mimeType) || !is_string($charset)) {
            throw new \InvalidArgumentException("Given arguments must of type String.");
        }

        if (strstr($mimeType, "text") !== false) {
            $this->addHeader('Content-Type', "{$mimeType}; charset={$charset}");
        } else {
            $this->addHeader('Content-Type', $mimeType);
        }

        return $this;
    }

    /**
     * Use for lazy redirection with other additional header parameters
     *
     * @param $location
     * @param int $statusCode
     * @return $this
     */
    public function setRedirect($location, $statusCode = 302)
    {
        if (!is_int($statusCode)) {
            throw new \InvalidArgumentException("Status Code must be of type Integer.");
        }

        $this->statusCode = $statusCode;
        $this->addHeader('Location', $location);
        return $this;
    }

    /**
     * Use for immediate redirection
     *
     * @param $url
     * @param int $statusCode
     */
    public function redirect($url, $statusCode = 302)
    {
        if (!is_int($statusCode)) {
            throw new \InvalidArgumentException("Status Code must be of type Integer.");
        }

        header("Location: ".$url, true, $statusCode);
    }

    /**
     * @return bool
     * @throws \HttpEncodingException
     */
    public function send()
    {
        $this->gzipCheckAndInit();
        $this->sendHeaders();
        $this->sendOutput();
    }

    /**
     * Output headers
     *
     * @return bool
     */
    public function sendHeaders()
    {
        $headers = $this->headers;

        // headers already send
        if (headers_sent()) {
            return $this;
        }

        // Lazy redirect
        if (isset($headers['Location'])) {
            header("Location: ".$headers['Location'], true, $this->statusCode);
            return true;
        }

        // Lazy(/consolidated) setting of headers
        foreach($headers as $key => $val) {
            if (!headers_sent()) {
                if($key === "Status") {
                    //http_response_code($val);
                    $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "HTTP/1.x";
                    $codeMsg = Response::$statusTexts[$this->statusCode];
                    header("{$protocol} {$this->statusCode} {$codeMsg}");
                } else {
                    header("{$key}: {$val}", true, $this->statusCode);
                }
            }
        }

        return true;
    }

    /**
     * Initiate gzip ob_start handler if enabled
     *
     * @throws \HttpEncodingException
     */
    public function gzipCheckAndInit()
    {
        if ($this->do_gzip_compression === true && extension_loaded('zlib') === true) {
            ob_start('ob_gzhandler');
            ob_implicit_flush(0);
        } elseif ($this->do_gzip_compression === true && extension_loaded('zlib') === false) {
            throw new \HttpEncodingException("Response set to use gzip compression, but 'zlib' extension not loaded or not found.");
        }
    }

    /**
     * Renders output
     */
    public function sendOutput()
    {
        if ($this->useView === true && !$this->content instanceof View) {
            $this->content = $this->view->fetch();
        }

        echo $this->content;

        if ($this->do_gzip_compression === true) {
            $this->sendGzipCompressed();
        }
    }

    /**
     * gzip Output compression
     *
     * @return bool
     */
    private function sendGzipCompressed()
    {
        //
        // Borrowed from php.net!
        //
        $gzip_contents = ob_get_contents();
        ob_end_clean();

        $gzip_size = strlen($gzip_contents);
        $gzip_crc = crc32($gzip_contents);

        $gzip_contents = gzcompress($gzip_contents, 9);
        $gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);

        echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
        echo $gzip_contents;
        echo pack('V', $gzip_crc);
        echo pack('V', $gzip_size);

        return true;
    }

    /**
     * @param $content
     * @param int $statusCode
     * @return Response
     */
    public static function create($content, $statusCode = 200)
    {
        return new self($content, $statusCode);
    }
}