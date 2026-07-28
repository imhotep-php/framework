<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Contracts\Localization\ILocalizationLoader;
use Imhotep\Contracts\Localization\ILocalizator;
use Imhotep\Filesystem\Filesystem;
use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\Support\Str;

class LocalizationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'localizator' => [ILocalizator::class, Localizator::class],
        'localizator.loader' => [ILocalizationLoader::class, FileLoader::class]
    ];

    public function register(): void
    {
        $this->app->singleton('localizator.loader', function ($app) {
            return new FileLoader(new Filesystem(false), $app->basePath('lang'));
        });

        $this->app->singleton('localizator', function ($app) {
            $localizator = new Localizator(
               $app->get('localizator.loader'),
               config('app.locale', 'en'),
               config('app.fallback_locale', 'en'),
           );

            $localizator->addModifier('lcfirst', fn($value) => Str::lcfirst($value));
            $localizator->addModifier('ucfirst', fn($value) => Str::ucfirst($value));
            $localizator->addModifier('lower', fn($value) => Str::lower($value));
            $localizator->addModifier('upper', fn($value) => Str::upper($value));
            $localizator->addModifier('ucwords', fn($value) => Str::ucwords($value));
            $localizator->addModifier('slug', fn($value) => Str::slug($value));

           return $localizator;
        });
    }
}