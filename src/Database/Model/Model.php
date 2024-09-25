<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

use stdClass;

/**
 * @property string $updateAt
 * @property string $createdAt
 * @property string $deletedAt
 */
class Model
{
    use HasAttributes, HasGuardAttributes, HasTimestamps, HasSoftDeletes;

    // Поля, которые можно наполнять
    protected array $fillable = [];

    protected array $hidden = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;;
    }

    public static function create(array|stdClass $attributes): static
    {
        return new static($attributes);
    }

    public static function newFrom(array|stdClass $attributes): static
    {
        $model = new static();

        $model->setAttributes((array)$attributes)->syncOriginals();

        return $model;
    }

    public function fill(array $attributes): static
    {
        if (empty($attributes)) {
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

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }
}