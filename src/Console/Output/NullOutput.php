<?php declare(strict_types=1);

namespace Imhotep\Console\Output;

class NullOutput extends Output
{
    protected function doWrite(string $message): void
    {
        // Nothing...
    }

    protected function hasColorSupport(): bool
    {
        return false;
    }
}