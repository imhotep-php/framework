<?php declare(strict_types=1);

namespace Imhotep\Console;

use Closure;
use Imhotep\Console\Events\CommandFinish;
use Imhotep\Console\Events\CommandStart;
use Imhotep\Console\Events\ConsoleStarting;
use Imhotep\Console\Input\ArrayInput;
use Imhotep\Console\Input\StringInput;
use Imhotep\Contracts\Console\Output;
use Imhotep\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Imhotep\Console\Command\ClosureCommand;
use Imhotep\Container\Container;
use Imhotep\Console\Command\Command;
use Imhotep\Console\Command\HelpCommand;
use Imhotep\Console\Command\ListCommand;
use Imhotep\Console\Input\InputArgument;
use Imhotep\Console\Input\InputDefinition;
use Imhotep\Console\Input\InputOption;
use Imhotep\Contracts\Console\Command as CommandContract;
use Imhotep\Contracts\Console\ConsoleException;
use Imhotep\Contracts\Console\Input as InputContract;
use Imhotep\Contracts\Console\Output as OutputContract;
use LogicException;

class Application
{
    protected static array $bootstrappers = [];

    /**
     * The list of available application commands.
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * The list of default application commands.
     *
     * @var array
     */
    protected array $defaultCommands = [
        'list' => ListCommand::class,
        'help' => HelpCommand::class
    ];

    /**
     * The default application command.
     *
     * @var string
     */
    protected string $defaultCommand = 'list';

    /**
     * The input instance.
     *
     * @var InputContract|null
     */
    protected ?InputContract $input = null;

    /**
     * The output instance.
     *
     * @var OutputContract|null
     */
    protected ?OutputContract $output = null;

    public function __construct(
        /**
         * The container instance.
         *
         * @var Container
         */
        protected Container $container,

        /**
         * The events instance.
         *
         * @var Dispatcher
         */
        protected Dispatcher $events,

        /**
         * The application name.
         *
         * @var string
         */
        public string $name,

        /**
         * The application version.
         *
         * @var string
         */
        public string $version
    ) { }

    public static function starting(Closure $callback): void
    {
        static::$bootstrappers[] = $callback;
    }

    public function bootstrap(): void
    {
        $this->events?->dispatch(new ConsoleStarting($this));

        foreach (static::$bootstrappers as $callback) {
            $callback($this);
        }
    }

    /**
     * Get the application name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the application version
     *
     * @return string
     */
    public function version(): string
    {
        return $this->version;
    }

    public function call(string $command, array $parameters = [], Output $output = null): int
    {
        if (empty($parameters)) {
            $input = new StringInput($command);
        }
        else {
            $input = new ArrayInput(array_merge(['command' => $command], $parameters));
        }

        return $this->run($input, $output ?? $this->output);
    }

    /**
     * Run the current application.
     *
     * @param InputContract $input
     * @param OutputContract $output
     * @return int
     * @throws ConsoleException
     */
    public function run(InputContract $input, OutputContract $output): int
    {
        $this->bootstrap();

        $this->input = $input;
        $this->output = $output;

        $this->input->bind($this->getDefaultDefinitions());

        if ($this->input->hasOption('help')) {
            $commandName = 'help';
        }
        elseif ($this->input->hasOption('version')) {
            $this->output->writeln($this->name.' <fg=green;>'.$this->version.'</>');

            return 0;
        }
        else {
            $commandName = $this->getCommandName();
        }

        return $this->runCommand($commandName);
    }

    /**
     * Run the specified command.
     *
     * @param string $commandName
     * @return int
     * @throws ConsoleException
     */
    protected function runCommand(string $commandName): int
    {
        $command = $this->getCommand($commandName);

        $this->configureIO($command);

        $this->events?->dispatch(new CommandStart($command, $this->input, $this->output));

        $command->setInput($this->input)
                ->setOutput($this->output)
                ->setContainer($this->container)
                ->setApplication($this->container)
                ->setConsole($this);

        $exitCode = $command->handle();

        $this->events?->dispatch(new CommandFinish($command, $this->input, $this->output, $exitCode));

        return $exitCode;
    }

    /**
     * Get the command name from the input stream or use the default command.
     *
     * @return string
     */
    protected function getCommandName(): string
    {
        return $this->input->getFirstArgument() ?? $this->defaultCommand;
    }

    /**
     * Get a list of all application commands.
     *
     * @return Command[]
     */
    public function getCommands(): array
    {
        return array_merge($this->defaultCommands, $this->commands);
    }

    /**
     * Get a command by name.
     *
     * @param string $commandName
     * @return Command
     * @throws ConsoleException
     */
    public function getCommand(string $commandName): Command
    {
        $command = $this->commands[$commandName] ?? $this->defaultCommands[$commandName] ?? null;

        if (is_null($command)) {
            throw new ConsoleException("Command [".$commandName."] not found.");
        }

        if ($command instanceof ClosureCommand) {
            return $command;
        }

        if (is_subclass_of($command, Command::class)) {
            return $this->container->make($command, ['name' => $commandName]);
        }

        throw new ConsoleException("Command [".$commandName."] is not extend [".Command::class."].");
    }

    /**
     * Determine if a command exists.
     *
     * @param string $commandName
     * @return bool
     */
    public function hasCommand(string $commandName): bool
    {
        $command = $this->commands[$commandName] ?? $this->defaultCommands[$commandName] ?? null;

        return ! is_null($command);
    }

    /**
     * Add a command to the application.
     *
     * @param string|Command $command
     * @param string|null $name
     * @return void
     */
    public function addCommand(string|Command $command, string $name = null): void
    {
        if (is_null($name)) {
            if (! is_subclass_of($command, Command::class)) {
                throw new LogicException('The ['.$command.'] command is not extends of ['.Command::class.'].');
            }

            $name = $command::getDefaultName();

            if (empty($name)) {
                throw new InvalidArgumentException('The property [defaultName] is not specified for the ['.$command.'] command.');
            }
        }
        elseif (empty($name)) {
            throw new InvalidArgumentException('The command name cannot be empty.');
        }

        $this->commands[ $name ] = $command;
    }

    /**
     * Configuring the input stream for the specified command.
     *
     * @param CommandContract $command
     * @return void
     * @throws ConsoleException
     */
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

        if (! $this->input->hasArgument('command')) {
            $command = $this->input->hasOption('help') ? 'help' : $this->defaultCommand;

            $this->input->setArgument('command', $command);
        }
    }

    /**
     * Get a default definition of the application.
     *
     * @return InputDefinition
     */
    protected function getDefaultDefinitions(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command'),
            new InputOption('help', 'h', InputOption::VALUE_NONE),
            new InputOption('version', 'V', InputOption::VALUE_NONE),
        ]);
    }
}