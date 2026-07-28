<?php declare(strict_types=1);

namespace Imhotep\Events;

use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Framework\Providers\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected array $listen = [];

    protected array $subscribe = [];

    public array $aliases = [
        'events' => [Events::class, Dispatcher::class],
    ];

    public function register(): void
    {
        $this->app->singleton('events', function ($app) {
            return new Events($app);
        });
    }

    public function boot(): void
    {
        foreach ($this->listen as $event => $listener) {
            $this->app['events']->listen($event, $listener);
        }

        foreach ($this->subscribe as $subscriber) {
            $this->app['events']->subscribe($subscriber);
        }

    }
}