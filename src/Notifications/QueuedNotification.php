<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\INotification as INotification;
use Imhotep\Contracts\Queue\ShouldQueue;

class QueuedNotification implements ShouldQueue
{
    public mixed $recipient;

    public INotification $notification;

    public ?array $channels;

    public ?int $tries;

    public ?int $timeout;

    public function __construct(mixed $recipient, INotification $notification, ?array $channels = null)
    {
        $this->channels = $channels;
        $this->recipient = $recipient;
        $this->notification = $notification;
    }

    public function handle(ChannelManager $manager): void
    {
        $manager->sendNow($this->recipient, $this->notification, $this->channels);
    }
}