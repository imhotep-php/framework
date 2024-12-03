<?php declare(strict_types=1);

namespace Imhotep\Console\Output;

class BufferedOutput extends Output
{
    protected string $buffer = '';

    public function fetch(): string
    {
        $buffer = $this->buffer;

        $this->buffer = '';

        return $buffer;
    }

    protected function doWrite(string $message): void
    {
        $this->buffer.= $message;
    }

    protected function hasColorSupport(): bool
    {
        return false;
    }
}