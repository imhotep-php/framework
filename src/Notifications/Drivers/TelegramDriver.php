<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Contracts\Notifications\INotification;
use RuntimeException;

class TelegramDriver implements INotificationDriver
{
    protected string $token = '';

    protected string $parseMode = '';

    protected bool $disableWebPagePreview = true;

    public function __construct(IConfigRepository $config)
    {
        $this->token = $config->stringOrFail('token');
        $this->parseMode = $config->string('parse_mode', 'MarkdownV2');
        $this->disableWebPagePreview = $config->bool('disable_web_page_preview', true);
    }

    public function send(mixed $recipient, INotification $notification): bool
    {
        if (! method_exists($notification, 'toTelegram')) {
            throw new RuntimeException("Method [toTelegram] not exists");
        }

        $recipientChatId = null;

        if (is_string($recipient)) {
            $recipientChatId = $recipient;
        }
        elseif (method_exists($recipient, 'routeNotificationFor')) {
            $recipientChatId = $recipient->routeNotificationFor('telegram', $notification);
        }

        if (is_null($recipientChatId)) {
            return false;
        }

        $message = $notification->toTelegram($recipient);

        if (is_null($message->parseMode())) {
            $message->parseMode($this->parseMode);
        }

        if (is_null($message->disableWebPagePreview())) {
            $message->disableWebPagePreview($this->disableWebPagePreview);
        }

        $params = [
            'chat_id' => $recipientChatId,
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