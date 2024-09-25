<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasSoftDeletes
{
    protected string $deletedAt = 'deleted_at';

    protected bool $softDelete = true;

    public function delete(): static
    {
        return $this->setAttribute($this->deletedAt, date('Y-m-d H:i:s'));
    }

    public function restore(): static
    {
        return $this->setAttribute($this->deletedAt, null);
    }
}