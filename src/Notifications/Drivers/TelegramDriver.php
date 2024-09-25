<?php

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Notifications\Notification;
use Imhotep\Contracts\Notifications\NotificationException;

class TelegramDriver extends AbstractDriver
{
    protected string $token = '';

    protected string $parseMode = '';

    protected bool $disableWebPagePreview = true;

    public function __construct(array $config)
    {
        if (empty($config['token'])) {
            throw new NotificationException("Property [token] is not configured for driver [telegram]");
        }

        $this->token = $config['token'];
        $this->parseMode = $config['parse_mode'] ?? 'MarkdownV2';
        $this->disableWebPagePreview = (bool)$config['disable_web_page_preview'] ?? true;
    }

    public function send($recipient, Notification $notification): bool
    {
        if (! method_exists($notification, 'toTelegram')) {
            throw new NotificationException("Method [toTelegram] not exists");
        }

        $message = $notification->toTelegram();

        if ($message->parseMode() === null) {
            $message->parseMode($this->parseMode);
        }

        if ($message->disableWebPagePreview() === null) {
            $message->parseMode($this->disableWebPagePreview);
        }

        $params = [
            'chat_id' => $recipient,
            'text' => $message->text(),
            'parse_mode' => $message->parseMode(),
            'disable_web_page_preview' => $message->disableWebPagePreview()
        ];

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($json = json_decode($result)) {
            if ($json->ok) return true;
        }

        return false;
    }
}