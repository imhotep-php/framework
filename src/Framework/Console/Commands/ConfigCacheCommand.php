<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Filesystem\Filesystem;
use LogicException;
use Throwable;

class ConfigCacheCommand extends Command
{
    public static string $defaultName = 'config:cache';

    public static string $defaultDescription = 'Create a config cache file for faster load application';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $configPath = $this->app->configCachePath();

        $this->files->put($configPath, '<?php return '.var_export(config()->all(), true).';'.PHP_EOL);

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->components()->info('Configuration cached successfully.');

        return 0;
    }
}