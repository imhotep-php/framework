<?php

declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Framework\Providers\ServiceProvider;
use Imhotep\Http\Request;

class ValidationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'validator' => Factory::class
    ];


    public function register()
    {
        $this->app->singleton('validator', function() {
            return new Factory(app('localizator'));
        });

        Request::macro('validate', function (array $rules, array $messages = []) {
            return validator()->validate($this->all(), $rules, $messages);
        });
    }
}