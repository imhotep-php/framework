<?php declare(strict_types = 1);

namespace Imhotep\Redis;

use Imhotep\Framework\Providers\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('redis', function ($app) {
            $config = config('database.redis');
            $driver = config('database.redis.client', 'phpredis');

            return new RedisManager($app, $driver, $config);
        });
    }
}