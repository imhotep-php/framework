<?php declare(strict_types=1);

namespace Imhotep\Console\Output;

use InvalidArgumentException;

class StreamOutput extends Output
{
    protected mixed $stream;

    public function __construct($stream)
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Invalid stream provided');
        }

        $this->stream = $stream;

        parent::__construct();
    }

    protected function doWrite(string $data): void
    {
        @fwrite($this->stream, $data);
        @fflush($this->stream);
    }

    protected function hasColorSupport(): bool
    {
        return stream_isatty($this->stream);
    }

}