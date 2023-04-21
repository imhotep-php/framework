<?php

declare(strict_types=1);

namespace Imhotep\View;

use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\View\Engines\EngineManager;

class ViewServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'view' => Factory::class,
    ];

    public function register()
    {
        $this->app->singleton('view.finder', function () {
            return new Finder($this->app['config']['view.paths']);
        });

        $this->app->singleton('view', function () {
            return $this->createFactory();
        });
    }

    protected function createFactory()
    {
        $factory = new Factory(
            $this->app,
            $this->app['view.finder'],
            new EngineManager($this->app),
            $this->app['config']['view']
        );

        $factory->share('__app', $this->app);

        return $factory;
    }
}