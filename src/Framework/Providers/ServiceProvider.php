<?php declare(strict_types=1);

namespace Imhotep\Framework\Providers;

use Closure;
use Imhotep\Framework\Application;
use Imhotep\Console\Application as Console;

abstract class ServiceProvider
{
    protected Application $app;

    public array $aliases = [];

    public array $bindings = [];

    public array $singletons = [];

    public array $commands = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register()
    {
    }

    public function boot()
    {
    }

    public function terminate()
    {
    }

    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config'];

        $config->set($key, array_merge(
            require $path, $config->get($key, [])
        ));
    }


    // Callbacks
    protected $bootingCallbacks = [];
    protected $bootedCallbacks = [];

    public function booting(Closure $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(Closure $callback)
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootingCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->app->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    public function callBootedCallbacks()
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->app->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    public function loadViewFrom(string|array $path, string $namespace): static
    {
        return $this->callAfterResolving('view', function ($view) use ($path, $namespace) {
            $view->addNamespace($namespace, $path);
        });
    }
    
    public function loadLangFrom(string|array $path, string $namespace)
    {
        return $this->callAfterResolving('localizator', function ($lang) use ($path, $namespace) {
            $lang->addNamespace($namespace, $path);
        });
    }

    protected function callAfterResolving(string $abstract, Closure $callback): static
    {
        $this->app->afterResolving($abstract, $callback);

        if ($this->app->resolved($abstract)) {
            $callback($this->app[$abstract], $this->app);
        }

        return $this;
    }

    protected function commands(array $commands): void
    {
        Console::starting(function ($console) use ($commands) {
            foreach ($commands as $name => $command) {
                $console->addCommand($command, is_string($name) ? $name : null);
            }
        });
    }
}