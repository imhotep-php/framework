<?php declare(strict_types=1);

namespace Imhotep\Auth;

use Imhotep\Contracts\Auth\Factory;
use Imhotep\Framework\Providers\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'auth' => [AuthManager::class, Factory::class]
    ];

    public function register()
    {
        $this->app->singleton('auth', function ($app) {
            return new AuthManager($app);
        });

        /*
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function ($guard = null) use ($app) {
                return call_user_func($app['auth']->userResolver(), $guard);
            });
        });
        */
    }
}