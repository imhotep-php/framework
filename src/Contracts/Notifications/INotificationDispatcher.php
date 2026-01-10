<?php declare(strict_types=1);

namespace Imhotep\Contracts\Notifications;

interface INotificationDispatcher
{
    public function channel(?string $name = null): INotificationDriver;

    public function send(mixed $recipients, INotification $notification): void;

    public function sendNow(mixed $recipients, INotification $notification, array $channels = null): void;
}