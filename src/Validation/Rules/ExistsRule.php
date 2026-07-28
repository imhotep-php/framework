<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Facades\DB;

class ExistsRule extends AbstractRule
{
    public function setParameters(array $parameters): static
    {
        $this->parameters['connection'] = null;

        if (count($parameters) === 1) {
            $this->parameters['table'] = $parameters[0];
            $this->parameters['column'] = $this->name;
        }
        elseif (count($parameters) === 2) {
            $this->parameters['table'] = $parameters[0];
            $this->parameters['column'] = $parameters[1];
        }

        if (str_contains($this->parameters['table'], '.')) {
            list($connection, $table) = explode('.', $this->parameters['table']);

            $this->parameters['connection'] = $connection;
            $this->parameters['table'] = $table;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['table', 'column']);

        $connection = $this->parameter('connection');
        $table = $this->parameter('table');
        $column = $this->parameter('column');

        return DB::connection($connection)::table($table)->where($column, $value)->exists();
    }
}