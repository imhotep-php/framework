<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasSoftDeletes
{
    protected string $deletedAtColumn = 'deleted_at';

    protected bool $softDeletes = true;

    protected bool $trashed = false;

    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    public function isTrashed(): bool
    {
        return $this->trashed;
    }
}