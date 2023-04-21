<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Console;

use Imhotep\Console\Formatter\Formatter;

interface Output
{
    public function newLine(): void;

    public function writeln(string|iterable $messages): void;

    public function write(string|iterable $messages, $newline = false): void;

    public function getFormatter(): Formatter;
}