<?php

declare(strict_types=1);

namespace Imhotep\Log\Handlers;

use Imhotep\Log\LogRecord;

class TelegramHandler extends AbstractHandler
{
    protected string $token;

    protected int $chatId;

    public function __construct(string $token, int $chatId, int|string $level)
    {
        parent::__construct($level);

        $this->token = $token;
        $this->chatId = $chatId;
    }

    public function handle(LogRecord $record): bool
    {
        return $this->send($this->getFormatter()->format($record));
    }

    protected function send(string $message): bool
    {
        $params = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return true;
    }
}