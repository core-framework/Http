<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 19/03/16
 * Time: 3:12 PM
 */

namespace Http\Contracts;

use Http\Router\Router;

interface Middleware
{
    public function run(Router $router, \Closure $next);
}