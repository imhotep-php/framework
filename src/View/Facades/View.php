<?php

declare(strict_types=1);

namespace Imhotep\View\Facades;

class View
{
    public static function make(string $view, array $data = [])
    {
        return app('view')->make($view, $data);
    }
}