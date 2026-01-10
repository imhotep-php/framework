<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Events;

use Imhotep\Contracts\Notifications\INotification;

class NotificationSkipped
{
    public function __construct(
        public mixed         $recipient,
        public INotification $notification,
        public string        $channel
    ) {}
}