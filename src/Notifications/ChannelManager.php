<?php declare(strict_types=1);

namespace Imhotep\Notifications;

use Closure;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\DriverManager;
use Imhotep\Contracts\Notifications\INotificationDispatcher;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Notifications\Drivers\DatabaseDriver;
use Imhotep\Notifications\Drivers\FcmDriver;
use Imhotep\Notifications\Drivers\MailDriver;
use Imhotep\Notifications\Drivers\SmsDriver;
use Imhotep\Notifications\Drivers\TelegramDriver;

class ChannelManager extends DriverManager implements INotificationDispatcher
{
    protected ?string $locale = null;

    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function send(mixed $recipients, INotification $notification): void
    {
        (new NotificationSender($this, $this->container['events'], $this->container['queue'], $this->locale))
            ->send($recipients, $notification);
    }

    public function sendNow(mixed $recipients, INotification $notification, array $channels = null): void
    {
        (new NotificationSender($this, $this->container['events'], $this->container['queue'], $this->locale))
            ->sendNow($recipients, $notification, $channels);
    }

    public function route(string $driver, mixed $route): AnonymousRecipient
    {
        return (new AnonymousRecipient($this))->route($driver, $route);
    }

    public function routes(array $routes): AnonymousRecipient
    {
        $recipient = new AnonymousRecipient($this);

        foreach ($routes as $driver => $route) {
            $recipient->route($driver, $route);
        }

        return $recipient;
    }

    public function channel(?string $name = null): INotificationDriver
    {
        return $this->driver($name);
    }

    public function driver(?string $driver = null, array|Closure $parameters = []): INotificationDriver
    {
        if (is_null($driver)) {
            $driver = $this->config->getOrFail('notifications.default');
        }

        $channelDriver = $this->config->stringOrFail(
            "notifications.channels.$driver.driver",
            'Notifications channel driver [:key] not configured.'
        );

        $channelConfig = $this->config->subset("notifications.channels.$driver");

        return parent::driver($channelDriver, [$channelConfig]);
    }

    protected function createDatabaseDriver(IConfigRepository $config): INotificationDriver
    {
        return new DatabaseDriver($config, $this->container['db']);
    }

    protected function createMailDriver(IConfigRepository $config): INotificationDriver
    {
        return new MailDriver($config);
    }

    protected function createTelegramDriver(IConfigRepository $config): INotificationDriver
    {
        return new TelegramDriver($config);
    }

    protected function createSmsDriver(IConfigRepository $config): INotificationDriver
    {
        return new SmsDriver($config);
    }

    protected function createFcmDriver(IConfigRepository $config): INotificationDriver
    {
        return new FcmDriver($config);
    }

    public function getDefaultChannel(): string
    {
        return $this->getDefaultDriver();
    }

    public function setDefaultChannel(string $channel): static
    {
        return $this->setDefaultDriver($channel);
    }

    public function getDefaultDriver(): string
    {
        return $this->config->stringOrFail('notifications.default');
    }

    public function setDefaultDriver(string $driver): static
    {
        $this->config['notifications.default'] = $driver;

        return $this;
    }
}