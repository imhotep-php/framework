<?php declare(strict_types=1);

namespace Imhotep\View\Engines;

class FileEngine extends Engine
{
    public function get(string $path): string
    {
        return file_get_contents($path);
    }
}