<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\Request;
use Imhotep\Http\UploadedFile;

class RequestClientInfoTest extends RequestTest
{
    public function testIp()
    {
        // Test X-Forwarded-For
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '192.168.1.100, 10.0.0.1, proxy1, proxy2'
        ]);
        $this->assertEquals('192.168.1.100', $request->ip()); // Should take first IP

        // Test Cloudflare
        $request = $this->createRequest([], [], [], [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1'
        ]);
        $this->assertEquals('203.0.113.1', $request->ip());

        // Test REMOTE_ADDR
        $request = $this->createRequest([], [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $this->assertEquals('127.0.0.1', $request->ip());

        // Test priority - X-Forwarded-For should take precedence
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '192.168.1.100',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $this->assertEquals('192.168.1.100', $request->ip());
    }

    public function testUserAgent()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser'
        ]);
        $this->assertEquals('Mozilla/5.0 Test Browser', $request->userAgent());

        $request = $this->createRequest();
        $this->assertEquals('', $request->userAgent());
    }

    public function testBearerToken()
    {
        // Valid bearer token
        $request = $this->createRequest([], [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'
        ]);
        $this->assertEquals('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $request->bearerToken());

        // With comma
        $request = $this->createRequest([], [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token123, something=else'
        ]);
        $this->assertEquals('token123', $request->bearerToken());

        // Case insensitive
        $request = $this->createRequest([], [], [], [], [
            'HTTP_AUTHORIZATION' => 'bearer token456'
        ]);
        $this->assertEquals('token456', $request->bearerToken());

        // No bearer token
        $request = $this->createRequest([], [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic dXNlcjpwYXNz'
        ]);
        $this->assertNull($request->bearerToken());

        $request = $this->createRequest();
        $this->assertNull($request->bearerToken());
    }

    public function testBasicAuth()
    {
        $request = $this->createRequest([], [], [], [], [
            'PHP_AUTH_USER' => 'john',
            'PHP_AUTH_PW' => 'secret'
        ]);

        $this->assertEquals('john', $request->getUser());
        $this->assertEquals('secret', $request->getPassword());

        $request = $this->createRequest();
        $this->assertNull($request->getUser());
        $this->assertNull($request->getPassword());
    }

    public function testAjax()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $this->assertTrue($request->ajax());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'SomethingElse'
        ]);
        $this->assertFalse($request->ajax());

        $request = $this->createRequest();
        $this->assertFalse($request->ajax());
    }

    public function testPajax()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_PJAX' => 'true'
        ]);
        $this->assertTrue($request->pajax());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_PJAX' => 'false'
        ]);
        $this->assertFalse($request->pajax());
    }

    public function testPrefetch()
    {
        // Test X-Moz header
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_MOZ' => 'prefetch'
        ]);
        $this->assertTrue($request->prefetch());

        // Test X-Purpose header
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_PURPOSE' => 'preview'
        ]);
        $this->assertTrue($request->prefetch());

        // Test case insensitive
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_MOZ' => 'PREFETCH'
        ]);
        $this->assertTrue($request->prefetch());

        // Test not prefetch
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_MOZ' => 'something'
        ]);
        $this->assertFalse($request->prefetch());

        $request = $this->createRequest();
        $this->assertFalse($request->prefetch());
    }
}