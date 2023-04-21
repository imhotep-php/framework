<?php

declare(strict_types=1);

namespace Imhotep\Database\Postgres\Schema;

use Imhotep\Database\Schema\Grammar as GrammarBase;

class Grammar extends GrammarBase
{
    /**
     * Possible column modifiers.
     *
     * @var string[]
     */
    protected array $modifiers = ['Collate', 'Primary', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var string[]
     */
    protected array $serials = ['bigSerial', 'serial', 'smallSerial'];

    public function addModifiers($sql, $table, $column): string
    {
        if (in_array($column->type, $this->serials)) {
            return $sql.$this->modifyPrimary($column);
        }

        return parent::addModifiers($sql, $table, $column);
    }

    public function compileColumnListing(): string
    {
        return 'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? and table_schema = ? and table_name = ?';
    }

    protected function typeBoolean(Column $column): string
    {
        return 'boolean'.(($column->array) ? ' array' : '');
    }

    protected function typeSmallInteger(Column $column): string
    {
        return "smallint".(($column->array) ? ' array' : '');
    }

    protected function typeInteger(Column $column): string
    {
        return "integer".(($column->array) ? ' array' : '');
    }

    protected function typeBigInteger(Column $column): string
    {
        return "bigint".(($column->array) ? ' array' : '');
    }

    protected function typeNumeric(Column $column): string
    {
        if (! is_null($column->precision) && ! is_null($column->scale)) {
            return sprintf("numeric(%s,%s)", $column->precision, $column->scale).(($column->array) ? ' array' : '');;
        }
        else if (! is_null($column->precision)) {
            return sprintf("numeric(%s)", $column->precision).(($column->array) ? ' array' : '');;
        }

        return 'numeric'.(($column->array) ? ' array' : '');;
    }

    protected function typeReal(Column $column): string
    {
        return "real".(($column->array) ? ' array' : '');;
    }

    protected function typeDouble(Column $column): string
    {
        return "double precision".(($column->array) ? ' array' : '');;
    }

    protected function typeSmallSerial(Column $column): string
    {
        return "smallserial";
    }

    protected function typeSerial(Column $column): string
    {
        return "serial";
    }

    protected function typeBigSerial(Column $column): string
    {
        return "bigserial";
    }



    protected function typeChar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 1;
        }

        return sprintf('char(%s)', $column->length).(($column->array) ? ' array' : '');;
    }

    protected function typeVarchar(Column $column): string
    {
        if(is_null($column->length) || $column->length < 1){
            $column->length = 255;
        }

        return sprintf('varchar(%s)', $column->length).(($column->array) ? ' array' : '');;
    }

    protected function typeText(Column $column): string
    {
        return "text".(($column->array) ? ' array' : '');;
    }



    protected function typeTimestamp(Column $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s without time zone%s', $precision, $useCurrent);
    }

    protected function typeTimestampTz(Column $column): string
    {
        $precision = !is_null($column->precision) ? "({$column->precision})" : '';
        $useCurrent = $column->useCurrent ? ' default CURRENT_TIMESTAMP' : '';

        return sprintf('timestamp%s with time zone%s', $precision, $useCurrent);
    }

    protected function typeDate(Column $column): string
    {
        return 'date';
    }

    protected function typeTime(Column $column): string
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' without time zone';
    }

    protected function typeTimeTz(Column $column): string
    {
        return 'time'.(is_null($column->precision) ? '' : "($column->precision)").' with time zone';
    }



    protected function typeUuid(Column $column): string
    {
        return 'uuid'.($column->array ? ' array' : '');
    }



    protected function modifyPrimary(Column $column)
    {
        if ($column->primary) {
            return ' PRIMARY KEY';
        }
    }

    protected function modifyCollate(Column $column)
    {
        if (! is_null($column->collation)) {
            return ' COLLATE '.$this->wrapValue($column->collation);
        }
    }

    protected function modifyNullable(Column $column)
    {
        return $column->nullable ? ' NULL' : ' NOT NULL';
    }

    protected function modifyDefault(Column $column)
    {
        if (! is_null($column->default)) {
            return ' DEFAULT '.$this->wrapDefaultValue($column->default);
        }
    }

    protected function modifyVirtualAs(Column $column)
    {
        if ($column->virtualAs !== null) {
            return " GENERATED ALWAYS AS ({$column->virtualAs})";
        }
    }

    protected function modifyStoredAs(Column $column)
    {
        if ($column->storedAs !== null) {
            return " GENERATED ALWAYS AS ({$column->storedAs}) STORED";
        }
    }
}