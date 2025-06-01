<?php declare(strict_types=1);

namespace Imhotep\Contracts\Http;

interface Response
{
    public function getContent(): mixed;
}