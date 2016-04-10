<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 21/03/16
 * Time: 1:08 AM
 */

namespace Http\Tests\Router;


use Http\Request\Request;
use Http\Response\Response;
use Http\Router\Router;
use Http\Route\Route;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router $router
     */
    public $router;

    public function setUp()
    {
        $this->router = new Router();
        $this->setRoutes();
    }

    public function tearDown()
    {

    }

    // ----- Helpers -----

    public function setRoutes()
    {
        Router::get('/test/{id:default=1}', 'testController@testId');
        Router::get('/test/{fname}/{id:num:default=1}', 'testController@testName');
        //grouping
        Router::group(['prefix' => '/group'], function() {
            Router::get('/groupTest/{id:default=1}', 'testController@testId');
        });
    }

    /**
     * @return array
     */
    public function pathProvider()
    {
        return [
            ['/test/1', ['id'], ['id' => '1'], 'testController@testId'],
            ['/test/', ['id'], ['id' => '1'], 'testController@testId'],
            ['/test/2', ['id'], ['id' => '2'], 'testController@testId'],
            ['/test/sam/3', ['fname','id'], ['fname' => 'sam', 'id' => 3], 'testController@testName'],
            ['/test/sham/4', ['fname','id'], ['fname' => 'sham', 'id' => 4], 'testController@testName'],
            ['/group/groupTest/1', ['id'], ['id' => '1'], 'testController@testId'],
        ];
    }

    public function getRequestMock($path)
    {
        $request = $this->getMockBuilder('\Core\Request\Request')
            ->setConstructorArgs(array($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES))
            ->setMethods(array('getHttpMethod', 'getPath'))
            ->getMock();

        $request->expects($this->once())
            ->method('getHttpMethod')
            ->will($this->returnValue('GET'));

        $request->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        return $request;
    }

    public function getRouterMock($controller = null)
    {
        $router = $this->getMockBuilder('\Core\Router\Router')->setMethods(array('makeController'))->getMock();
        $router->expects($this->any())
            ->method("makeController")
            ->will($this->returnValue($controller));

        return $router;
    }

    public function getControllerStub($class, $method, $values)
    {
        $controller = $this->getMockBuilder($class)->setMethods(array($method))->getMock();
        $controller->expects($this->any())
            ->method($method)
            ->with($this->equalTo($values))
            ->will($this->returnCallback(array($this, 'responseCallback')));

        return $controller;
    }

    public function getClass($action)
    {
        $class = explode('@', $action)[0];
        return 'app\\Controllers\\' . $class;
    }

    public function getMethod($action)
    {
        return explode('@', $action)[1];
    }

    public function getResponseContent($valuesString)
    {
        return "<html><h1>Hello World - {$valuesString} </h1></html>";
    }

    public function responseCallback($payload)
    {
        $valuesString = serialize($payload);
        $response = new Response("<html><h1>Hello World - {$valuesString} </h1></html>", 200);
        return $response;
    }

    // ----- TESTS ------

    /**
     * Simple Router Test
     */
    public function testRouter()
    {
        $this->assertInstanceOf('\Core\Router\Router', $this->router);
    }

    /**
     * @param $path
     * @param $params
     * @param $values
     * @param $action
     * @dataProvider pathProvider
     */
    public function testRouterRouting($path, $params, $values, $action)
    {
        $request = $this->getRequestMock($path);

        /** @var Route $route */
        $route = $this->router->parse($request);
        $this->assertInstanceOf('\Core\Router\Route', $route);
        $this->assertSame($action, $route->getAction());
        $this->assertSame($params, $route->getParameterNames());
        $this->assertSame($values, $route->getParameterValues());
    }

    /**
     * @param $path
     * @param $params
     * @param $values
     * @param $action
     * @dataProvider pathProvider
     */
    public function testRouterHandling($path, $params, $values, $action)
    {
        $valuesString = serialize($values);
        $method = $this->getMethod($action);
        $class = $this->getClass($action);
        
        $controller = $this->getControllerStub($class, $method, $values);
        $router = $this->getRouterMock($controller);
        $request = $this->getRequestMock($path);

        $this->setRoutes();

        /** @var Response $response */
        $response = $router->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($this->getResponseContent($valuesString), $response->getContent());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRouterAlwaysReturnsResponse()
    {
        $router = $this->getRouterMock();
        $request = $this->getRequestMock('/test/2');
        $this->setRoutes();

        $response = $router->handle($request);
        $this->assertInstanceOf('\\Core\\Response\\Response', $response);
        $this->assertEquals(604, $response->getStatusCode());

        $response = $router->handle($request = $this->getRequestMock('/asdasd/2'));
        $this->assertInstanceOf('\\Core\\Response\\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
