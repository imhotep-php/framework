<?php declare(strict_types=1);

namespace Imhotep\Hash;

use Imhotep\Framework\Providers\ServiceProvider;

class HashServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'hash' => HashManager::class
    ];

    public function register(): void
    {
        $this->app->singleton('hash', function ($app) {
            return new HashManager($app);
        });
    }
}