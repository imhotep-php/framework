<?php

namespace Imhotep\Notifications;

use Imhotep\Container\Container;
use Imhotep\Contracts\Notifications\Notification as NotificationContract;
use Imhotep\Notifications\Drivers\AbstractDriver;
use Imhotep\Notifications\Drivers\SMTPDriver;
use Imhotep\Notifications\Drivers\TelegramDriver;

class ChannelManager
{
    protected array $channels = [];

    public function __construct(protected Container $app) {}

    public function send($recipients, NotificationContract $notification): void
    {
        (new NotificationSender($this, $this->app['queue']))->send($recipients, $notification);
    }

    public function sendNow($recipients, NotificationContract $notification, array $channels = null): void
    {
        (new NotificationSender($this, $this->app['queue']))->send($recipients, $notification, $channels);
    }

    public function channel(string $name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultChannel();
        }

        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        $config = $this->getChannelConfig($name);

        $createMethod = "create".ucfirst($config['driver'])."Driver";

        if (method_exists($this, $createMethod)) {
            return $this->channels[$name] = $this->{$createMethod}($config);
        }

        throw new \Exception("Notification driver [{$config['driver']}] is not configured.");
    }

    public function driver(string $name = null)
    {
        return $this->channel($name);
    }

    public function getDefaultChannel(): string
    {
        if ($default = $this->app['config']->get('notification.default')) {
            return $default;
        }

        throw new \Exception('Default channel is not configured.');
    }

    public function getChannelConfig(string $name): array
    {
        if ($config = $this->app['config']->get("notification.channels.{$name}")) {
            return $config;
        }

        throw new \Exception("Notification channel [{$name}] is not configured.");
    }

    protected function createSmtpDriver($config): AbstractDriver
    {
        return new SMTPDriver($config);
    }

    protected function createTelegramDriver(array $config): AbstractDriver
    {
        return new TelegramDriver($config);
    }
}