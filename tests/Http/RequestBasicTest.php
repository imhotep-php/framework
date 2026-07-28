<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\Request;

class RequestBasicTest extends RequestTest
{
    public function testCreateFromGlobals()
    {
        $_GET = ['foo' => 'bar'];
        $_POST = ['baz' => 'qux'];
        $_COOKIE = ['session' => 'abc123'];
        $_FILES = ['file' => $this->createMockFile()];
        $_SERVER = ['SERVER_NAME' => 'test.com'];

        $request = Request::createFromGlobals();

        $this->assertEquals('GET', $request->method());
        $this->assertEquals($_GET, $request->query());
        $this->assertEquals($_POST, $request->post());
        $this->assertEquals($_COOKIE, $request->cookies());
        $this->assertEquals([], $request->json());
        $this->assertTrue($request->hasFile('file'));
        $this->assertEquals($_SERVER, $request->server());
    }

    public function testCreateWithUriAndMethod()
    {
        $request = Request::create('https://example.com:8080/path?foo=bar', 'POST', ['key' => 'value']);

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('example.com', $request->host());
        $this->assertEquals('example.com:8080', $request->host(true));
        $this->assertEquals('/path', $request->path());
        $this->assertEquals('foo=bar', $request->queryString());
        $this->assertEquals(['key' => 'value'], $request->post());
        $this->assertEquals([], $request->json());
        $this->assertTrue($request->secure());
    }

    public function testCreateWithBasicAuth()
    {
        $request = Request::create('https://user:pass@example.com/test');

        $this->assertEquals('user', $request->getUser());
        $this->assertEquals('pass', $request->getPassword());
    }

    public function testConstructorInitializesAllBags()
    {
        $query = ['q' => 'search'];
        $post = ['title' => 'Test'];
        $cookies = ['session' => '123'];
        $json = ['name' => 'John', 'age' => 30];
        $files = ['document' => $this->createMockFile()];
        $server = ['SERVER_PORT' => 443];

        $request = $this->createRequest($query, $post, $cookies, $files, $server, json_encode($json));

        $this->assertEquals($query, $request->query());
        $this->assertEquals($post, $request->post());
        $this->assertEquals($cookies, $request->cookies());
        $this->assertEquals($json, $request->json());
        $this->assertTrue($request->hasFile('document'));
        $this->assertEquals($server, $request->server());
    }

    public function testCreateWithInvalidJson()
    {
        $request = Request::create('https://example.com/', content: 'Invalid json');

        $this->assertEquals([], $request->json());
    }

    public function testMethod()
    {
        $request = $this->createRequest(server: ['REQUEST_METHOD' => 'POST']);
        $this->assertEquals('POST', $request->method());

        $request = $this->createRequest(server: ['REQUEST_METHOD' => 'GET']);
        $this->assertEquals('GET', $request->method());

        // Default to GET
        $request = $this->createRequest();
        $this->assertEquals('GET', $request->method());
    }

