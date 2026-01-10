<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Events;

use Imhotep\Contracts\Notifications\INotification;
use Throwable;

class NotificationFailed
{
    public function __construct(
        public mixed         $recipient,
        public INotification $notification,
        public string        $channel,
        public Throwable     $exception
    ) {}
}