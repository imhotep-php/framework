<?php

declare(strict_types=1);

namespace Imhotep\Database\SQLite\Schema;

use Imhotep\Database\Schema\Column;
use Imhotep\Database\Schema\Table as TableBase;

class Table extends TableBase
{
    protected $columnClassDefault = Column::class;

    /*
    |--------------------------------------------------------------------------
    | Custom types
    |--------------------------------------------------------------------------
    */

    /**
     * Create column as primary auto-incrementing big integer (8-byte, 1 to 9223372036854775807).
     *
     * @param string $name
     * @return Column
     */
    public function id(string $name = 'id'): Column
    {
        return $this->addColumn('integer', $name, ['primary' => true]);
    }

    /**
     * Create string column
     *
     * @param $name
     * @param int|null $length
     * @return Column
     */
    public function string($name, int $length = null): Column
    {
        return $this->text($name);
    }

    public function timestamps()
    {
        $this->integer('created_at');
        $this->integer('updated_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Default SQLite types
    |--------------------------------------------------------------------------
    */

    /**
     * Create new typical integer (4-byte, -2147483648 to +2147483647) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function integer(string $column): Column
    {
        return $this->addColumn('integer', $column);
    }

    /**
     * Create new numeric (up to 131072 digits before decimal point and after 16383) column in table.
     *
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return Column
     */
    public function numeric(string $column, int $precision = null, int $scale = null): Column
    {
        return $this->addColumn('numeric', $column, compact('precision', 'scale'));
    }

    /**
     * Create new double (6 decimal digits precision) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function real(string $column): Column
    {
        return $this->addColumn('real', $column);
    }

    /**
     * Create column as text variable unlimited length.
     *
     * @param string $name
     * @return mixed
     */
    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function blob(string $column): Column
    {
        return $this->addColumn('blob', $column);
    }
}