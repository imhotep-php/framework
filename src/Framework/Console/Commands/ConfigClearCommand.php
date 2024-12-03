<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Filesystem\Filesystem;

class ConfigClearCommand extends Command
{
    public static string $defaultName = 'config:clear';

    public static string $defaultDescription = 'Remove the configuration cache file';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $this->files->delete($this->app->configCachePath());

        $this->components()->info('Configuration cache cleared successfully.');

        return 0;
    }
}