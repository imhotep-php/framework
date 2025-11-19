<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Commands\CacheTableCommand;
use Imhotep\Contracts\Cache\ICache;
use Imhotep\Contracts\Cache\ICacheFactory;
use Imhotep\Framework\Providers\ServiceProvider;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'cache' => [CacheManager::class, ICacheFactory::class],
        'cache.store' => [ICache::class, CacheInterface::class],
    ];

    public function register(): void
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->store();
        });


        $this->commands([
            'cache:table' => CacheTableCommand::class,
        ]);
    }
}