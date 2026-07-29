<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Adapters;

use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Contracts\Filesystem\IFilesystemDriver;

class CloudAdapter implements IFilesystem
{
    protected IFilesystemDriver $driver;

    protected array $config;

    public function __construct(IFilesystemDriver $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function __call($method, $parameters)
    {
        return $this->driver->$method(...$parameters);
    }

    public function exists(string $path): bool
    {
        return $this->driver->exists($path);
    }

    public function list(string $path): array|false
    {
        return $this->driver->list($path);
    }

    public function directories(string $path, bool $recursive = false): array
    {
        return $this->driver->directories($path, $recursive);
    }

    public function files(string $path, bool $recursive = false): array
    {
        return $this->driver->files($path, $recursive);
    }

    public function makeDirectory(string $path): bool
    {
        return $this->driver->makeDirectory($path);
    }

    public function cleanDirectory(string $path): bool
    {
        return $this->driver->cleanDirectory($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->driver->deleteDirectory($path);
    }
}
