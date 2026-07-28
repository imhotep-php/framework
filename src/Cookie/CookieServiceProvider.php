<?php declare(strict_types=1);

namespace Imhotep\Cookie;

use Imhotep\Framework\Providers\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'cookie' => CookieJar::class,
    ];

    public function register(): void
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app['config']->subset('session');

            return (new CookieJar())->setDefault(
                path: $config->string('path'),
                domain: $config->string('domain'),
                secure: $config->bool('secure'),
                httpOnly: $config->bool('httpOnly'),
                sameSite: $config->string('sameSite')
            );
        });
    }
}