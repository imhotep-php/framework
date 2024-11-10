<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres\Schema;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Schema\Table as TableBase;

class Table extends TableBase
{
    protected string $columnClassDefault = Column::class;

    /*
    |--------------------------------------------------------------------------
    | Custom types
    |--------------------------------------------------------------------------
    */

    /**
     * Create column as primary auto-incrementing big integer (8-byte, 1 to 9223372036854775807).
     *
     * @param string $column
     * @return Column
     */
    public function id(string $column = 'id'): Column
    {
        return $this->addColumn('bigSerial', $column, ['primary' => true]);
    }

    /**
     * Create string column
     *
     * @param $name
     * @param int|null $length
     * @return Column
     */
    public function string(string $column, int $length = null): Column
    {
        if (is_null($length)) {
            return $this->text($column);
        }

        return $this->varchar($column, $length);
    }

    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision);
        $this->timestamp('updated_at', $precision);
    }

    public function softDeletes(string $column = 'deleted_at', int $precision = 0): Column
    {
        return $this->timestamp($column, $precision);
    }

    /*
    |--------------------------------------------------------------------------
    | Default PostgreSQL numbers and boolean types
    |--------------------------------------------------------------------------
    */

    public function boolean(string $column): Column
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create new small integer (2-byte, -32768 to +32767) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function smallInteger(string $column): Column
    {
        return $this->addColumn('smallInteger', $column);
    }

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
     * Create new big integer (8-byte, -9223372036854775808 to +9223372036854775807) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function bigInteger(string $column): Column
    {
        return $this->addColumn('bigInteger', $column);
    }

    /**
     * Create new auto-incrementing small integer (2-byte, 1 to 32767) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function smallSerial(string $column): Column
    {
        return $this->addColumn('smallSerial', $column);
    }

    /**
     * Create new auto-incrementing typical integer (4-byte, 1 to 2147483647) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function serial(string $column): Column
    {
        return $this->addColumn('serial', $column);
    }

    /**
     * Create new auto-incrementing typical integer (8-byte, 1 to 9223372036854775807) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function bigSerial(string $column): Column
    {
        return $this->addColumn('bigSerial', $column);
    }

    /**
     * Create new double (15 decimal digits precision) column in table.
     *
     * @param string $name
     * @return Column
     */
    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    /**
     * Create new decimal (up to 131072 digits before decimal point and after 16383) column in table.
     *
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return Column
     */
    public function decimal(string $column, int $precision = null, int $scale = null): Column
    {
        return $this->numeric($column, $precision, $scale);
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
     * @param string $column
     * @return Column
     */
    public function real(string $column): Column
    {
        return $this->addColumn('real', $column);
    }

    /*
    |--------------------------------------------------------------------------
    | Symbolic types
    |--------------------------------------------------------------------------
    */

    /**
     * Create column as text fixed-length (blank padded).
     *
     * @param string $column
     * @param int $length
     * @return mixed
     */
    public function char(string $column, int $length = null): Column
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create column as text variable-length (with limit).
     *
     * @param string $column
     * @param int|null $length
     * @return mixed
     */
    public function varchar(string $column, int $length = null): Column
    {
        return $this->addColumn('varchar', $column, compact('length'));
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

    /*
    |--------------------------------------------------------------------------
    | Datetime types
    |--------------------------------------------------------------------------
    */

    public function timestamp(string $column, int $precision = 0): Column
    {
        if($precision < 0) $precision = 0;
        if($precision > 6) $precision = 6;

        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    public function timestampTz(string $column, int $precision = 0): Column
    {
        if($precision < 0) $precision = 0;
        if($precision > 6) $precision = 6;

        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    public function time(string $column): Column
    {
        return $this->addColumn('time', $column);
    }

    public function timeTz(string $column): Column
    {
        return $this->addColumn('timeTz', $column);
    }

    public function interval(string $column): Column
    {
        throw new DatabaseException("Type 'interval' not supported.");
    }


    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    */

    public function uuid(string $column): Column
    {
        return $this->addColumn('uuid', $column);
    }
}