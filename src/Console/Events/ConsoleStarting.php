<?php

namespace Imhotep\Console\Events;

use Imhotep\Console\Application as Console;

class ConsoleStarting
{
    public function __construct(
        public Console $console
    ) {}
}