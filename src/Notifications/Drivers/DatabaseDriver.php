<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Drivers;

use DateTimeInterface;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Database\ConnectionResolver;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Notifications\AnonymousRecipient;
use Imhotep\Notifications\NotificationRepository;
use RuntimeException;

class DatabaseDriver implements INotificationDriver
{
    protected ?string $connectionName;

    protected string $tableName;

    public function __construct(protected IConfigRepository $config, protected ConnectionResolver $db)
    {
        $this->connectionName = $config->string('connection');
        $this->tableName = $config->string('table', 'notifications');
    }

    public function notifications(): NotificationRepository
    {
        return new NotificationRepository($this->config);
    }

    public function send(mixed $recipient, INotification $notification): bool
    {
        $payload = $this->buildPayload($recipient, $notification);

        if (is_object($recipient) && method_exists($recipient, 'routeNotificationFor')) {
            return $recipient->routeNotificationFor('database', $notification)->create($payload) !== null;
        }

        return $this->notifications()->create($payload) !== null;
    }

    protected function buildPayload(mixed $recipient, INotification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            $data = $notification->toDatabase($recipient);
        }
        elseif (method_exists($notification, 'toArray')) {
            $data = $notification->toArray($recipient);
        }
        else {
            throw new RuntimeException(sprintf(
                "Notification [%s] must implement [toDatabase] or [toArray] method.",
                get_class($notification)
            ));
        }

        if (! is_array($data)) {
            throw new RuntimeException(sprintf(
                "Method [toDatabase] or [toArray] must return array, %s given",
                gettype($data)
            ));
        }

        return [
            'id' => $notification->id,
            'recipient' => $recipient,
            'type' => $this->getNotificationType($notification, $recipient),
            'data' => $data,
            'read_at' => $this->getNotificationReadAt($notification, $recipient),
        ];
    }

    protected function getNotificationType(INotification $notification, mixed $recipient): string
    {
        return method_exists($notification, 'databaseType')
            ? $notification->databaseType($recipient)
            : get_class($notification);
    }

    protected function getNotificationReadAt(INotification $notification, mixed $recipient): ?string
    {
        if (! method_exists($notification, 'databaseReadAt')) {
            return null;
        }

        $readAt = $notification->databaseReadAt($recipient);

        if (is_null($readAt)) {
            return null;
        }

        if (is_int($readAt)) {
            return date('Y-m-d H:i:s', $readAt);
        }

        if ($readAt instanceof DateTimeInterface) {
            return $readAt->format('Y-m-d H:i:s');
        }

        if (is_string($readAt)) {
            return date('Y-m-d H:i:s', strtotime($readAt));
        }

        throw new RuntimeException(sprintf(
            "Method [databaseReadAt] must be return null, timestamp, DateTime or string, [%s] given",
            gettype($readAt)
        ));
    }
}