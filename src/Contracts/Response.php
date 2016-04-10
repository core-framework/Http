<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 16/11/15
 * Time: 10:54 AM
 */

namespace Http\Contracts;

interface Response
{
    /**
     * Set Response Content
     *
     * @param null $content
     * @supported array || string || serializable
     * @return void
     */
    public function setContent($content = null);

    /**
     * Check if content is set
     *
     * @return mixed
     */
    public function getIsContentSet();

    /**
     * Set status code for current response
     *
     * @param int $code
     * @return void
     */
    public function setStatusCode($code = 200);


    /**
     * Set view object associated with current response
     *
     * @param View $view
     * @return void
     */
    public function setView(View $view);

    /**
     * Add Header(s) for current response
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function addHeader($key, $value);

    /**
     * Get a previously set header
     *
     * @param $key
     * @return mixed
     */
    public function getHeader($key);

    /**
     * Remove previously set header
     *
     * @param $key
     * @return mixed
     */
    public function removeHeader($key);

    /**
     * Method to send computed response to Client (browser)
     *
     * @return mixed
     */
    public function send();

}