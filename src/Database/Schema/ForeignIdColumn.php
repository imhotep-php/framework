<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

class ForeignIdColumn extends Column
{
    /**
     * Create a new foreign ID column definition.
     *
     * @param Table $table
     * @param array $attributes
     */
    public function __construct(
        protected Table $table,
        array $attributes = []
    )
    {
        parent::__construct($attributes);
    }

    /**
     * Create a foreign key constraint on this column referencing the "id" column of the conventionally related table.
     *
     * @param string|null $table
     * @param string $column
     * @return ForeignKey
     */
    public function constrained(string $table = null, string $column = 'id'): ForeignKey
    {
        if (is_null($table)) {
            if (! str_contains($this->name, '_')) {
                return $this->references($column);
            }

            $table = str_replace('_'.$column, '', $this->name);
        }

        return $this->references($column)->on($table);
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @param string $column
     * @return ForeignKey
     */
    public function references(string $column): ForeignKey
    {
        return $this->table->foreign($this->name)->references($column);
    }
}