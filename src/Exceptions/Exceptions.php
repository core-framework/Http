<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 27/03/16
 * Time: 8:00 PM
 */

namespace Http\Exceptions;


class HttpException extends \RuntimeException {}
class PageNotFoundException extends HttpException {
    /**
     * PageNotFoundException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = "Page is Not Found", $code = 404, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
class ControllerNotFoundException extends HttpException 
{
    /**
     * ControllerNotFoundException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = "Controller Not Found", $code = 604, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
class ControllerMethodNotFoundException extends \BadMethodCallException
{
    /**
     * ControllerNotFoundException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = "Controller Method Not Found", $code = 605, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}