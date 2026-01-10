<?php declare(strict_types = 1);

namespace Imhotep\Contracts\Notifications;

interface INotificationDriver
{
    public function send($recipient, INotification $notification): mixed;
}