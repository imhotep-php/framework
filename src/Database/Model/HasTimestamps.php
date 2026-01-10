<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasTimestamps
{
    protected bool $timestamps = true;

    protected string $createdAtColumn = 'created_at';

    protected string $updatedAtColumn = 'updated_at';

    public function getCreatedAtColumn(): string
    {
        return $this->createdAtColumn;
    }

    public function getUpdatedAtColumn(): string
    {
        return $this->updatedAtColumn;
    }

    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    public function updateTimestamps(): void
    {
        if (! $this->timestamps) return;

        $now = date('Y-m-d H:i:s');

        $createdAt = $this->getAttribute($this->createdAtColumn);
        if (is_null($createdAt)) {
            $this->setAttribute($this->createdAtColumn, $now);
        }

        $this->setAttribute($this->updatedAtColumn, $now);
    }
}