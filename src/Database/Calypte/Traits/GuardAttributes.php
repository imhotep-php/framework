<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte\Traits;

use Imhotep\Database\Calypte\Builder;

trait GuardAttributes
{
    protected static $unguarded = false;

    protected static $databaseColumns = [];

    protected array $guarded = ['*'];

    protected array $fillable = [];

    protected function totallyGuarded(): bool
    {
        return (count($this->fillable) === 0 && $this->guarded == ['*']);
    }

    protected function isFillable($key): bool
    {
        if (self::$unguarded) {
            return true;
        }

        if (in_array($key, $this->guarded)) {
            return false;
        }

        if (in_array($key, $this->fillable)) {
            return true;
        }

        return false;
    }

    protected function isGuarded($key): bool
    {
        if (empty($this->guarded)) {
            return false;
        }

        return ($this->guarded == ['*']) // All column is guarded
            || in_array($key, $this->guarded) // Column exist in $guarded
            || ! $this->isDatabaseColumn($key); // Column is not exists in database
    }

    protected function isDatabaseColumn($key): bool
    {
        $className = get_class($this);

        if (! isset(static::$databaseColumns[$className])) {
            $columns = $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->getTable());

            if (empty($columns)) {
                return false;
            }

            static::$databaseColumns[$className] = $columns;
        }

        return in_array($key, static::$databaseColumns[$className]);
    }


}