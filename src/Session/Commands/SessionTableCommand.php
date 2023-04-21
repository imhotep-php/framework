<?php

declare(strict_types=1);

namespace Imhotep\Session\Commands;

use Imhotep\Console\Command\Command;

class SessionTableCommand extends Command
{
    public static string $defaultName = 'session:table';

    public static string $defaultDescription = 'Create a migration for the session database table';

    public function handle(): void
    {

    }
}