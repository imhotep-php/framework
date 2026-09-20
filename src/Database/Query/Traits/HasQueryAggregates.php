<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

trait HasQueryAggregates
{
    public function count(string $column = 'id'): int
    {
        return (int)$this->aggregate(__FUNCTION__, [$column]);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function sum(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    protected function aggregate(string $function, array $columns = ['*']): mixed
    {
        $this->aggregate = compact('function', 'columns');

        $results = $this->get();

        return empty($results) ? null : $results[0]->aggregate;
    }
}