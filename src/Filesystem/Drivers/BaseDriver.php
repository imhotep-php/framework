<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use BadMethodCallException;
use Generator;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Contracts\Filesystem\IFilesystemDriver;
use Imhotep\Support\MimeTypes;
use Throwable;

abstract class BaseDriver implements IFilesystemDriver
{
    public function __construct(
        protected bool $throwed = true
    ) {}

    protected function handleException(Throwable $e): false
    {
        if ($this->throwed) {
            throw $e;
        }

        return false;
    }

    protected function methodNotSupported(string $method): void
    {
        throw new BadMethodCallException("Method [{$method}] not supported in " . static::class);
    }

    public function __call(string $method, array $parameters): mixed
    {
        $this->methodNotSupported($method);
    }
}
