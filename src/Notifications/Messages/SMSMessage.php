<?php

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\Message;

class SMSMessage implements Message
{
    protected string $from = '';

    protected string $to = '';

    protected string $content = '';

    protected bool $isUnicode = false;

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function to(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function unicode(): static
    {
        $this->isUnicode = true;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'content' => $this->content
        ];
    }
}