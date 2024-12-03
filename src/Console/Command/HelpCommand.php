<?php declare(strict_types=1);

namespace Imhotep\Console\Command;

class HelpCommand extends Command
{
    public static string $defaultDescription = 'Display help for the given command.';

    public string $signature = 'help {command_name? : The command name for the help command.}';

    public function handle(): int
    {
        $command = $this->getCommand();

        $details = $this->getCommandDetails($command);

        $this->newLine();

        if (! empty($details['Description'])) {
            $this->displaySection('Description');
            $this->line('  '.$details['Description']);
            $this->newLine();
        }

        $this->displaySection('Usage');
        $this->line('  '.$details['Usage']);
        $this->newLine();

        if (! empty($details['Arguments'])) {
            $this->displaySection('Arguments');

            $length = 0;
            foreach ($details['Arguments'] as $name => $desc) {
                $nameLength = strlen($name);
                if ($nameLength > $length) $length = $nameLength;
            }
            $length += 2;

            foreach ($details['Arguments'] as $name => $desc) {
                $this->line(sprintf('  <fg=green>%-'.$length.'s</> %s', $name, $desc));
            }

            $this->newLine();
        }

        if (! empty($details['Options'])) {
            $this->displaySection('Options');

            $length = 0;
            foreach ($details['Options'] as $name => $desc) {
                $nameLength = strlen($name);
                if ($nameLength > $length) $length = $nameLength;
            }
            $length += 2;

            foreach ($details['Options'] as $name => $desc) {
                $this->line(sprintf('  <fg=green>%-'.$length.'s</> %s', $name, $desc));
            }

            $this->newLine();
        }

        if ($command->name() === 'help') {
            $this->displaySection('Help');
            $this->line('  The <fg=green>help</> command displays help for a given command:');
            $this->newLine();
            $this->line('    <fg=green>./imhotep help list</>');
            $this->newLine();
            $this->line('  You can also output the help in other formats by using the --format option:');
            $this->newLine();
            $this->line('    <fg=green>./imhotep help --format=json list</>');
            $this->newLine();
            $this->line('  To display the list of available commands, please use the <fg=green>list</> command.');
            $this->newLine();
        }

        return 0;
    }

    protected function getCommand(): Command
    {
        $commandName = $this->hasArgument('command_name') ?
            $this->argument('command_name') : $this->argument('command');

        return $this->console->hasCommand($commandName) ?
            $this->console->getCommand($commandName) : $this;
    }

    protected function displaySection(string $section): void
    {
        $this->line("<fg=yellow>{$section}:</>");
    }

    protected function getCommandDetails(Command $command): array
    {
        $usage = $command->name();

        if ($command->hasOptions()) {
            $usage .= ' [options]';
        }

        if ($arguments = $command->getArguments()) {
            $arguments = array_reverse($arguments);

            $usageArguments = '';
            foreach ($arguments as $argument) {
                $usageArguments.= ' ['.$argument->getName().$usageArguments.']';
            }

            $usage .= $usageArguments;
        }


        return [
            'Description' => $command->description(),
            'Usage' => $usage, //$command->name().' [options] [--] [command_name]',
            'Arguments' => $this->getCommandArgumentsDetails($command),
            'Options' => $this->getCommandOptionsDetails($command),
        ];
    }

    protected function getCommandArgumentsDetails(Command $command): array
    {
        $details = [];

        foreach ($command->getArguments() as $argument) {
            $description = $argument->getDescription();

            if ($default = $argument->getDefault()) {
                $description.= ' <fg=yellow>[default: "'.((string)$default).'"]</>';
            }

            $details[ $argument->getName() ] = $description;
        }

        return $details;
    }

    protected function getCommandOptionsDetails(Command $command): array
    {
        $details = [];

        $options = $command->getOptions();

        foreach ($options as $option) {
            $name = '';

            if ($shortcut = $option->getShortcut()) {
                if (is_string($shortcut)) {
                    $name.= '-'.$shortcut;
                }
                elseif (is_array($shortcut)) {
                    $name.= '-'.implode('|', $shortcut);
                }
            }

            $name.= (empty($name) ? '' : ', ').'--'.$option->getName();

            if ($option->isValueOptional()) {
                $name.= '='.strtoupper($option->getName());
            }
            elseif ($option->isValueRequired()) {
                $name.= '[='.strtoupper($option->getName()).']';
            }

            $description = $option->getDescription();

            if ($default = $option->getDefault()) {
                $description.= ' <fg=yellow>[default: '.((string)$default).']</>';
            }

            $details[$name] = $description;
        }

        $details['-h, --help'] = 'Display help for the command.';
        $details['-V, --version'] = 'Display this application version';

        return $details;
    }
}