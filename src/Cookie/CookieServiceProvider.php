<?php

namespace Imhotep\Cookie;

use Imhotep\Contracts\Encryption\Encrypter as EncrypterContract;
use Imhotep\Encryption\Encrypter;
use Imhotep\Framework\Providers\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    public array $aliases = [
        CookieJar::class => 'cookie',
    ];

    public function register()
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app['config']->get('session.drivers.cookie');
            $cookies = new CookieJar();

            if (is_array($config)) {
                $cookies->setDefault(
                    $config['path'], $config['domain'], $config['secure'],
                    $config['httpOnly'], $config['sameSite']
                );
            }

            return $cookies;
        });
    }
}