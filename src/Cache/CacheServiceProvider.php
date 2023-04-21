<?php

declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Framework\Providers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'cache' => CacheManager::class
    ];

    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });
    }
}