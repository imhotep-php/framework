<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Concerns;

trait PathHelpers
{
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
}
