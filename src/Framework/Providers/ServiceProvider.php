<?php

declare(strict_types=1);

namespace Imhotep\Framework\Providers;

use Imhotep\Application\Provider\Closure;
use Imhotep\Framework\Application;

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

    public function commands(array $commands): void
    {
        if ($this->app->isAlias('console')) {
            $console = $this->app['console'];

            foreach ($commands as $name => $command) {
                $console->resolveCommand($name, $command);
            }
        }
    }
}