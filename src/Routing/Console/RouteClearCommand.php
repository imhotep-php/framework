<?php declare(strict_types=1);

namespace Imhotep\Routing\Console;

use Imhotep\Console\Command\Command;

class RouteClearCommand extends Command
{
    public static string $defaultName = 'route:clear';

    public static string $defaultDescription = 'Remove the route cache file';

    public function handle(): int
    {
        @unlink($this->app->basePath('storage/framework/route.php'));

        $this->components()->success('Cache file removed is success');

        return 0;
    }
}