<?php

declare(strict_types=1);

namespace Imhotep\Facades;

class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}