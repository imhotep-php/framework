<?php

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;

class RouteClearCommand extends Command
{
    public static string $defaultName = 'route:clear';

    public static string $defaultDescription = 'Remove the route cache file';

    public function handle(): void
    {
        @unlink($this->app->basePath('storage/framework/route.php'));

        $this->components()->success('Cache file removed is success');
    }
}