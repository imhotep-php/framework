<?php

declare(strict_types=1);

namespace Imhotep\Console;

use Imhotep\Container\Container;
use Imhotep\Console\Command\Command;
use Imhotep\Console\Command\HelpCommand;
use Imhotep\Console\Command\ListCommand;
use Imhotep\Console\Input\InputArgument;
use Imhotep\Console\Input\InputDefinition;
use Imhotep\Console\Input\InputOption;
use Imhotep\Console\Input\Option;
use Imhotep\Contracts\Console\Command as CommandContract;
use Imhotep\Contracts\Console\ConsoleException;
use Imhotep\Contracts\Console\Input as InputContract;
use Imhotep\Contracts\Console\Output as OutputContract;
use InvalidArgumentException;

class Application
{
    protected ?Container $container = null;

    public string $name = 'Imhotep Framework';

    public string $version = '';

    protected array $commands = [];

    protected array $defaultCommands = [
        'list' => ListCommand::class,
        'help' => HelpCommand::class
    ];

    protected string $defaultCommand = 'list';

    protected ?InputContract $input = null;

    protected ?OutputContract $output = null;

    public function __construct(Container $container, string $version)
    {
        $this->container = $container;
        $this->version = $version;
    }

    public function run(InputContract $input, OutputContract $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->input->bind($this->getDefaultDefinitions());

        if ($this->input->hasOption('help')) {
            $commandName = 'help';
        }
        else {
            $commandName = $this->getCommandName();
        }

        $this->runCommand($commandName);

        return 0;
    }

    protected function runCommand($commandName): void
    {
        $command = $this->getCommand($commandName);

        $this->configureIO($command);

        $command->setInput($this->input)
                ->setOutput($this->output)
                ->setContainer($this->container)
                ->setApplication($this->container)
                ->setConsole($this);

        $command->handle();
    }

    protected function getCommandName(): string
    {
        return $this->input->getFirstArgument() ?? $this->defaultCommand;
    }

    protected function getCommand(string $commandName): Command|\Closure
    {
        $command = $this->commands[$commandName] ?? $this->defaultCommands[$commandName] ?? null;

        if (is_null($command)) {
            throw new ConsoleException("Command [{$commandName}] not found.");
        }

        if ($command instanceof \Closure) {
            return $command;
        }

        if (is_subclass_of($command, Command::class)) {
            return $this->container->make($command);
        }

        throw new ConsoleException("Command [{$commandName}] is not extend [".Command::class."].");
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return array_merge($this->defaultCommands, $this->commands);
    }


    public function resolveCommand($name, $command): void
    {
        $this->commands[ $name ] = $command;
    }

    protected function configureIO(CommandContract $command): void
    {
        $definition = $this->getDefaultDefinitions();

        foreach ($command->getOptions() as $option) {
            $definition->addOption($option);
        }

        foreach ($command->getArguments() as $argument) {
            $definition->addArgument($argument);
        }

        $this->input->bind($definition);
    }

    public function getDefaultDefinitions(): InputDefinition
    {
        return new InputDefinition([
            InputArgument::builder('command')->required()->description('The command name')->build(),
            InputOption::builder('help', 'h')->build(),
            InputOption::builder('version', 'V')->build(),
            InputOption::builder('verbose', 'v|vv|vvv')->build(),
        ]);
    }
}