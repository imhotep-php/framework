<?php

declare(strict_types=1);

namespace Imhotep\Console\Command;

class ClosureCommand extends Command
{
    protected \Closure $callback;

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;

        parent::__construct();
    }

    public function handle(): void
    {
        $this->callback();
    }
}