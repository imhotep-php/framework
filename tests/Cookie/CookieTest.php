<?php

namespace Imhotep\Tests\Cache;

use Imhotep\Cookie\Cookie;
use Imhotep\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    public function test_cookies_created_with_props()
    {
        $jar = $this->getCookieJar();

        $cookie = $jar->make('app', 'test', 10, '/path', '/domain', true, false, 'lax');
        $this->assertSame('app', $cookie->getName());
        $this->assertSame('test', $cookie->getValue());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('/domain', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());


        $cookie = $jar->forever('app', 'test', '/path', '/domain', false, true, 'strict');
        $this->assertSame('app', $cookie->getName());
        $this->assertSame('test', $cookie->getValue());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('/domain', $cookie->getDomain());
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());

        $cookie = $jar->forget('app');
        $this->assertEmpty($cookie->getValue());
        $this->assertTrue($cookie->getExpires() < time());
    }

    public function test_cookies_created_with_props_and_default()
    {
        $jar = $this->getCookieJar();
        $jar->setDefault('/path', '/domain', true, 'lax');

        $cookie = $jar->make('app', 'test');
        $this->assertSame('app', $cookie->getName());
        $this->assertSame('test', $cookie->getValue());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('/domain', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly()); // true by default
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_cookies_queued()
    {
        $jar = $this->getCookieJar();

        $this->assertEmpty($jar->getQueuedCookies());
        $this->assertFalse($jar->hasQueued('foo'));

        $jar->queue($jar->make('foo', 'bar'));
        $this->assertTrue($jar->hasQueued('foo'));
        $this->assertInstanceOf(Cookie::class, $jar->queued('foo'));

        $jar->queue('baz', 'daz');
        $this->assertTrue($jar->hasQueued('baz'));
        $this->assertInstanceOf(Cookie::class, $jar->queued('baz'));
        $this->assertSame('daz', $jar->queued('baz')->getValue());
    }

    public function getCookieJar()
    {
        return new CookieJar();
    }
}