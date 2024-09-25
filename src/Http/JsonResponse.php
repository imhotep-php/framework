<?php

declare(strict_types=1);

namespace Imhotep\Http;

class JsonResponse extends Response
{
    public function __construct(array $content = [], int $statusCode = 200, array $headers = [], int $options = 0)
    {
        $this->json($content)->setStatusCode($statusCode)->setHeaders($headers);
    }
}