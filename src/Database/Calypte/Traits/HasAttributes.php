<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte\Traits;

trait HasAttributes
{
    protected array $attributes = [];

    protected array $originals = [];

    protected array $changes = [];

    protected array $casts = [];

    protected function getAttributes()
    {
        return $this->attributes;
    }

    protected function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    protected function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;

        $this->changes[$key] = $this->originalIsEquivalent($key);
    }

    protected function setRawAttribute($attributes, bool $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginals();
        }
    }


    protected function syncOriginals(): void
    {
        $this->originals = $this->attributes;
    }

    protected function originalIsEquivalent($key): bool
    {
        if (! array_key_exists($key, $this->originals)) {
            return false;
        }

        $attribute = $this->attributes[$key];
        $original = $this->originals[$key];

        if ($attribute === $original) {
            return true;
        }
        elseif (is_null($attribute)) {
            return false;
        }

        return false;
    }

    protected function isChanged(){
        return in_array(true, $this->changes);
    }

    protected function getChanges()
    {
        $changes = [];
        foreach ($this->changes as $key => $state) {
            if (! $state) continue;

            $changes[$key] = $this->attributes[$key];
        }

        return $changes;
    }

    public function only(mixed $keys): array
    {
        $result = [];

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            $result[$key] = $this->getAttribute($key);
        }

        return $result;
    }
}