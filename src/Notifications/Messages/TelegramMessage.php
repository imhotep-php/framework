<?php

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\Message;

class TelegramMessage implements Message
{
    //protected string $chatId = '';

    protected ?string $parseMode = null;

    protected ?bool $disableWebPagePreview = null;

    protected string $greeting = '';

    protected array $lines = [];

    /*
    public function chatId(string $chatId = null): static|string
    {
        if (is_null($chatId)) {
            return $this->chatId;
        }

        $this->chatId = $chatId;

        return $this;
    }
    */

    public function parseMode(string $mode = null): static|string|null
    {
        if (is_null($mode)) {
            return $this->parseMode;
        }

        if (in_array($mode, ['MarkdownV2', 'Markdown', 'HTML'])) {
            $this->parseMode = $mode;
        }

        return $this;
    }

    public function disableWebPagePreview(bool $state = null): static|bool|null
    {
        if (is_null($state)) {
            return $this->disableWebPagePreview;
        }

        $this->disableWebPagePreview = $state;

        return $this;
    }

    public function greeting(string $greeting): static
    {
        $this->greeting = $greeting;

        return $this;
    }

    public function line(string $line): static
    {
        $this->lines[] = $line;

        return $this;
    }

    public function lineIf(bool $boolean, string $line): static
    {
        if ($boolean) {
            $this->with($line);
        }

        return $this;
    }

    public function lines(array $lines): static
    {
        foreach ($lines as $line) {
            $this->with($line);
        }

        return $this;
    }

    public function linesIf(bool $boolean, array $lines): static
    {
        if ($boolean) {
            $this->lines($lines);
        }

        return $this;
    }

    public function with(mixed $line): static
    {
        if (is_string($line)) {
            $this->lines[] = $line;
        }

        return $this;
    }

    public function text(): string
    {
        $text = '';

        if ($this->parseMode === 'HTML') {
            $text.= "<strong>{$this->greeting}</strong>";

            foreach ($this->lines as $line) {
                if (! empty($text)) $text.= PHP_EOL;

                $text.= $line;
            }

            return $text;
        }

        // Markdown
        $text = $this->greeting;

        foreach ($this->lines as $line) {
            $text.= $line."\n";
        }

        return $text;
    }

    public function toArray(): array
    {
        return [
            'greeting' => $this->greeting,
            'lines' => $this->lines,
        ];
    }
}