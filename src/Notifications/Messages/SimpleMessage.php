<?php

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\Message;

class SimpleMessage implements Message
{
    public function __construct(
        protected array $data
    )
    {}

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function toArray(): array
    {
        return [];
    }
}