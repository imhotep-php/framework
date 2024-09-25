<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

use Imhotep\Support\Str;

trait HasAttributes
{
    protected static array $attributeMutatorCache = [];

    protected array $attributes = [];

    protected array $originals = [];

    protected array $casts = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        $key = Str::snake($key);

        if ($this->hasGetMutator($key)) {
            return $this->getMutatorValue($key);
        }

        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $key = Str::snake($key);

        if ($this->hasSetMutator($key)) {
            $this->setMutatorValue($key, $value);
        }
        else {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    protected function hasMutator(string $key): bool
    {
        $method = Str::camel($key);

        if (isset(static::$attributeMutatorCache[static::class][$method])) {
            return static::$attributeMutatorCache[static::class][$method];
        }

        if (! method_exists($this, $method)) {
            return static::$attributeMutatorCache[static::class][$method] = false;
        }

        $returnType = (new \ReflectionMethod($this, $method))->getReturnType();

        return static::$attributeMutatorCache[static::class][$method] =
            $returnType instanceof \ReflectionNamedType &&
            $returnType->getName() === Attribute::class;
    }

    protected function hasGetMutator(string $key): bool
    {
        if (! $this->hasMutator($key)) {
            return false;
        }

        return ! is_null($this->{Str::camel($key)}()->get);
    }

    protected function hasSetMutator(string $key): bool
    {
        if (! $this->hasMutator($key)) {
            return false;
        }

        return ! is_null($this->{Str::camel($key)}()->set);
    }

    protected function getMutatorValue(string $key): mixed
    {
        $attribute = $this->{Str::camel($key)}();

        return call_user_func($attribute->get,
            $this->attributes[$key] ?? null,
            $this->attributes
        );
    }

    protected function setMutatorValue(string $key, mixed $value): void
    {
        $attribute = $this->{Str::camel($key)}();

        $this->attributes[$key] = call_user_func($attribute->set, $value, $this->attributes);
    }



    public function syncOriginals(): void
    {
        $this->originals = $this->attributes;
    }

    public function isChanged(): bool
    {
        foreach ($this->attributes as $key => $val) {
            if (! $this->originalIsEquivalent($key)) {
                return true;
            }
        }

        return false;
    }

    public function getChanges(): array
    {
        return array_filter($this->attributes, function ($key) {
            return ! $this->originalIsEquivalent($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function originalIsEquivalent(string $key): bool
    {
        if (! array_key_exists($key, $this->originals)) {
            return false;
        }

        $attribute = $this->attributes[$key] ?? null;
        $original = $this->originals[$key] ?? null;

        if ($attribute === $original) {
            return true;
        }

        return false;
    }

    // Возвращает массив атрибутов по списку ключей
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