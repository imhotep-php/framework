<?php

declare(strict_types=1);

namespace Imhotep\Database\Mysql\Schema;

use Imhotep\Database\Schema\Column;
use Imhotep\Database\Schema\Table as TableBase;

class Table extends TableBase
{
    public function id(string $name = 'id'): Column
    {
        return $this->serial($name);
    }

    public function string($name, int $length = null): Column
    {
        if (is_null($length)) {
            return $this->text($name);
        }

        return $this->varchar($name, $length);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
    }


    /*
    |--------------------------------------------------------------------------
    | Default MySQL types
    |--------------------------------------------------------------------------
    */

    public function serial(string $column): Column
    {
        return $this->addColumn('serial', $column);
    }

    public function boolean(string $column): Column
    {
        return $this->addColumn('boolean', $column);
    }

    public function tinyInteger(string $column): Column
    {
        return $this->addColumn('tinyint', $column);
    }

    public function smallInteger(string $column): Column
    {
        return $this->addColumn('smallint', $column);
    }

    public function mediumInteger(string $column): Column
    {
        return $this->addColumn('mediumint', $column);
    }

    public function integer(string $column): Column
    {
        return $this->addColumn('int', $column);
    }

    public function bigInteger(string $column): Column
    {
        return $this->addColumn('bigint', $column);
    }

    public function float(string $column): Column
    {
        return $this->addColumn('float', $column);
    }

    public function double(string $column): Column
    {
        return $this->addColumn('double', $column);
    }

    public function char(string $column, int $length = null): Column
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    public function varchar(string $column, int $length = null): Column
    {
        return $this->addColumn('char', $column, compact('length'));
    }

    public function tinyText(string $column): Column
    {
        return $this->addColumn('tinytext', $column);
    }

    public function text(string $column): Column
    {
        return $this->addColumn('text', $column);
    }

    public function mediumText(string $column): Column
    {
        return $this->addColumn('mediumtext', $column);
    }

    public function longText(string $column): Column
    {
        return $this->addColumn('longtext', $column);
    }

    public function tinyBlob(string $column): Column
    {
        return $this->addColumn('tinyblob', $column);
    }

    public function blob(string $column): Column
    {
        return $this->addColumn('blob', $column);
    }

    public function mediumBlob(string $column): Column
    {
        return $this->addColumn('mediumblob', $column);
    }

    public function longBlob(string $column): Column
    {
        return $this->addColumn('longblob', $column);
    }

    public function date(string $column): Column
    {
        return $this->addColumn('date', $column);
    }

    public function datetime(string $column): Column
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamp(string $column): Column
    {
        return $this->addColumn('timestamp', $column);
    }

    public function time(string $column): Column
    {
        return $this->addColumn('time', $column);
    }

    public function year(string $column): Column
    {
        return $this->addColumn('year', $column);
    }

    public function json(string $column): Column
    {
        return $this->addColumn('json', $column);
    }

    public function enum(string $column): Column
    {
        return $this->addColumn('enum', $column);
    }

    public function set(string $column): Column
    {
        return $this->addColumn('set', $column);
    }


}