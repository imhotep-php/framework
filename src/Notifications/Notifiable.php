<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Support\Str;

trait Notifiable
{
    public function notify(INotification $notification): void
    {
        app(ChannelManager::class)->send($this, $notification);
    }

    public function notifyNow(INotification $notification): void
    {
        app(ChannelManager::class)->sendNow($this, $notification);
    }

    public function routeNotificationFor(string $channel, ?INotification $notification = null): mixed
    {
        $method = 'routeNotificationFor'.Str::studly($channel);

        if (method_exists($this, $method)) {
            return $this->{$method}($notification);
        }

        return match ($channel) {
            'database' => $this->notifications(),
            'mail' => $this->email,
            'sms' => $this->phone,
            'telegram' => $this->telegram_chat_id,
            default => null,
        };
    }

    public function notifications(): NotificationRepository
    {
        return app(NotificationRepository::class)->for($this)->latest();
    }

    public function readNotifications(): NotificationRepository
    {
        return $this->notifications()->read();
    }

    public function unreadNotifications(): NotificationRepository
    {
        return $this->notifications()->unread();
    }
}