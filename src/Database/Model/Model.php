<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

use Imhotep\Contracts\Database\IModel;
use InvalidArgumentException;
use stdClass;

abstract class Model implements IModel
{
    use HasAttributes, HasGuardAttributes, HasPrimaryKey, HasTimestamps, HasSoftDeletes;

    protected string $repositoryClass = '';

    // Поля, которые можно наполнять
    protected array $fillable = [];

    // Поля, которые нужно скрыть при сериализации в toArray
    protected array $hidden = [];

    protected array $casts = [];

    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /* the function intersects with a function from the repository
    public static function create(array|stdClass $attributes): static
    {
        return new static($attributes);
    }
    */

    public static function newFrom(array $attributes): static
    {
        return (new static())->setRawAttributes($attributes)->syncOriginals();
    }

    public function fill(array $attributes): static
    {
        if (empty($attributes)) {
            return $this;
        }

        $error = function (string|array $keys) {
            throw new InvalidArgumentException(sprintf(
                'Add [%s] to fillable property to allow mass assignment on [%s].',
                implode(", ", (array)$keys), get_class($this)
            ));
        };

        if ($this->totallyGuarded()) {
            $error(
                (count($this->fillable) === 0)
                    ? array_keys($attributes)
                    : array_diff(array_keys($attributes), array_keys(array_flip($this->fillable)))
            );
        }

        foreach ($attributes as $key => $val) {
            if (! $this->isFillable($key)) {
                $error($key);
            }

            $this->setAttribute($key, $val);
        }

        return $this;
    }

    public function forceFill(array $attributes): static
    {
        static::$unguarded = true;

        $this->fill($attributes);

        static::$unguarded = false;

        return $this;
    }

    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toArray(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->getAttribute($name) !== null;
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        if ($this->repositoryClass !== '' && class_exists($this->repositoryClass)) {
            $repository = new $this->repositoryClass;

            return $repository->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Call to undefined method {$name}()");
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return (new static())->{$name}(...$arguments);
    }
}