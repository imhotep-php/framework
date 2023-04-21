<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte\Traits;

use Imhotep\Database\Calypte\Builder;
use Imhotep\Support\Str;

trait HasQuery
{
    protected ?string $connection = null;

    protected ?string $table = null;

    public function getConnection()
    {
        return db($this->connection);
    }

    public function getTable()
    {
        return $this->table ?? Str::pluralModel(
                basename(str_replace('\\', '/', get_class($this)))
            );
    }

    public function setTable(string $table)
    {
        $this->table = $table;
    }

    public function query()
    {

    }

    public function newQuery()
    {
        return new Builder($this->getConnection(), $this);
    }

    public function fill(array $attributes = [])
    {

    }

    public function update(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function save()
    {
        dd('save', $this->attributes, $this->originals);
    }

    public function delete()
    {

    }
}