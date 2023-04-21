<?php

declare(strict_types=1);

namespace Imhotep\Console\Command;

class HelpCommand extends Command
{
    public static string $defaultName = 'help';

    public static string $defaultDescription = 'Display help for the given command. When no command is given display help for the list command';

    public function handle(): void
    {
        //echo $this->input->getArgument('command');
    }

    public function getArguments(): array
    {
        return [];
    }
}