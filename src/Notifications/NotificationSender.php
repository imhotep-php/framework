<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Container\Container;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Contracts\Localization\HasLocalePreference;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Notifications\Events\NotificationFailed;
use Imhotep\Notifications\Events\NotificationSending;
use Imhotep\Notifications\Events\NotificationSent;
use Imhotep\Notifications\Events\NotificationSkipped;
use Imhotep\Queue\QueueManager;
use Imhotep\Support\Str;
use Throwable;

class NotificationSender
{
    public function __construct(
        protected ChannelManager $manager,
        protected ?Dispatcher $events = null,
        protected ?QueueManager $queue = null,
        protected ?string $locale = null,
    ) {}

    public function send(mixed $recipients, INotification $notification): void
    {
        if ($notification->shouldBeQueued()) {
            $this->sendQueue($recipients, $notification);
        } else {
            $this->sendNow($recipients, $notification);
        }
    }

    public function sendNow(mixed $recipients, INotification $notification, ?array $channels = null): void
    {
        $recipients = $this->formatRecipients($recipients);

        foreach ($recipients as $recipient) {
            if (empty($viaChannels = $channels ?: $notification->via($recipient))) {
                continue;
            }

            $cloneNotification = clone $notification;
            $cloneNotification->id ??= Str::uuid();

            $locale = $this->preferredLocale($recipient, $notification);

            $this->withLocale($locale, function() use ($recipient, $cloneNotification, $viaChannels) {
                foreach ($viaChannels as $channel) {
                    if ($channel === 'database' && $recipient instanceof AnonymousRecipient) {
                        continue;
                    }

                    $this->sendToRecipient($recipient, clone $cloneNotification, $channel);
                }
            });
        }
    }

    protected function sendToRecipient(mixed $recipient, INotification $notification, string $channel): void
    {
        if (! $this->shouldSendNotification($recipient, $notification, $channel)) {
            $this->events?->dispatch(new NotificationSkipped($recipient, $notification, $channel));

            return;
        }

        try {
            $response = $this->manager->channel($channel)->send($recipient, $notification);
        }
        catch (Throwable $e) {
            $this->events?->dispatch(new NotificationFailed($recipient, $notification, $channel, $e));

            throw $e;
        }

        $this->events?->dispatch(new NotificationSent($recipient, $notification, $channel, $response));
    }

    protected function shouldSendNotification(mixed $recipient, INotification $notification, string $channel): bool
    {
        if (! $notification->shouldSend($recipient, $channel)) {
            return false;
        }

        return $this->events?->until(
            new NotificationSending($recipient, $notification, $channel)
        ) !== false;
    }

    protected function sendQueue(mixed $recipients, INotification $notification): void
    {
        $recipients = $this->formatRecipients($recipients);

        foreach ($recipients as $recipient) {
            $cloneNotification = clone $notification;
            $cloneNotification->id ??= Str::uuid();

            foreach ($notification->via($recipient) as $channel) {
                $this->queue?->dispatch(new QueuedNotification($recipient, clone $cloneNotification, [$channel]));
            }
        }
    }

    protected function preferredLocale(mixed $recipient, INotification $notification): ?string
    {
        if ($notification->locale) {
            return $notification->locale;
        }

        if ($this->locale) {
            return $this->locale;
        }

        if ($recipient instanceof HasLocalePreference) {
            return $recipient->preferredLocale();
        }

        return null;
    }

    protected function withLocale(?string $locale, callable $callback): mixed
    {
        if (is_null($locale)) {
            return $callback();
        }

        $app = Container::getInstance();

        $original = $app->locale();

        try {
            $app->setLocale($locale);

            return $callback();
        }
        finally {
            $app->setLocale($original);
        }
    }

    protected function formatRecipients(mixed $recipients): array
    {
        return array_filter(is_array($recipients) ? $recipients : [$recipients]);
    }
}