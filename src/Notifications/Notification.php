<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Contracts\Queue\ShouldQueue;

abstract class Notification implements INotification
{
    public ?string $id = null;

    public ?string $locale = null;

    /**
     * Устанавливаем язык уведомления
     *
     * @param string|null $locale
     * @return $this
     */
    public function locale(?string $locale = null): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Получаем каналы отправки для указанного получателя
     *
     * @param mixed $recipient
     * @return array
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function via(mixed $recipient): array
    {
        return [];
    }

    /**
     * Можно ли отправить данное уведомление получателю по указанному каналу
     *
     * @param mixed $recipient
     * @param string $channel
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function shouldSend(mixed $recipient, string $channel): bool
    {
        return true;
    }

    /**
     * Данные уведомления
     *
     * @param string $recipient
     * @return array
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function toArray(mixed $recipient): array
    {
        return [];
    }

    public function shouldBeQueued(): bool
    {
        return $this instanceof ShouldQueue;
    }
}