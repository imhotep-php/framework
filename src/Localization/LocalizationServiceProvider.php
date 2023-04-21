<?php

declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Contracts\Localization\Localizator as LocalizatorContract;
use Imhotep\Framework\Providers\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'localizator' => LocalizatorContract::class
    ];

    public function register()
    {
        $this->app->singleton('localizator', function () {
           return new Localizator(
               $this->app->basePath().'/lang',
               config('app.locale', 'en'),
               config('app.fallback_locale', 'en'),
           );
        });
    }
}