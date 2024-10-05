<?php

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Filesystem\Filesystem;

class ConfigClearCommand extends Command
{
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): void
    {
        $this->files->delete($this->app->configCachePath());

        $this->components()->info('Configuration cache cleared successfully.');
    }
}