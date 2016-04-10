<?php


namespace Http\Tests\Request;

use Http\Request\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $request Request
     */
    public $request;

    public function setUp()
    {
        $this->request = Request::createFromGlobals();
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('\Core\Request\Request', $this->request);
    }

    public function testGetIp()
    {
        $this->assertSame('127.0.0.1', $this->request->ip());
    }

    public function testGetSetPath()
    {
        $this->assertSame('/', $this->request->getPath());
        $this->request->setPath('/testing/path');
        $this->assertEquals('/testing/path', $this->request->getPath());
    }



}
