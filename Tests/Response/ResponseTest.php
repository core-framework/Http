<?php

namespace Http\Tests\Response;

use Http\Response\Response;
use org\bovigo\vfs\vfsStream;

/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 02/11/15
 * Time: 11:39 PM
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $root = vfsStream::setup('test', 0777);
        vfsStream::newFile('test.txt', 0777)->at($root)->setContent('Hi, this is a test file');
    }

    public function tearDown()
    {

    }

    /**
     * @covers \Core\Response\Response::__construct
     * @covers \Core\Response\BaseResponse::__construct
     * @covers \Core\Response\BaseResponse::setContent
     * @covers \Core\Response\BaseResponse::setStatusCode
     */
    public function testConstructor()
    {
        $response = new Response();
        $this->assertInstanceOf('\\Core\\Response\\Response', $response);
    }

    /**
     * @covers \Core\Response\Response::setFile
     * @covers \Core\Response\Response::getFile
     */
    public function testSetFileWithFile()
    {
        $file = vfsStream::url('test/test.txt');
        $response = new Response();
        $response->setFile($file);
        $this->assertFileEquals($file, $response->getFile());
    }

    /**
     * @covers \Core\Response\Response::setFile
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given File should be a valid and readable file.
     */
    public function testSetFileWithNonFile()
    {
        $response = new Response();
        $response->setFile('asdasd');
    }

    /**
     * @covers \Core\Response\Response::setAccessControl
     * @covers \Core\Response\Response::getHeader
     */
    public function testSetAccessControlDefault()
    {
        $response = new Response();
        $response->setAccessControl();
        $this->assertSame("*", $response->getHeader('Access-Control-Allow-Origin'));
    }

    /**
     * @covers \Core\Response\Response::setAccessControl
     * @covers \Core\Response\Response::getHeader
     */
    public function testSetAccessControlWithValue()
    {
        $response = new Response();
        $response->setAccessControl('www.testdomain.com');
        $this->assertSame("www.testdomain.com", $response->getHeader('Access-Control-Allow-Origin'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptPatch
     * @covers \Core\Response\Response::getHeader
     */
    public function testSetAcceptPatchDefault()
    {
        $response = new Response();
        $response->setAcceptPatch();
        $this->assertSame("text/html;charset=utf-8", $response->getHeader('Accept-Patch'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptPatch
     * @covers \Core\Response\Response::getHeader
     */
    public function testSetAcceptPathWithValue()
    {
        $response = new Response();
        $response->setAcceptPatch("text/css", "ISO-8859-1");
        $this->assertSame("text/css;charset=ISO-8859-1", $response->getHeader('Accept-Patch'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptRange
     */
    public function testSetAcceptRangeWithDefault()
    {
        $response = new Response();
        $response->setAcceptRange();
        $this->assertSame("none", $response->getHeader('Accept-Ranges'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptRange
     */
    public function testSetAcceptRangeWithBytesRange()
    {
        $response = new Response();
        $response->setAcceptRange("1000-20000");
        $this->assertSame("bytes 1000-20000", $response->getHeader('Accept-Ranges'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptRange
     */
    public function testSetAcceptRangeWithEmptyString()
    {
        $response = new Response();
        $response->setAcceptRange('');
        $this->assertSame("bytes", $response->getHeader('Accept-Ranges'));
    }

    /**
     * @covers \Core\Response\Response::setAcceptRange
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Incorrect value provided for Accept-Ranges header. Range must be string with '-' character or an empty string.
     */
    public function testSetAcceptRangeThrowsException()
    {
        $response = new Response();
        $response->setAcceptRange(0);
    }

    /**
     * @covers \Core\Response\Response::setAge
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSetAgeWithDefault()
    {
        $response = new Response();
        $response->setAge();
    }

    /**
     * @covers \Core\Response\Response::setAge
     * @expectedException \InvalidArgumentException
     */
    public function testSetAgeWithStringArgument()
    {
        $response = new Response();
        $response->setAge('222');
    }

    /**
     * @covers \Core\Response\Response::setAge
     */
    public function testSetAgeWithIntValue()
    {
        $response = new Response();
        $response->setAge(100);
        $this->assertSame(100, $response->getHeader('Age'));
    }

    /**
     * @covers \Core\Response\Response::setAllow
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetAllowWithDefault()
    {
        $response = new Response();
        $response->setAllow();
    }

    /**
     * @covers \Core\Response\Response::setAllow
     */
    public function testSetAllowWithArray()
    {
        $response = new Response();
        $methods = ['get', 'post'];
        $response->setAllow($methods);
        $this->assertSame('GET, POST', $response->getHeader('Allow'));
    }

    /**
     * @covers \Core\Response\Response::setAllow
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetAllowWithNonArray()
    {
        (new Response())->setAllow('POST');
    }

    /**
     * @covers \Core\Response\Response::setCacheControl
     */
    public function testSetCacheControlWithDefault()
    {
        $response = new Response();
        $response->setCacheControl();
        $this->assertSame("max-age=3600, public", $response->getHeader('Cache-Control'));
    }

    /**
     * @covers \Core\Response\Response::setCacheControl
     */
    public function testSetCacheControlWithValue()
    {
        $response = new Response();
        $response->setCacheControl("public", 4000);
        $this->assertSame("max-age=4000, public", $response->getHeader('Cache-Control'));
    }

    /**
     * @covers \Core\Response\Response::setCacheControl
     * @expectedException \InvalidArgumentException
     */
    public function testSetCacheControlWithNonInteger()
    {
        (new Response())->setCacheControl("public", '3000');
    }

    /**
     * @covers \Core\Response\Response::__construct
     * @covers \Core\Response\Response::setDefaults
     * @covers \Core\Response\Response::setConnection
     */
    public function testSetConnectionHappensByDefault()
    {
        $value = (new Response())->getHeader('Connection');
        $this->assertSame('close', $value);
    }

    /**
     * @covers \Core\Response\Response::setConnection
     */
    public function testSetConnectionWithValue()
    {
        $response = new Response();
        $response->setConnection("close");
        $this->assertSame("close", $response->getHeader('Connection'));
    }

    /**
     * @covers \Core\Response\Response::setConnection
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Connection only supports 'keep-alive' & 'close' values.
     */
    public function testSetConnectionThrowsExceptionOnInvalidArgument()
    {
        $response = new Response();
        $response->setConnection("Some-Illegal-Value");
    }

    /**
     * @covers \Core\Response\Response::setConnection
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Connection only supports 'keep-alive' & 'close' values.
     */
    public function testSetConnectionThrowsExceptionOnNonString()
    {
        $response = new Response();
        $response->setConnection(123123);
    }

    /**
     * @covers \Core\Response\Response::setContentDisposition
     */
    public function testSetContentDispositionWithDefault()
    {
        $response = new Response();
        $response->setContentDisposition();
        $this->assertSame("inline", $response->getHeader('Content-Disposition'));
    }

    /**
     * @covers \Core\Response\Response::setContentDisposition
     */
    public function testSetContentDispositionWithValues()
    {
        $response = new Response();
        $response->setContentDisposition("attachment", ["filename" => "somefile.txt"]);
        $this->assertSame("attachment; filename=\"somefile.txt\"", $response->getHeader('Content-Disposition'));
    }

    /**
     * @covers \Core\Response\Response::setContentDisposition
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Given Content Disposition Type is not valid! Valid Types are - /
     */
    public function testSetContentDispositionWithInValidType()
    {
        (new Response())->setContentDisposition("someInvalidType");
    }

    /**
     * @covers \Core\Response\Response::setContentDisposition
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Given Parameter is not valid! Valid Parameters are - /
     */
    public function testSetContentDispositionWithInValidParam()
    {
        (new Response())->setContentDisposition("attachment", ['someIllegalParam' => "andValue"]);
    }

    /**
     * @covers \Core\Response\Response::setContentEncoding
     */
    public function testSetContentEncodingWithDefault()
    {
        $response = new Response();
        // Mimic real work
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $response->setContentEncoding();
        $this->assertSame('gzip', $response->getHeader('Content-Encoding'));
    }

    /**
     * @covers \Core\Response\Response::setContentEncoding
     */
    public function testSetContentEncodingWithValue()
    {
        $response = new Response();
        $response->setContentEncoding('deflate');
        $this->assertSame('deflate', $response->getHeader('Content-Encoding'));
    }

    /**
     * @covers \Core\Response\Response::setContentLanguage
     */
    public function testSetContentLanguageWithDefault()
    {
        $response = new Response();
        $response->setContentLanguage();
        $this->assertSame('en', $response->getHeader('Content-Language'));
    }

    /**
     * @covers \Core\Response\Response::setContentLanguage
     */
    public function testSetContentLanguageWithValue()
    {
        $response = new Response();
        $response->setContentLanguage('mi');
        $this->assertSame('mi', $response->getHeader('Content-Language'));
    }

    /**
     * @covers \Core\Response\Response::setContentLength
     */
    public function testSetContentLengthWithDefault()
    {
        $file = vfsStream::url('test/test.txt');
        $response = new Response();
        $response->setFile($file);
        $response->setContentLength();
        $this->assertSame(filesize($file), $response->getHeader('Content-Length'));
    }

    /**
     * @covers \Core\Response\Response::setContentLength
     */
    public function testSetContentLengthWithValue()
    {
        $response = new Response();
        $response->setContentLength(123);
        $this->assertSame(123, $response->getHeader('Content-Length'));
    }

    /**
     * @covers \Core\Response\Response::setContentLength
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Length must be a valid Integer,/
     */
    public function testSetContentLengthWithString()
    {
        $response = new Response();
        $response->setContentLength('123');
    }


    /**
     * @covers \Core\Response\Response::setContentLocation
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetContentLocationWithNoValue()
    {
        $response = new Response();
        $response->setContentLocation();
    }

    /**
     * @covers \Core\Response\Response::setContentLocation
     */
    public function testSetContentLocationWithValue()
    {
        $response = new Response();
        $response->setContentLocation('/test?something=something');
        $this->assertSame('/test?something=something', $response->getHeader('Content-Location'));
    }

    /**
     * @covers \Core\Response\Response::setContentRange
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetContentRangeWithNoValue()
    {
        $response = new Response();
        $response->setContentRange();
    }

    /**
     * @covers \Core\Response\Response::setContentRange
     */
    public function testSetContentRangeWithValue()
    {
        $response = new Response();
        $response->setContentRange(0,100,1000);
        $this->assertSame('bytes 0-100/1000', $response->getHeader('Content-Range'));
    }

    /**
     * @covers \Core\Response\Response::setContentRange
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument passed must be of type Integer. One or more non-integers arguments were given.
     */
    public function testSetContentRangeWithString()
    {
        $response = new Response();
        $response->setContentRange('x','y','test');
    }

    /**
     * @covers \Core\Response\Response::setContentType
     */
    public function testSetContentTypeDefault()
    {
        $response = new Response();
        $response->setContentType();
        $this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
    }

    /**
     * @covers \Core\Response\Response::setContentType
     */
    public function testSetContentTypeWithValue()
    {
        $response = new Response();
        $response->setContentType("application/json");
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    /**
     * @covers \Core\Response\Response::setContentType
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given arguments cannot be empty.
     */
    public function testSetContentTypeWithEmpty()
    {
        $response = new Response();
        $response->setContentType("");
    }

    /**
     * @covers \Core\Response\Response::setContentType
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given arguments must of type String.
     */
    public function testSetContentTypeWithNonStrings()
    {
        $response = new Response();
        $response->setContentType(00123);
    }

    /**
     * @covers \Core\Response\Response::setRedirect
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetRedirectWithDefault()
    {
        $response = new Response();
        $response->setRedirect();
    }

    /**
     * @covers \Core\Response\Response::setRedirect
     */
    public function testSetRedirectWithValues()
    {
        $response = new Response();
        $response->setRedirect('/some/url', 301);
        $this->assertSame('/some/url', $response->getHeader('Location'));
    }

    /**
     * @covers \Core\Response\Response::redirect
     * @runInSeparateProcess
     */
    public function testRedirectWithValue()
    {
        $response = new Response();
        $response->redirect("/redirected");
        $headersList = xdebug_get_headers();
        $statusCode = http_response_code();

        $this->assertSame(302, $statusCode);
        $this->assertContains('Location: /redirected', $headersList);
    }

    /**
     * @covers \Core\Response\Response::redirect
     * @runInSeparateProcess
     */
    public function testRedirectWithValues()
    {
        $response = new Response();
        $response->redirect("/some/location", 301);
        $headersList = xdebug_get_headers();
        $statusCode = http_response_code();

        $this->assertSame(301, $statusCode);
        $this->assertContains('Location: /some/location', $headersList);
    }

    /**
     * @covers \Core\Response\Response::send
     * @runInSeparateProcess
     */
    public function testSendWithDefault()
    {
        $response = new Response();
        $response->send();
        $headersList = xdebug_get_headers();
        $statusCode = http_response_code();

        $this->assertEquals(200, $statusCode);
        $this->assertContains('Connection: close', $headersList);
        $this->assertEmpty($response->getContent());
    }

    /**
     * @covers \Core\Response\Response::send
     * @runInSeparateProcess
     */
    public function testSendWithContentAsString()
    {
        $str = "Hi, this is test content";
        $response = new Response($str);
        $response->send();
        $headersList = xdebug_get_headers();
        $statusCode = http_response_code();

        $this->assertEquals(200, $statusCode);
        $this->assertContains('Connection: close', $headersList);
        $this->expectOutputString($str);
    }

    /**
     * @covers \Core\Response\Response::send
     * @runInSeparateProcess
     */
    public function testSendWithContentAsArray()
    {
        $arr = ['test1' => 'val1', 'test2' => 'val2'];
        $response = new Response($arr);
        $response->send();
        $headersList = xdebug_get_headers();
        $statusCode = http_response_code();

        $this->assertEquals(200, $statusCode);
        $this->assertContains('Connection: close', $headersList);
        $this->assertContains('Content-Type: application/json', $headersList);
        $this->assertJson(json_encode($arr), $response->getContent());
    }
}
