<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasGuardAttributes
{
    protected static bool $unguarded = false;

    protected array $guarded = ['*'];

    protected array $fillable = [];

    protected function totallyGuarded(): bool
    {
        return (empty($this->fillable) && $this->guarded === ['*']);
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

        return ($this->guarded === ['*']) || in_array($key, $this->guarded);
    }
}