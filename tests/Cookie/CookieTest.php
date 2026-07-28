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
        $this->assertSame('app', $cookie->name());
        $this->assertSame('test', $cookie->value());
        $this->assertSame('/path', $cookie->path());
        $this->assertSame('/domain', $cookie->domain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->sameSite());


        $cookie = $jar->forever('app', 'test', '/path', '/domain', false, true, 'strict');
        $this->assertSame('app', $cookie->name());
        $this->assertSame('test', $cookie->value());
        $this->assertSame('/path', $cookie->path());
        $this->assertSame('/domain', $cookie->domain());
        $this->assertFalse($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->sameSite());

        $cookie = $jar->forget('app');
        $this->assertEmpty($cookie->value());
        $this->assertTrue($cookie->expires() < time());
    }

    public function test_cookies_created_with_props_and_default()
    {
        $jar = $this->getCookieJar();
        $jar->setDefault('/path', '/domain', true, 'lax');

        $cookie = $jar->make('app', 'test');
        $this->assertSame('app', $cookie->name());
        $this->assertSame('test', $cookie->value());
        $this->assertSame('/path', $cookie->path());
        $this->assertSame('/domain', $cookie->domain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly()); // true by default
        $this->assertSame('lax', $cookie->sameSite());
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
        $this->assertSame('daz', $jar->queued('baz')->value());
    }

    public function getCookieJar()
    {
        return new CookieJar();
    }
}