    public function testIsMethod()
    {
        $request = $this->createRequest(server: ['REQUEST_METHOD' => 'POST']);

        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod(['GET', 'POST']));
        $this->assertTrue($request->isMethod('GET', 'POST'));
        $this->assertFalse($request->isMethod('GET'));
        $this->assertFalse($request->isMethod(['GET', 'PUT']));
    }

    public function testSecure()
    {
        // HTTPS on
        $request = $this->createRequest(server: ['HTTPS' => 'on']);
        $this->assertTrue($request->secure());

        // HTTPS = 1
        $request = $this->createRequest(server: ['HTTPS' => '1']);
        $this->assertTrue($request->secure());

        // Port 443
        $request = $this->createRequest(server: ['SERVER_PORT' => 443]);
        $this->assertTrue($request->secure());

        // X-Forwarded-Proto
        $request = $this->createRequest(server: ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $this->assertTrue($request->secure());

        // X-Forwarded-SSL
        $request = $this->createRequest(server: ['HTTP_X_FORWARDED_SSL' => 'on']);
        $this->assertTrue($request->secure());

        // Not secure
        $request = $this->createRequest();
        $this->assertFalse($request->secure());
    }

    public function testScheme()
    {
        $request = $this->createRequest(server: ['HTTPS' => 'on']);
        $this->assertEquals('https', $request->scheme());

        $request = $this->createRequest();
        $this->assertEquals('http', $request->scheme());
    }

    public function testHost()
    {
        // With HOST header
        $request = $this->createRequest(server: [
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_NAME' => 'wrong.com',
            'SERVER_PORT' => 80
        ]);
        $this->assertEquals('example.com', $request->host());
        $this->assertEquals('example.com:8080', $request->host(true));

        // Without HOST header, use SERVER_NAME
        $request = $this->createRequest(server: [
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => 443
        ]);
        $this->assertEquals('example.com', $request->host());
        $this->assertEquals('example.com', $request->host(true));

        // Without HOST header, use SERVER_ADDR
        $request = $this->createRequest(server: [
            'SERVER_ADDR' => '192.168.1.1',
            'SERVER_PORT' => 80
        ]);
        $this->assertEquals('192.168.1.1', $request->host());
        $this->assertEquals('192.168.1.1', $request->host(true));
    }

    public function testPort()
    {
        // From HOST header
        $request = $this->createRequest(server: ['HTTP_HOST' => 'example.com:8080']);
        $this->assertEquals(8080, $request->port());

        // From SERVER_PORT
        $request = $this->createRequest(server: ['SERVER_PORT' => '9000']);
        $this->assertEquals(9000, $request->port());

        // Default HTTP port
        $request = $this->createRequest();
        $this->assertEquals(80, $request->port());

        // Default HTTPS port
        $request = $this->createRequest(server: ['HTTPS' => 'on']);
        $this->assertEquals(443, $request->port());
    }

    public function testPath()
    {
        $request = $this->createRequest(server: ['REQUEST_URI' => '/path/to/resource?foo=bar']);
        $this->assertEquals('/path/to/resource', $request->path());

        // Remove trailing slash
        $request = $this->createRequest(server: ['REQUEST_URI' => '/path/']);
        $this->assertEquals('/path', $request->path());

        // Keep single slash
        $request = $this->createRequest(server: ['REQUEST_URI' => '/']);
        $this->assertEquals('/', $request->path());

        // Without query string
        $request = $this->createRequest(server: ['REQUEST_URI' => '/path']);
        $this->assertEquals('/path', $request->path());

        // Empty request URI
        $request = $this->createRequest(server: ['REQUEST_URI' => '']);
        $this->assertEquals('', $request->path());
    }

    public function testQueryString()
    {
        $request = $this->createRequest(server: ['QUERY_STRING' => 'foo=bar&baz=qux']);
        $this->assertEquals('foo=bar&baz=qux', $request->queryString());

        $request = $this->createRequest();
        $this->assertEquals('', $request->queryString());
    }

    public function testRoot()
    {
        $request = $this->createRequest(server: [
            'HTTP_HOST' => 'example.com:8080',
            'HTTPS' => 'on'
        ]);
        $this->assertEquals('https://example.com:8080', $request->root());

        $request = $this->createRequest(server: [
            'HTTP_HOST' => 'example.com'
        ]);
        $this->assertEquals('http://example.com', $request->root());
    }

    public function testUri()
    {
        $request = $this->createRequest(server: ['REQUEST_URI' => '/path?foo=bar']);
        $this->assertEquals('/path?foo=bar', $request->uri());

        $request = $this->createRequest();
        $this->assertEquals('', $request->uri());
    }

    public function testUrl()
    {
        $request = $this->createRequest(server: [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path?existing=param'
        ]);

        $this->assertEquals('http://example.com/path', $request->url());
        $this->assertEquals('http://example.com/path?new=param', $request->url(['new' => 'param']));
        $this->assertEquals('http://example.com/path?new=param&existing=override',
            $request->url(['new' => 'param', 'existing' => 'override']));
    }

    public function testFullUrl()
    {
        $request = $this->createRequest(['page' => 1, 'sort' => 'name'], server: [
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/users?page=1&sort=name'
        ]);

        $this->assertEquals('http://example.com/users?page=1&sort=name', $request->fullUrl());
        $this->assertEquals('http://example.com/users?page=2&sort=name', $request->fullUrl(['page' => 2]));
        $this->assertEquals('http://example.com/users?page=1', $request->fullUrl([], ['sort']));
        $this->assertEquals('http://example.com/users?sort=name', $request->fullUrl([], ['page']));
        $this->assertEquals('http://example.com/users?sort=image', $request->fullUrl(['sort' => 'image'], ['page']));
    }

}