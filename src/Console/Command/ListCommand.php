<?php declare(strict_types=1);

namespace Imhotep\Console\Command;

class ListCommand extends Command
{
    public static string $defaultDescription = 'The list all commands';

    public string $signature = 'list {--format=?txt}';

    public function handle(): int
    {
        if ($this->option('format') === 'json') {
            $this->line($this->commandsJson());

            return 0;
        }


        $this->newLine();
        $this->line('<b>'.$this->console->name().' <fg=green>'.$this->console->version().'</></b>');
        $this->newLine();
        $this->line('<fg=yellow>Usage:</>');
        $this->line("\040\040command [options] [arguments]");
        //$this->newLine();
        //$this->line('<fg=yellow>Options:</>');
        $this->newLine();
        $this->line('<fg=yellow>Available commands:</>');

        foreach ($this->commands() as $group => $commands) {
            ksort($commands);

            if (! empty($group)) $this->line("\040<fg=yellow>$group</>");

            foreach ($commands as $name => $description) {
                $name = implode(":", array_filter([$group, $name]));

                $this->line(sprintf("\040\040<fg=green>%-20s</> %s", $name, $description));
            }
        }

        return 0;
    }

    protected function commands(): array
    {
        $commands = []; $noGroups = [];

        foreach($this->console->getCommands() as $name => $command) {

            if (! str_contains($name, ':')) {
                $noGroups[$name] = $this->getCommandDescription($command);
                continue;
            }

            list($group, $name) = explode(":", $name);

            $commands[$group][$name] = $this->getCommandDescription($command);
        }

        foreach ($noGroups as $name => $description) {
            if (isset($commands[$name])) {
                $commands[$name][''] = $description;

                continue;
            }

            $commands[''][$name] = $description;
        }

        ksort($commands);

        return $commands;
    }

    protected function commandsJson(): string
    {
        $commands = $this->console->getCommands();

        foreach ($commands as $name => $command) {
            $commands[$name] = $this->getCommandDescription($command);
        }

        ksort($commands);

        return json_encode($commands);
    }

    protected function getCommandDescription(string|Command $command): string
    {
        return $command instanceof Command ? $command->getDescription() : $command::getDefaultDescription();
    }
}