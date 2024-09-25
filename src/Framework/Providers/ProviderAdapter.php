<?php

declare(strict_types=1);

namespace Imhotep\Framework\Providers;

use Imhotep\Framework\Application;
use Imhotep\Framework\PackageManager;

class ProviderAdapter
{
    protected Application $app;

    protected array $providers = [];

    protected array $loaded = [];

    protected array $deferred = [];

    //protected $repository;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get($provider)
    {

    }

    public function register($provider): void
    {
        ($provider === '*')
            ? $this->registerFromConfig()
            : $this->registerOfOne($provider);
    }

    protected function registerFromConfig(): void
    {
        $providers = $this->app['config']->get('app.providers', []);

        $providers = array_merge($providers, ($this->app->make(PackageManager::class))->providers());

        foreach($providers as $provider) {
            $this->registerOfOne($provider);
        }
    }

    protected function registerOfOne($provider): void
    {
        if (is_string($provider)) {
            $provider = $this->resolve($provider);
        }

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->app->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $key = is_int($key) ? $value : $key;
                $this->app->singleton($key, $value);
            }
        }

        if (property_exists($provider, 'aliases')) {
            foreach ($provider->aliases as $class => $alias) {
                $this->app->alias($class, $alias);
            }
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }


        // Mark as registered
        $this->providers[] = $provider;
        $this->loaded[get_class($provider)] = true;


        // Boot provider if app is booted
        if($this->app->isBooted()){
            $this->boot($provider);
        }
    }


    public function boot($provider): void
    {
        ($provider === '*')
            ? $this->bootAll()
            : $this->bootOfOne($provider);
    }

    protected function bootAll(): void
    {
        foreach($this->providers as $provider){
            $this->bootOfOne($provider);
        }
    }

    protected function bootOfOne($provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->app->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    public function resolve($provider): ServiceProvider
    {
        return new $provider($this->app);
    }

    public function getDebug(): array
    {
        $result = [];

        foreach ($this->providers as $provider) {
            $provider = get_class($provider);

            $result[ $provider ] = [
                'loaded' => $this->loaded[$provider] ?? false,
                'deferred' => $this->deferred[$provider] ?? false,
            ];
        }

        return $result;
    }
}