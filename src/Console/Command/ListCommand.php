<?php

declare(strict_types=1);

namespace Imhotep\Console\Command;

class ListCommand extends Command
{
    public static string $defaultName = 'list';

    public static string $defaultDescription = 'List all commands';

    public function handle(): void
    {
        echo sprintf("%s %s\n\n", $this->console->name, $this->console->version);

        echo "Usage:\n";
        echo "\040\040command [options] [arguments]\n\n";

        echo "Options:\n\n";

        echo "Available commands:\n";

        foreach ($this->console->getCommands() as $command) {
            echo sprintf(" %-20s %s\n", $command::$defaultName, $command::$defaultDescription);
        }
        echo "\n";
    }

    public function getArguments(): array
    {
        return [];
    }
}