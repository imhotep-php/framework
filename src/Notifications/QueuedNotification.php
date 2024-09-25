<?php

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\Notification;
use Imhotep\Contracts\Queue\ShouldQueue;

class QueuedNotification implements ShouldQueue
{
    public array $recipients;

    public Notification $notification;

    public ?array $channels;

    public ?int $tries;

    public ?int $timeout;

    public function __construct(array $recipients, Notification $notification, array $channels = null)
    {
        $this->channels = $channels;
        $this->recipients = $recipients;
        $this->notification = $notification;
    }

    public function handle(ChannelManager $manager)
    {
        $manager->sendNow($this->recipients, $this->notification, $this->channels);
    }
}