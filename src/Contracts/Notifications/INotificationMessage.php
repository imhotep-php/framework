<?php declare(strict_types=1);

namespace Imhotep\Contracts\Notifications;

interface INotificationMessage
{
    public function toArray(): array;
}