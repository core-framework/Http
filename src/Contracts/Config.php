<?php

namespace Http\Contracts;

/**
 * Parameters interface.
 */
interface Config
{
    /**
     * Get parameter
     *
     * @param $key
     * @param bool|mixed $default
     * @return mixed
     */
    public static function get($key = null, $default = false);
    
}
