<?php declare(strict_types=1);

namespace Imhotep\Http\Request;

class HeaderBag extends ParameterBug
{
    protected function modifyKey(string $key): string
    {
        return str_replace('-', '_', strtoupper($key));
    }
}