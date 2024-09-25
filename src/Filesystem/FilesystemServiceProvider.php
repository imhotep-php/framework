<?php

declare(strict_types=1);

namespace Imhotep\Filesystem;

use Imhotep\Framework\Providers\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'filesystem' => FilesystemManager::class
    ];

    public function register()
    {
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });

        $this->app->singleton('filesystem.disk', function ($app) {
           return $app['filesystem']->disk();
        });

        $this->app->singleton('filesystem.cloud', function ($app) {
            return $app['filesystem']->cloud();
        });

        $this->app->singleton('files', function () {
            return new Filesystem();
        });
    }

    public function boot()
    {

    }
}