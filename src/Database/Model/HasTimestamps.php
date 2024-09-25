<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasTimestamps
{
    protected string $createdAt = 'created_at';

    protected string $updatedAt = 'updated_at';

    protected bool $timestamps = true;
}