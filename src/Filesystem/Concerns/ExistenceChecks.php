<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Concerns;

trait ExistenceChecks
{
    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    public function fileExists(string $path): bool
    {
        return $this->isFile($path);
    }

    public function fileMissing(string $path): bool
    {
        return !$this->isFile($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->isDirectory($path);
    }

    public function directoryMissing(string $path): bool
    {
        return !$this->isDirectory($path);
    }
}