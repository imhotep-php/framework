<?php declare(strict_types=1);

namespace Imhotep\Database\Repository;

use Exception;
use Imhotep\Contracts\Database\IModel;
use Imhotep\Database\Model\Model;
use Imhotep\Database\Query\Builder;
use Imhotep\Facades\DB;
use Imhotep\Support\Str;
use RuntimeException;

/**
 * @method $this columns(array $columns)
 * @method $this latest(string $column = 'created_at')
 * @method $this offset(int $offset)
 * @method $this limit(int $limit)
 */
abstract class Repository
{
    use HasScopes;

    protected ?string $connection = null;

    protected ?string $table = null;

    protected string $model = '';

    protected array $withs = [];

    public function __construct()
    {
        if (empty($this->model)) {
            throw new RuntimeException(
                'Repository ' . static::class . ' must define $model property with a model class'
            );
        }
    }

    //public function find(id);
    //public function findOrFail(id);
    //public function findBy(column, value);

    /**
     * @return IModel[]
     */
    public function all(): array
    {
        return $this->applyWiths($this->query()->get());
    }

    public function find(int|string $id): ?IModel
    {
        return $this->applyWiths($this->query()->find($id));
    }

    /*
    public function findOrFail($id): Model
    {
        if ($result = $this->find($id)) {
            return $result;
        }

        throw new ModelNotFoundException();
    }
    */

    public function first(): ?IModel
    {
        return $this->applyWiths($this->query()->first());
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function exists(): bool
    {
        return !is_null($this->query()->first());
    }

    public function missing(): bool
    {
        return is_null($this->query()->first());
    }


    public function create(array $attributes): ?object
    {
        $model = new $this->model;
        $model->fill($attributes);
        $model->updateTimestamps();

        if ($id = $this->query()->insertGetId($model->getAttributes())) {
            return $model->forceFill(['id' => $id])->syncOriginals();
        }

        return null;
    }

    public function update(IModel $model): bool
    {
        if (!$model instanceof $this->model) {
            throw new Exception('Invalid model');
        }

        $model->updateTimestamps();

        return $this->query()
                ->where($model->keyName(), $model->key())
                ->update($model->getChanges()) > 0;
    }

    public function delete(Model $model): bool
    {
        if (!$model instanceof $this->model) {
            throw new Exception('Invalid model');
        }

        if ($model->usesSoftDeletes()) {
            $model->setAttribute($model->getDeletedAtColumn(), date('Y-m-d H:i:s'));

            return $this->update($model);
        }

        return $this->query()
                ->where($model->keyName(), $model->key())
                ->delete() > 0;
    }

    public function restore(Model $model): bool
    {
        if (!$model instanceof $this->model) {
            throw new Exception('Invalid model');
        }

        if ($model->usesSoftDeletes()) {
            $model->setAttribute($model->getDeletedAtColumn(), null);

            return $this->update($model);
        }

        return false;
    }

    protected function scopeColumns(Builder $query, array $columns): void
    {
        $query->select($columns);
    }

    protected function scopeLatest(Builder $query, string $column = 'created_at'): void
    {
        $query->orderBy($column, 'desc');
    }

    protected function scopeOffset(Builder $query, int $offset): void
    {
        $query->offset($offset);
    }

    protected function scopeLimit(Builder $query, int $limit): void
    {
        $query->limit($limit);
    }


    protected function query(): Builder
    {
        $query = DB::connection($this->connection)
            ->table($this->table())
            ->setModel($this->model);

        $this->applyScopes($query);

        return $query;
    }

    protected function table(): string
    {
        if ($this->table !== null) {
            return $this->table;
        }

        $className = basename(str_replace('\\', '/', get_class($this)));
        $modelName = str_replace('Repository', '', $className);

        return Str::pluralModel($modelName);
    }


    public function with(string|array $relations): static
    {
        $this->withs = array_merge($this->withs, (array)$relations);

        return $this;
    }

    protected function applyWiths(mixed $items): mixed
    {
        if (is_null($items)) {
            return null;
        }

        $oneItem = !is_array($items);

        if ($oneItem) {
            $items = [$items->key() => $items];
        } else {
            $normalized = [];
            foreach ($items as $item) {
                $normalized[$item->key()] = $item;
            }
            $items = $normalized;
        }

        foreach ($this->withs as $with) {
            $withMethod = "load" . ucfirst($with);
            if (method_exists($this, $withMethod)) {
                $items = $this->$withMethod($items);
            }
        }

        $this->withs = [];

        return $oneItem ? array_shift($items) : $items;
    }


    public function __call(string $name, array $arguments)
    {
        $scopeMethod = "scope" . ucfirst($name);
        if (method_exists($this, $scopeMethod)) {
            $this->scope(function ($query) use ($scopeMethod, $arguments) {
                $this->$scopeMethod($query, ...$arguments);
            });

            return $this;
        }

        if (str_starts_with($name, 'with')) {
            $this->with(lcfirst(substr($name, 4)));

            return $this;
        }

        throw new Exception('Call to undefined method ' . get_class($this) . '::' . $name . '()');
    }
}