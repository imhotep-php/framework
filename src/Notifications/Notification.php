<?php

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\Notification as NotificationContract;
use Imhotep\Notifications\Messages\MailMessage;

class Notification implements NotificationContract
{
    public string $id;

    public string $locale;

    public function locale(string $locale = null): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function via($recipient): array
    {
        return [];
    }

    public function shouldSend($recipient, string $channel): bool
    {
        return true;
    }

    public function toArray($recipient): array
    {
        return [];
    }

    /*
    public function toMail()
    {
        return (new MailMessage())
            ->line('')
            ->action('', '')
            ->line('');
    }
    */
}