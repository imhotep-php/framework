<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\Request;
use PHPUnit\Framework\TestCase;

abstract class RequestTest extends TestCase
{
    protected function createRequest(
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        mixed $content = null
    ): Request {
        return new Request($query, $post, $cookies, $files, $server, $content);
    }

    protected function createMockFile(
        string $name = 'test.txt',
        string $type = 'text/plain',
        int $size = 1024,
        string $tmpName = '/tmp/test.txt',
        int $error = UPLOAD_ERR_OK
    ): array {
        return [
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'tmp_name' => $tmpName,
            'error' => $error,
        ];
    }
}