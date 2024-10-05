<?php declare(strict_types=1);

namespace Imhotep\Framework\Console;

use Imhotep\Console\Application as Console;
use Imhotep\Console\Command\ClosureCommand;
use Imhotep\Console\Command\Command;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Framework\Application;

class Kernel
{
    protected Application $app;

    protected Console $console;

    protected array $bootstrappers = [
        \Imhotep\Framework\Bootstrap\HandleExceptions::class,
        \Imhotep\Framework\Bootstrap\LoadEnvironment::class,
        \Imhotep\Framework\Bootstrap\LoadConfiguration::class,
        \Imhotep\Framework\Bootstrap\RegisterFacades::class,
        \Imhotep\Framework\Bootstrap\SetRequestForConsole::class
    ];

    protected bool $commandsLoaded = false;

    protected int $commandStartedAt = 0;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->console = new Console($this->app, $this->app->version());

        $this->app->alias('console', Console::class);
        $this->app->instance('console', $this->console);
    }

    public function bootstrap(): void
    {
        $this->app->bootstrapWith($this->bootstrappers);

        /*
        if (! $this->commandsLoaded) {
            $this->commands();
            $this->commandsLoaded = true;
        }
        */
    }

    public function handle($input, $output): int
    {
        $this->commandStartedAt = now();

        try {
            $this->bootstrap();

            return $this->console->run($input, $output);
        }
        catch (\Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);
        }

        return 1;
    }

    public function terminate($input, $status)
    {

    }

    public function getApplication()
    {

    }



    public function run($input, $output){

    }

    public function call($command, array $arguments, $outputBuffer)
    {

    }

    public function commands()
    {

    }

    protected function loadCommands(string|array $paths): void
    {
        $paths = is_array($paths) ? $paths : [$paths];

        $paths = array_filter($paths, function ($path) {
           return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        foreach ($paths as $path) {
            $filenames = array_diff(scandir($path), ['..','.']);

            foreach($filenames as $filename) {
                if (! is_file($path.'/'.$filename)) {
                    continue;
                }

                if (!str_ends_with($filename, '.php')) {
                    continue;
                }

                $namespace = $this->app->getNamespace();
                $command = str_replace(realpath($this->app->path()).'/', '', $path.'/'.$filename);
                $command = $namespace.str_replace(['/', '.php'], ['\\', ''], $command);

                if (is_subclass_of($command, Command::class)) {
                    $this->console->resolveCommand($command::$defaultName, $command);
                }
            }
        }
    }

    public function addCommand(string $name, \Closure $callback): ClosureCommand
    {
        $command = new ClosureCommand($callback);
        $command::$defaultName = $name;

        $this->console->resolveCommand($name, $command);

        return $command;
    }


    protected function reportException(\Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    protected function renderException($output, \Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->renderForConsole($e, $output);
    }
}
