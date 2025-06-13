<?php declare(strict_types=1);

namespace Imhotep\Cache;

use Imhotep\Cache\Commands\CacheTableCommand;
use Imhotep\Contracts\Cache\ICacheFactory;
use Imhotep\Framework\Providers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'cache' => [CacheManager::class, ICacheFactory::class],
    ];

    public function register(): void
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->commands([
            'cache:table' => CacheTableCommand::class,
        ]);
    }
}