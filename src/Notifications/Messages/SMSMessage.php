<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\INotificationMessage;

class SmsMessage implements INotificationMessage
{
    public function __construct(
        public string $text,
        public ?string $from = null
    ) {}

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function toArray(): array
    {
        return [];
    }
}