<?php

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\Notification as NotificationContract;
use Imhotep\Contracts\Queue\ShouldQueue;
use Imhotep\Queue\QueueManager;
use Imhotep\Support\Str;

class NotificationSender
{
    //protected string $defaultChannel = 'mail';

    //protected ?string $locale = null;

    //protected $manager;

    //protected $events;

    public function __construct(
        protected $manager,
        protected QueueManager $queue
    ) {}

    public function send($recipients, NotificationContract $notification): void
    {
        if ($notification instanceof ShouldQueue) {
            $this->sendQueue($recipients, $notification);
            return;
        }

        $this->sendNow($recipients, $notification);
    }

    public function sendNow($recipients, NotificationContract $notification, array $channels = null): void
    {
        $recipients = $this->formatRecipients($recipients);

        foreach ($recipients as $recipient) {
            if (empty($viaChannels = $channels ?: $notification->via($recipient))) {
                continue;
            }

            foreach ($viaChannels as $channel) {
                $this->sendToRecipient($recipient, $notification, $channel);
            }
        }
    }

    protected function sendToRecipient($recipient, NotificationContract $notification, $channel): void
    {
        if (empty($notification->id)) {
            $notification->id = Str::uuid();
        }

        if (! $notification->shouldSend($recipient, $channel)) {
            return;
        }

        $this->manager->driver($channel)->send($recipient, $notification);
    }

    protected function sendQueue($recipients, NotificationContract $notification): void
    {
        $recipients = $this->formatRecipients($recipients);

        foreach ($recipients as $recipient) {
            foreach ($notification->via($recipient) as $channel) {
                $this->queue->dispatch(new QueuedNotification($recipients, $notification, [$channel]));
            }
        }


    }

    protected function formatRecipients($recipients): array
    {
        if (! is_array($recipients)) {
            return [$recipients];
        }

        return $recipients;
    }
}