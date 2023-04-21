<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte;

use ArrayAccess;
use Imhotep\Contracts\Arrayable;
use Imhotep\Contracts\Database\Calypte\Model as ModelContract;
use Imhotep\Contracts\Jsonable;
use Imhotep\Database\Calypte\Traits\ForwardCalls;
use Imhotep\Database\Calypte\Traits\HasAttributes;
use Imhotep\Database\Calypte\Traits\HasQuery;
use Imhotep\Database\Calypte\Traits\GuardAttributes;
use JsonSerializable;

class Model implements ModelContract, Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    use HasQuery, HasAttributes, GuardAttributes, ForwardCalls;

    protected string $keyName = 'id';

    protected string $keyType = 'int';

    protected bool $incrementing = true;

    protected bool $exists = false;

    protected bool $wasRecentlyCreated = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function newInstance()
    {

    }

    public function fill(array $attributes): static
    {
        if (count($attributes) === 0) {
            return $this;
        }

        $error = function (string|array $keys) {
            throw new \Exception(sprintf(
                'Add [%s] to fillable property to allow mass assignment on [%s].',
                implode(", ", (array)$keys), get_class($this)
            ));
        };

        if($this->totallyGuarded()){
            $error(
                (count($this->fillable) === 0)
                    ? array_keys($attributes)
                    : array_diff(array_keys($attributes), array_keys(array_flip($this->fillable)))
            );
        }

        foreach ($attributes as $key => $val) {
            if(! $this->isFillable($key)){
                $error($key);
            }

            $this->setAttribute($key, $val);
        }

        return $this;
    }

    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $data = $this->newQuery()->where($this->keyName, $this->getKey())->get();

        $this->setRawAttribute(

        );

        return $this;
    }

    public function save()
    {
        if ($this->exists) {
            $saved = $this->performUpdate();
        }
        else {
            $saved = $this->performInsert();
        }

        if($saved) $this->finishSave();

        return $saved;
    }

    protected function finishSave()
    {
        $this->syncOriginals();
    }

    protected function performInsert(): bool
    {
        $query = $this->newQuery();

        /*if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }*/

        $this->setAttribute('created_at', date("Y-m-d H:i:s"));
        $this->setAttribute('updated_at', date("Y-m-d H:i:s"));

        $attributes = $this->getAttributes();

        if ($this->incrementing) {
            $id = $query->insertGetId($attributes, $this->keyName);

            $this->setAttribute($this->keyName, $id);
        }
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        $this->exists = true;

        $this->wasRecentlyCreated = true;

        return true;
    }

    protected function performUpdate(): bool
    {
        if ($this->isChanged()) {
            $this->newQuery()
                ->where($this->keyName, $this->getKey())
                ->update($this->getChanges());
        }

        return true;
    }

    // logic
    public function is(Model $model)
    {
        return $this->getKey() === $model->getKey() &&
               $this->getTable() === $this->getTable();
    }

    public function isNot(Model $model)
    {
        return ! $this->is($model);
    }

    // cccc
    public function getKeyName()
    {
        return $this->keyName;
    }

    public function getKey()
    {
        return $this->getAttribute($this->keyName);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }


    // dddd
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return "";
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return ! is_null($this->getAttribute($offset));
    }

    public function offsetUnset(mixed $offset): void
    {
        //unset($this->attributes[$offset], $this->relations[$offset]);
    }

    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value)
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return ! is_null($this->getAttribute($key));
    }

    public function __unset(string $key): void
    {
        //unset($this->attributes[$key], $this->relations[$key]);
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static)->{$method}(...$parameters);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }
}