<?php

namespace Imhotep\Tests\Http;

use DateTime;
use DateTimeInterface;
use Imhotep\Contracts\Arrayable;
use Imhotep\Contracts\Jsonable;
use Imhotep\Cookie\Cookie;
use Imhotep\Http\Response;
use InvalidArgumentException;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function testProtocol(): void
    {
        // Default: 1.1
        $this->assertSame('1.1', $this->response->protocol());

        $this->response->setProtocol('2');
        $this->assertSame('2', $this->response->protocol());
    }

    public function testCharset(): void
    {
        $this->assertSame('UTF-8', $this->response->charset());

        $this->response->setCharset('ISO-8859-1');
        $this->assertSame('ISO-8859-1', $this->response->charset());

        $this->response->setContentType('text/html', 'ISO-8859-2');
        $this->assertSame('text/html', $this->response->header('Content-Type'));
        $this->assertSame('ISO-8859-2', $this->response->charset());
    }

    public function testContent(): void
    {
        $this->assertNull($this->response->content());

        $content = 'Hello, World!';
        $this->response->setContent($content);
        $this->assertSame($content, $this->response->content());
        $this->assertIsString($this->response->content());
    }

    public function testJsonableContent(): void
    {
        $response = new Response(new ArrayableStub);
        $this->assertSame('{"foo":"bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonableStub);
        $this->assertSame('foo', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableAndJsonableStub);
        $this->assertSame('{"foo":"bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = (new Response)->setContent(['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonSerializableStub);
        $this->assertSame('{"foo":"bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableStub);
        $this->assertSame('{"foo":"bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response->setContent('{"foo": "bar"}');
        $this->assertSame('{"foo": "bar"}', $response->content());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testOriginalContent(): void
    {
        $response = new Response(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $response->originalContent());

        $response->setContent(['baz' => 'qux']);
        $this->assertSame(['baz' => 'qux'], $response->originalContent());

        // Retrieves the first original content
        $previousResponse = new Response(['foo' => 'bar']);
        $response = new Response($previousResponse);

        $this->assertSame(['foo' => 'bar'], $response->originalContent());
    }

    public function testStatusCode(): void
    {
        $this->assertSame(200, $this->response->status());

        $this->response->setStatus(404);
        $this->assertSame(404, $this->response->status());
        $this->assertSame('Not Found', $this->response->statusPhrase());

        $this->response->setStatus(418, "Imhotep");
        $this->assertSame(418, $this->response->status());
        $this->assertSame("Imhotep", $this->response->statusPhrase());

        $this->response->setStatus(999);
        $this->assertSame(999, $this->response->status());
        $this->assertTrue($this->response->isInvalid());
        $this->assertFalse($this->response->isSuccessful());

        $this->response->setStatus(200);
        $this->assertTrue($this->response->isOk());
        $this->assertTrue($this->response->isSuccessful());

        $this->response->setStatus(404);
        $this->assertTrue($this->response->isNotFound());
        $this->assertTrue($this->response->isClientError());

        $this->response->setStatus(100);
        $this->assertTrue($this->response->isInformational());
    }

    public function testHeaders(): void
    {
        // Set one header
        $this->response->setHeader('X-Custom-Header', 'CustomValue');
        $this->assertSame('CustomValue', $this->response->header('X-Custom-Header'));

        // Set many headers
        $headers = [
            'X-Header-One' => 'Value1',
            'X-Header-Two' => 'Value2',
            'Content-Type' => 'application/xml'
        ];
        $this->response->setHeaders($headers);

        foreach ($headers as $name => $value) {
            $this->assertSame($value, $this->response->header($name));
        }

        // Check headers
        $this->assertTrue($this->response->hasHeader('Content-Type'));
        $this->assertFalse($this->response->hasHeader('Non-Existent-Header'));

        // Remove header
        $this->assertTrue($this->response->hasHeader('X-Header-One'));
        $this->response->removeHeader('X-Header-One');
        $this->assertFalse($this->response->hasHeader('X-Header-One'));

        // Get all headers
        $allHeaders = $this->response->headers();
        $this->assertIsArray($allHeaders);
        $this->assertArrayHasKey('x-header-two', $allHeaders);
    }

    public function testCacheControl(): void
    {
        $this->response->setCacheControl('no-cache');
        $this->response->setCacheControl('private');
        $this->assertSame('no-cache, private', $this->response->cacheControl());

        $this->response->clearCacheControl()->setCacheControl('public');
        $this->assertSame('public', $this->response->cacheControl());

        $this->response->clearCacheControl()->setCacheControl('private');
        $this->assertSame('private', $this->response->cacheControl());

        $this->response->clearCacheControl()->setCacheControl('max-age', 3600);
        $this->assertSame('max-age=3600', $this->response->cacheControl());

        $this->response->clearCacheControl()->setCacheControl('no-cache');
        $this->assertSame('no-cache', $this->response->cacheControl());

        $this->response->clearCacheControl()->setCacheControl('must-revalidate');
        $this->assertSame('must-revalidate', $this->response->cacheControl());

        $expires = new DateTime('+1 hour');
        $this->response->setExpires($expires);
        $this->assertInstanceOf(DateTimeInterface::class, $this->response->expires());
        $this->assertTrue($this->response->hasHeader('Expires'));

        $this->response->setETag('abc123');
        $this->assertSame('"abc123"', $this->response->etag());
        $this->assertTrue($this->response->hasHeader('ETag'));

        $lastModified = new DateTime('-1 day');
        $this->response->setLastModified($lastModified);
        $this->assertInstanceOf(DateTimeInterface::class, $this->response->lastModified());
        $this->assertTrue($this->response->hasHeader('Last-Modified'));
    }

    public function testCookies(): void
    {
        $this->response->setCookie('session_id', 'abc123');
        $cookies = $this->response->cookies();

        $this->assertCount(1, $cookies);
        $this->assertArrayHasKey('session_id', $cookies);
        $this->assertInstanceOf(Cookie::class, $cookies['session_id']);
        $this->assertSame('abc123', $cookies['session_id']->value());

        $this->response->setCookie(
            'user_prefs',
            'theme=dark',
            time() + 3600, // 60 mins
            '/',
            'example.com',
            true,
            true,
            'STRICT'
        );

        $cookies = $this->response->cookies();
        $this->assertCount(2, $cookies);

        $cookie = $this->response->cookie('user_prefs');
        $this->assertSame('theme=dark', $cookie->value());
        $this->assertSame('/', $cookie->path());
        $this->assertSame('example.com', $cookie->domain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->sameSite());

        // Delete cookie
        $this->response->removeCookie('session_id');
        $this->assertNotNull($this->response->cookie('session_id'));
        $this->assertLessThan(time(), $this->response->cookie('session_id')->expires());

        $this->response->clearCookies();
        $this->assertEmpty($this->response->cookies());

        $this->assertNull($this->response->cookie('non-existent'));
    }

    public function testSend(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Xdebug is required for header testing');
        }

        ob_start();

        $this->response->setContent('Test content');
        $this->response->setHeader('X-Test', 'Value');
        $this->response->send();

        $output = ob_get_clean();

        $this->assertSame('Test content', $output);

        $headers = xdebug_get_headers();
        $this->assertNotEmpty($headers);
    }


}


class ArrayableStub implements Arrayable
{
    public function toArray(): array
    {
        return ['foo' => 'bar'];
    }
}

class ArrayableAndJsonableStub implements Arrayable, Jsonable
{
    public function toJson($options = 0): string
    {
        return '{"foo":"bar"}';
    }

    public function toArray(): array
    {
        return [];
    }
}

class JsonableStub implements Jsonable
{
    public function toJson($options = 0): string
    {
        return 'foo';
    }
}

class JsonSerializableStub implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}