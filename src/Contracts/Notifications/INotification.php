<?php declare(strict_types=1);

namespace Imhotep\Contracts\Notifications;

interface INotification
{
    public function locale(string $locale = null): static;

    public function via(string $recipient): array;

    public function shouldSend(mixed $recipient, string $channel): bool;

    public function shouldBeQueued(): bool;
}