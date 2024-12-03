<?php

namespace Imhotep\Console\Events;

use Imhotep\Contracts\Console\Command;
use Imhotep\Contracts\Console\Input as InputContract;
use Imhotep\Contracts\Console\Output as OutputContract;

class CommandFinish
{
    public function __construct(
        public Command $command,
        public InputContract $input,
        public OutputContract $output,
        public int $exitCode
    ) {}
}