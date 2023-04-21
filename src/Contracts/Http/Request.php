<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Http;

interface Request
{
    public function getMethod(): string;

    public function isMethod(string $method): bool;

    public function uri();

    public function host();
}