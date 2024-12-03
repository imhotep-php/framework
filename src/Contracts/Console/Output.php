<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Console;

use Imhotep\Console\Formatter\Formatter;

interface Output
{
    public function newLine(int $count = 1): static;

    public function writeln(string|iterable $messages): static;

    public function write(string|iterable $messages, $newline = false): static;

    public function getFormatter(): Formatter;
}