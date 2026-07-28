<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function testRedirect()
    {
        $response = $this->factory->redirect('https://example.com/login');
        $this->assertSame(302, $response->status());
        $this->assertSame('https://example.com/login', $response->header('Location'));

        $response = $this->factory->redirect('https://example.com/new-url', 301, ['Cache-Control' => 'no-cache']);
        $this->assertSame(301, $response->status());
        $this->assertNull($response->header('Cache-Control'));
        $this->assertSame('https://example.com/new-url', $response->header('Location'));
    }

    public function testJson()
    {
        $data = [
            'success' => true,
            'message' => 'Operation completed',
            'data' => ['id' => 123, 'name' => 'Test']
        ];

        $response = $this->factory->json($data);

        $this->assertSame($data, $response->originalContent());
        $this->assertJson($response->content());
        $this->assertSame($data, json_decode($response->content(), true));
        $this->assertSame('application/json', $response->header('Content-Type'));
    }

    public function testJsonp()
    {
        $data = ['foo' => 'bar'];
        $response = $this->factory->jsonp('sendTo', $data);
        $this->assertSame($data, $response->originalContent());
        $this->assertSame('/**/sendTo({"foo":"bar"});', $response->content());
        $this->assertSame('text/javascript', $response->header('Content-Type'));
    }

    public function testXmlResponse(): void
    {
        $xml = '<?xml version="1.0"?><root><item>Test</item></root>';
        $response = $this->factory->xml($xml);

        $this->assertSame($xml, $response->content());
        $this->assertSame('application/xml', $response->contentType());
        $this->assertSame('UTF-8', $response->charset());
    }

    public function testFileDownload(): void
    {
        $fileName = 'test.txt';
        $fileContent = 'Test file content';
        $fileCallback = function () use ($fileContent) {
            echo $fileContent;
        };


        $response = $this->factory->streamDownload($fileCallback, $fileName);

        $this->assertSame($fileCallback, $response->callback());

        ob_start();

        $response->send();

        $output = ob_get_clean();

        $this->assertSame($output, $fileContent);

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
        }
    }
}