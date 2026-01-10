<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

use Imhotep\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use function PHPUnit\Framework\returnArgument;

trait HasAttributes
{
    protected static array $attributeMutatorCache = [];

    protected array $attributes = [];

    protected array $originalAttributes = [];

    protected array $changedAttributes = [];

    protected array $cachedAttributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getRawAttributes(): array
    {
        return $this->attributes;
    }

    public function setRawAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }


    public function getAttribute(string $key): mixed
    {
        // Order: method mutator | attribute mutator | casts | changes | attributes

        $value = $this->attributes[$key] ?? null;

        if ($this->hasGetMethodMutator($key)) {
            return $this->getMethodMutatorValue($key, $value);
        }

        if ($this->hasGetAttributeMutator($key)) {
            return $this->getAttributeMutatorValue($key, $value);
        }

        if ($this->hasCast($key)) {
            return $this->castValueForGet($key, $value);
        }

        // related attributes

        return $value;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        if ($this->hasSetMethodMutator($key)) {
            $this->setMethodMutatorValue($key, $value);

            return $this;
        }
        elseif ($this->hasSetAttributeMutator($key)) {
            $this->setAttributeMutatorValue($key, $value);

            return $this;
        }
        elseif ($this->hasCast($key)) {
            $value = $this->castValueForSet($key, $value);
        }

        $this->attributes[$key] = $value;

        if (! $this->originalIsEquivalent($key)) {
            $this->changedAttributes[$key] = $value;
        } else {
            unset($this->changedAttributes[$key]);
        }

        return $this;
    }

    public function getRawAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setRawAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }


    // Method mutators
    protected function hasGetMethodMutator(string $key): bool
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    protected function hasSetMethodMutator(string $key): bool
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    protected function getMethodMutatorValue(string $key, mixed $value): mixed
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    protected function setMethodMutatorValue(string $key, mixed $value): void
    {
        $value = $this->{'set'.Str::studly($key).'Attribute'}($value);

        if (! is_array($value)) {
            $value = [$key => $value];
        }

        foreach ($value as $k => $v) {
            $this->attributes[$k] = $v;

            if (! $this->originalIsEquivalent($k)) {
                $this->changedAttributes[$k] = $v;
            } else {
                unset($this->changedAttributes[$k]);
            }
        }
    }


    // Attribute mutators
    protected function hasGetAttributeMutator(string $key): bool
    {
        return $this->hasAttributeMutator($key, 'get');
    }

    protected function hasSetAttributeMutator(string $key): bool
    {
        return $this->hasAttributeMutator($key, 'set');
    }

    protected function hasAttributeMutator(string $key, string $type): bool
    {
        $method = Str::camel($key);

        if (isset(static::$attributeMutatorCache[static::class][$method][$type])) {
            return static::$attributeMutatorCache[static::class][$method][$type];
        }

        if (! method_exists($this, $method)) {
            return static::$attributeMutatorCache[static::class][$method][$type] = false;
        }

        $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        return static::$attributeMutatorCache[static::class][$method][$type] =
            $returnType instanceof ReflectionNamedType &&
            $returnType->getName() === Attribute::class &&
            is_callable($this->$method()->$type);
    }

    protected function getAttributeMutatorValue(string $key, mixed $value): mixed
    {
        if (array_key_exists($key, $this->cachedAttributes)) {
            return $this->cachedAttributes[$key];
        }

        $attribute = $this->{Str::camel($key)}();

        $value = call_user_func($attribute->get, $value, $this->attributes);

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->cachedAttributes[$key] = $value;
        } else {
            unset($this->cachedAttributes[$key]);
        }

        return $value;
    }

    protected function setAttributeMutatorValue(string $key, mixed $value): void
    {
        $attribute = $this->{Str::camel($key)}();

        $value = call_user_func($attribute->set, $value, $this->attributes);

        if (! is_array($value)) {
            $value = [$key => $value];
        }

        foreach ($value as $k => $v) {
            $this->attributes[$k] = $v;

            if (! $this->originalIsEquivalent($k)) {
                $this->changedAttributes[$k] = $v;
            } else {
                unset($this->changedAttributes[$k]);
            }

            unset($this->cachedAttributes[$k]);
        }
    }


    // Casts
    protected function hasCast(string $key): bool
    {
        return array_key_exists($key, $this->casts);
    }

    protected function castValueForGet(string $key, mixed $value): mixed
    {
        $type = $this->casts[$key];

        switch ($type) {
            case 'string':
                return strval($value);

            case 'int':
            case 'integer':
                return intval($value);

            case 'real':
            case 'float':
            case 'double':
                return floatval($value);

            case 'bool':
            case 'boolean':
                return boolval($value);

            case 'array':
            case 'json':
                return json_decode($value, true);

            case 'object':
                return json_decode($value);

            case 'pg_array_int':
            case 'pg_array_float':
            case 'pg_array_string':
                return $this->decodePgArray($value, substr($type, 9));

            case 'date':
                return $this->decodeDate($value);

            case 'datetime':
                return $this->decodeDatetime($value);

            case 'immutable_date':
                return $this->decodeDate($value, true);

            case 'immutable_datetime':
                return $this->decodeDatetime($value, true);

            case 'timestamp':
                return $this->decodeTimestamp($value);
        }

        if (str_starts_with($type, 'decimal')) {
            return $this->decodeDecimal($value, (int)substr($type, 8));
        }

        return $value;
    }

    protected function castValueForSet(string $key, mixed $value): ?string
    {
        $type = $this->casts[$key];

        if (is_null($value)) {
            return null;
        }

        if ($type === 'string') {
            return (string)$value;
        }

        if (in_array($type, ['int', 'integer'])) {
            if (is_int($value)) {
                return (string)$value;
            }

            throw new \InvalidArgumentException(sprintf(
                "Value '%s' for field '%s' must be an integer.",
                $value, $key
            ));
        }

        if (in_array($type, ['float', 'double', 'real'])) {
            if (is_float($value)) {
                return (string)$value;
            }

            throw new \InvalidArgumentException(sprintf(
                "Value '%s' for field '%s' must be a float.",
                $value, $key
            ));
        }

        if (in_array($type, ['array', 'json', 'object'])) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            throw new \InvalidArgumentException(sprintf(
                $type === 'object'
                    ? "Value '%s' for field '%s' must be an object."
                    : "Value '%s' for field '%s' must be an array.",
                $value, $key
            ));
        }

        if (in_array($type, ['bool', 'boolean'])) {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            throw new \InvalidArgumentException(sprintf(
                "Value '%s' for field '%s' must be a boolean.",
                $value, $key
            ));
        }

        return (string)$value;
    }

    protected function decodePgArray(mixed $value, string|null $type = null): array
    {
        if (is_null($value) || $value === '{}') return [];

        $value = explode(",", trim($value, '{}'));

        if ($type === 'int') {
            array_walk($value, fn (&$item) => $item = (int)$item);
        }
        elseif ($type === 'float') {
            array_walk($value, fn (&$item) => $item = (float)$item);
        }
        elseif ($type === 'string') {
            array_walk($value, fn (&$item) => $item = trim($item, "'"));
        }

        return $value;
    }

    protected function encodePgArray(mixed $value, string $type): string
    {
        if (is_array($value) && count($value) > 0) {
            $value = array_map(function ($val) {
                return is_string($val) ? "'$val'" : $val;
            }, $value);

            return "{".implode(",", $value)."}";
        }

        return "{}";
    }

    protected function decodeDecimal(mixed $value, int $decimals): ?float
    {
        if (is_null($value)) {
            return null;
        }

        return (float)number_format((float)$value, $decimals, '.', '');
    }

    protected function decodeDate(mixed $value, bool $immutable = false): ?\DateTimeInterface
    {
        if (is_null($value)) {
            return $value;
        }

        if ($immutable) {
            return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value)->setTime(0, 0);
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $value)->setTime(0, 0);
    }

    protected function decodeDatetime(mixed $value, bool $immutable = false): ?\DateTimeInterface
    {
        if (is_null($value)) {
            return null;
        }

        if ($immutable) {
            return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $value);
    }

    protected function decodeTimestamp(mixed $value): ?int
    {
        if (is_null($value)) {
            return null;
        }

        return $this->decodeDatetime($value)->getTimestamp();
    }




    public function syncOriginals(): static
    {
        $this->originalAttributes = $this->attributes;

        return $this;
    }


    public function isChanged(?string $attribute = null): bool
    {
        if (is_string($attribute)) {
            return $this->originalIsEquivalent($attribute) === false;
        }

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

    public function resetChanges(): static
    {
        foreach ($this->changedAttributes as $name => $value) {
            $this->attributes[$name] = $value;
        }

        $this->changedAttributes = [];

        return $this;
    }

    protected function originalIsEquivalent(string $key): bool
    {
        if (! array_key_exists($key, $this->originalAttributes)) {
            return false;
        }

        $original = $this->originalAttributes[$key] ?? null;
        $value = $this->attributes[$key] ?? null;

        return $original === $value;
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

    public function except(mixed $keys): array
    {
        $result = $this->getAttributes();

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            unset($result[$key]);
        }

        return $result;
    }
}