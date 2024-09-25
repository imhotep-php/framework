<?php declare(strict_types=1);

namespace Imhotep\Database;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Schema\Table;
use Imhotep\Support\Traits\Macroable;

abstract class Grammar
{
    use Macroable {
        __call as macroCall;
    }

    protected ?string $tablePrefix = null;

    public function getTablePrefix(): ?string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string $prefix = null): static
    {
        $this->tablePrefix = $prefix;

        return $this;
    }



    protected function columnize(string|array $columns): string
    {
        return implode(', ', array_map([$this, 'wrap'], (array)$columns));
    }

    protected function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        return sprintf('"%s"', $value);
    }

    protected function wrapTable(string|array|Table $table): string
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }

        if (is_array($table)) {
            if (is_null($table[1])) return $this->wrapTable($table[0]);

            return $this->wrapTable($table[0]).' as '.$this->wrap($table[1]);
        }

        if(! empty($this->tablePrefix)){
            $table = $this->tablePrefix.$table;
        }

        return $this->wrap($table);
    }

    protected function wrapValue(string $value): string
    {
        if ($value === '*') return $value;

        return '"'.str_replace('"', '""', $value).'"';
    }

    protected function getDefaultValue(mixed $value = null): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        if (is_null($value)) {
            return 'NULL';
        }

        return $this->wrapValue( is_bool($value) ? (int)$value : (string)$value );
    }

    protected function quoteString($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "'$value'";
    }


    protected function isJsonSelector(string $value): bool
    {
        return str_contains($value, '->');
    }

    /**
     * Split the given JSON selector into the field and the optional path and wrap them separately.
     *
     * @param  string  $column
     * @return array
     */
    protected function wrapJsonFieldAndPath(string $column): array
    {
        $parts = explode('->', $column, 2);

        $field = $this->wrap($parts[0]);

        $path = count($parts) > 1 ? ', '.$this->wrapJsonPath($parts[1]) : '';

        return [$field, $path];
    }

    /**
     * Wrap the given JSON path.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    protected function wrapJsonPath(string $value, string $delimiter = '->'): string
    {
        $value = preg_replace("/([\\\\]+)?\\'/", "''", $value);

        $jsonPath = explode($delimiter, $value);
        $jsonPath = array_map(fn ($segment) =>  $this->wrapJsonPathSegment($segment), $jsonPath);
        $jsonPath = implode('.', $jsonPath);

        return "'$".(str_starts_with($jsonPath, '[') ? '' : '.').$jsonPath."'";
    }

    /**
     * Wrap the given JSON path segment.
     *
     * @param  string  $segment
     * @return string
     */
    protected function wrapJsonPathSegment(string $segment): string
    {
        if (preg_match('/(\[[^\]]+\])+$/', $segment, $parts)) {
            $key = $segment;

            if ($pos = mb_strrpos($segment, $parts[0])) {
                $key = mb_substr($segment, 0, $pos);
            }

            if (! empty($key)) {
                return '"'.$key.'"'.$parts[0];
            }

            return $parts[0];
        }

        return '"'.$segment.'"';
    }


    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new DatabaseException(sprintf('Grammar method %s in %s not configured or not supported.', $method, static::class));
    }
}