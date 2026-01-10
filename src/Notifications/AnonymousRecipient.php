<?php declare(strict_types=1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Contracts\Notifications\INotificationDispatcher;
use InvalidArgumentException;

class AnonymousRecipient
{
    protected array $routes = [];

    public function __construct(
        protected INotificationDispatcher $dispatcher
    ) {}

    public function route(string $driver, mixed $route): static
    {
        if ($driver === 'database') {
            throw new InvalidArgumentException('The database channel does not support on-demand notifications.');
        }

        $this->routes[$driver] = $route;

        return $this;
    }

    public function notify(INotification $notification): void
    {
        $this->dispatcher->send($this, $notification);
    }

    public function notifyNow(INotification $notification): void
    {
        $this->dispatcher->send($this, $notification);
    }

    public function routeNotificationFor(string $driver): mixed
    {
        return $this->routes[$driver] ?? null;
    }

    public function getKey(): mixed
    {
        return null;
    }
}