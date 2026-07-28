<?php

namespace Imhotep\Debug\Dumper;

use Imhotep\Debug\Data;

abstract class AbstractDumper implements IDumper
{
    protected mixed $outputStream = null;

    protected int $maxDepth;

    public function __construct(mixed $outputStream = null, int $maxDepth = 5)
    {
        if (is_null($outputStream)) {
            $outputStream = fopen('php://output', 'w');
        }

        $this->outputStream = $outputStream;

        $this->maxDepth = $maxDepth;
    }

    abstract public function dump(Data $data);

    public function write(string $data): void
    {
        fwrite($this->outputStream, $data);
    }
}