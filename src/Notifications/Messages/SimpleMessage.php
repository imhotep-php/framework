<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\INotificationMessage;

class SimpleMessage implements INotificationMessage
{
    public function __construct(
        public array $data = []
    ) {}

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}