<?php

class Request
{

    protected static array $trustedProxies = [];

    protected static array $trustedHosts = [];

    protected static int $trustedHeaderSet = 0;

    public static function setTrustedProxies(array $proxies, int $trustedHeaderSet): void
    {
        static::$trustedProxies = $proxies;

        static::$trustedHeaderSet = $trustedHeaderSet;
    }

    public static function getTrustedProxies(): array
    {
        return static::$trustedProxies;
    }

    public static function getTrustedHeaderSet(): int
    {
        return static::$trustedHeaderSet;
    }

    public static function setTrustedHosts(array $hosts): void
    {
        static::$trustedHosts = $hosts;
    }

    public static function getTrustedHosts(): array
    {
        return self::$trustedHosts;
    }



    public function test_trusted_proxies_XForwardFor()
    {
        $request = Request::create('http://example.com/');
        $request->server->set('REMOTE_ADDR', '3.3.3.3');
        $request->headers->set('X_FORWARDED_FOR', '1.1.1.1, 2.2.2.2');
        $request->headers->set('X_FORWARDED_HOST', 'foo.example.com:1234, real.example.com:8080');
        $request->headers->set('X_FORWARDED_PROTO', 'https');
        $request->headers->set('X_FORWARDED_PORT', 443);

        // no trusted proxies
        $this->assertEquals('3.3.3.3', $request->ip());
        $this->assertEquals('example.com', $request->host());
        $this->assertEquals(80, $request->getPort());
        $this->assertFalse($request->isSecure());

        // disabling proxy trusting
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        $this->assertEquals('3.3.3.3', $request->getClientIp());
        $this->assertEquals('example.com', $request->getHost());
        $this->assertEquals(80, $request->getPort());
        $this->assertFalse($request->isSecure());

        // request is forwarded by a non-trusted proxy
        Request::setTrustedProxies(['2.2.2.2'], Request::HEADER_X_FORWARDED_FOR);
        $this->assertEquals('3.3.3.3', $request->getClientIp());
        $this->assertEquals('example.com', $request->getHost());
        $this->assertEquals(80, $request->getPort());
        $this->assertFalse($request->isSecure());

        // trusted proxy via setTrustedProxies()
        Request::setTrustedProxies(['3.3.3.3', '2.2.2.2'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $this->assertEquals('1.1.1.1', $request->getClientIp());
        $this->assertEquals('foo.example.com', $request->getHost());
        $this->assertEquals(443, $request->getPort());
        $this->assertTrue($request->isSecure());

        // trusted proxy via setTrustedProxies()
        Request::setTrustedProxies(['3.3.3.4', '2.2.2.2'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $this->assertEquals('3.3.3.3', $request->getClientIp());
        $this->assertEquals('example.com', $request->getHost());
        $this->assertEquals(80, $request->getPort());
        $this->assertFalse($request->isSecure());

        // check various X_FORWARDED_PROTO header values
        Request::setTrustedProxies(['3.3.3.3', '2.2.2.2'], Request::HEADER_X_FORWARDED_PROTO);
        $request->headers->set('X_FORWARDED_PROTO', 'ssl');
        $this->assertTrue($request->isSecure());

        $request->headers->set('X_FORWARDED_PROTO', 'https, http');
        $this->assertTrue($request->isSecure());
    }
}