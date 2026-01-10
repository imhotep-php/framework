<?php declare(strict_types = 1);

namespace Imhotep\Notifications;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Repository\Repository;
use Imhotep\Database\Utils\MorphHelper;

class NotificationRepository extends Repository
{
    protected string $model = NotificationModel::class;

    public function __construct(
        protected IConfigRepository $config
    ) {
        $this->table = $config->string('table', 'notifications');
        $this->connection = $config->string('connection');
    }

    public function create(array $attributes): ?object
    {
        return parent::create(
            MorphHelper::prepare('recipient', $attributes)
        );
    }

    protected function scopeFor(Builder $query, mixed $recipient): void
    {
        $query->whereMorph('recipient', $recipient);
    }

    protected function scopeRead(Builder $query): void
    {
        $query->whereNotNull('read_at');
    }

    protected function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function markAsRead(string|array $ids = null): bool
    {
        $query = $this->query();

        if (! empty($ids)) {
            if (is_string($ids)) {
                $query->where('id', $ids);
            } elseif (is_array($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $now = date('Y-m-d H:i:s');

        return $query->whereNull('read_at')->update([
            'read_at' => $now,
            'updated_at' => $now,
        ]) > 0;
    }
}