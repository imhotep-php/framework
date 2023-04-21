<?php

declare(strict_types=1);

namespace Imhotep\Encryption;

use Imhotep\Contracts\Encryption\MissingAppKeyException;
use Imhotep\Framework\Providers\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'encrypter' => [
            \Imhotep\Contracts\Encryption\Encrypter::class,
            Encrypter::class,
        ],
    ];

    public function register()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app['config']->get('app');

            if (empty($config['key'])) {
                throw new MissingAppKeyException;
            }

            return new Encrypter($config['key'], $config['cipher']);
        });
    }
}