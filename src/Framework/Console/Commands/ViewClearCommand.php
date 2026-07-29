<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;

class ViewClearCommand extends Command
{
    public static string $defaultDescription = 'Clear view cache';

    public string $signature = '';

    public function handle(): int
    {
        $cachePath = config('view.cache_path');

        if (empty($cachePath)) {
            $this->error('View cache path is not configured');
            return 1;
        }

        if (!is_dir($cachePath)) {
            $this->warn("Cache directory does not exist: {$cachePath}");
            return 0;
        }

        files()->cleanDirectory(config('view.cache_path'));

        $this->blockSuccess('View cache cleared');

        return 0;
    }
}