<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Database\Model\Model;

/**
 * @property string $id
 * @property string $type
 * @property array $data
 * @property \DateTime|null $read_at
 * @property \DateTime|null $created_at
 * @property \DateTime|null $updated_at
 */
class NotificationModel extends Model
{
    protected string $keyType = 'string';

    protected array $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected array $guarded = [];
}