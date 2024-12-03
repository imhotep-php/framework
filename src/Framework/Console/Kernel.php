<?php declare(strict_types=1);

namespace Imhotep\Framework\Console;

use Closure;
use Imhotep\Console\Application as Console;
use Imhotep\Console\Command\ClosureCommand;
use Imhotep\Console\Output\NullOutput;
use Imhotep\Console\Utils\SignatureParser;
use Imhotep\Contracts\Console\Input;
use Imhotep\Contracts\Console\Kernel as KernelContract;
use Imhotep\Contracts\Console\Output;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Framework\Application;
use Imhotep\Framework\Events\Terminating;
use Throwable;

class Kernel
{
    protected Application $app;

    protected ?Console $console = null;

    protected array $bootstrappers = [
        \Imhotep\Framework\Bootstrap\HandleExceptions::class,
        \Imhotep\Framework\Bootstrap\LoadEnvironment::class,
        \Imhotep\Framework\Bootstrap\LoadConfiguration::class,
        \Imhotep\Framework\Bootstrap\RegisterFacades::class,
        \Imhotep\Framework\Bootstrap\SetRequestForConsole::class
    ];

    protected bool $commandsLoaded = false;

    protected ?int $commandStartedAt = null;

    protected array $commandHandlers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->app->alias('events', Dispatcher::class);
        $this->app->instance(KernelContract::class, $this);
    }

    public function bootstrap(): void
    {
        $this->app->bootstrapWith($this->bootstrappers);

        if (! $this->commandsLoaded) {
            $this->commands();

            $this->commandsLoaded = true;
        }
    }

    public function handle(Input $input, Output $output = null): int
    {
        $this->commandStartedAt = now();

        try {
            $this->bootstrap();

            return $this->getConsole()->run($input, $output);
        }
        catch (Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);
        }

        return 1;
    }

    public function call(string $command, array $parameters = [], Output $output = null): int
    {
        $this->bootstrap();

        return $this->getConsole()->call($command, $parameters, $output);
    }

    public function callSilent(string $command, array $parameters = []): int
    {
        $this->bootstrap();

        return $this->getConsole()->call($command, $parameters, new NullOutput());
    }

    public function whenCommandLongerThan(int $threshold, callable $handler): void
    {
        $this->commandHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    public function terminate(Input $input, int $status): void
    {
        $this->app['events']?->dispatch(new Terminating());

        $this->app->terminate();

        if (is_null($this->commandStartedAt)) {
            return;
        }

        $time = round((now() - $this->commandStartedAt) * 1000);

        foreach($this->commandHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            if ($time >= $threshold) {
                $handler($this->commandStartedAt, $input, $status, $time);
            }
        }

        $this->commandStartedAt = null;
    }

    protected function commands(): void
    {
        // $this->loadCommands([...]);
    }

    protected function loadCommands(string|array $paths): void
    {
        $paths = array_filter(is_array($paths) ? $paths : [$paths], function ($path) {
           return is_dir($path);
        });

        if (empty($paths)) return;

        $commands = [];
        foreach ($paths as $path) {
            $filenames = array_diff(scandir($path), ['..','.']);

            foreach($filenames as $filename) {
                if (! is_file($path.'/'.$filename)) {
                    continue;
                }

                if (! str_ends_with($filename, '.php')) {
                    continue;
                }

                $namespace = $this->app->getNamespace();
                $command = str_replace(realpath($this->app->path()).'/', '', $path.'/'.$filename);
                $command = $namespace.str_replace(['/', '.php'], ['\\', ''], $command);

                $commands[] = $command;
            }
        }

        if (! empty($commands)) {
            Console::starting(function ($console) use ($commands) {
                foreach ($commands as $command) {
                    $console->addCommand($command);
                }
            });
        }
    }

    public function command(string $signature, Closure $callback): ClosureCommand
    {
        $name = SignatureParser::name($signature);

        $command = new ClosureCommand($signature, $callback, $name);

        $this->getConsole()->addCommand($command, $name);

        return $command;
    }

    protected function reportException(Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    protected function renderException($output, Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->renderForConsole($e, $output);
    }


    public function getConsole(): Console
    {
        if ($this->console) {
            return $this->console;
        }

        return $this->console = new Console($this->app, $this->app['events'],
            'Imhotep Framework', $this->app->version()
        );
    }

    public function setConsole(Console $console): void
    {
        $this->console = $console;
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    public function setApplication(Application $app): static
    {
        $this->app = $app;

        return $this;
    }
}
