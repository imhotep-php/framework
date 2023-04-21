<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte;

use Closure;
use Imhotep\Contracts\Database\QueryBuilder as QueryBuilderContract;
use Imhotep\Contracts\Database\SchemaBuilder as SchemaBuilderContract;
use Imhotep\Database\Calypte\Exceptions\ModelNotFoundException;
use Imhotep\Database\Calypte\Traits\ForwardCalls;
use Imhotep\Database\Connection as ConnectionContract;
use Imhotep\Contracts\Database\Calypte\Model as ModelContract;

/**
 * Used methods from [Query/Builder]:
 * @method take(int $count)
 * @method get()
 */
class Builder
{
    use ForwardCalls;

    protected ?ConnectionContract $connection = null;

    protected ?SchemaBuilderContract $schemaBuilder = null;

    protected ?QueryBuilderContract $query = null;

    protected ?ModelContract $model = null;

    public function __construct(ConnectionContract $connection, ModelContract $model)
    {
        $this->connection = $connection;
        $this->schemaBuilder = $connection->getSchemaBuilder();
        $this->query = $connection->query();
        $this->setModel($model);
    }

    public function setModel(ModelContract $model): static
    {
        $this->model = $model;
        $this->query->from($model->getTable());

        return $this;
    }

    public function getModel(): ?ModelContract
    {
        return $this->model;
    }

    public function newModelInstance($attributes = [])
    {
        $model = get_class($this->model);
        return new $model($attributes);
    }

    public function make(array $attributes = []): ?ModelContract
    {
        return $this->newModelInstance($attributes);
    }

    public function create(array $attributes = []): ModelContract
    {
        $model = $this->make($attributes);
        $model->save();
        return $model;
    }

    public function first(): ?ModelContract
    {
        if (! is_null($attributes = $this->take(1)->first())) {
            return $this->make($attributes);
        }

        return null;
    }

    public function firstOrNew(array $attributes = []): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        return $this->create($attributes);
    }

    public function firstOrCreate(array $attributes = []): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        $model = $this->create($attributes);
        $model->save();

        return $model;
    }

    public function firstOrFail(): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        throw (new ModelNotFoundException())->setModel(get_class($this->model));
    }

    /**
     * @param Closure $callback
     * @return ModelContract|mixed|null
     */
    public function firstOr(Closure $callback)
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        return $callback();
    }

    public function where(...$condition): Builder
    {
        $this->query->where(...$condition);

        return $this;
    }

    /**
     * @param $method
     * @param $parameters
     * @return \Imhotep\Database\Query\Builder|mixed
     * @throws \Exception
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->query, $method, $parameters);
    }
}