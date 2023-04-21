<?php

declare(strict_types=1);

namespace Imhotep\Database\Traits;

use Imhotep\Database\Events\QueryExecuted;

trait ConnectionLogger
{
    protected bool $loggingQueries = true;

    protected array $queryLog = [];

    protected float $totalQueryDuration = 0.0;

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging(): bool
    {
        return $this->loggingQueries;
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog(): static
    {
        $this->loggingQueries = true;

        return $this;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog(): static
    {
        $this->loggingQueries = false;

        return $this;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog(): static
    {
        $this->queryLog = [];

        return $this;
    }

    /**
     * Get the duration of all run queries in milliseconds.
     *
     * @return float
     */
    public function totalQueryDuration(): float
    {
        return $this->totalQueryDuration;
    }

    /**
     * Reset the duration of all run queries.
     *
     * @return void
     */
    public function resetTotalQueryDuration(): static
    {
        $this->totalQueryDuration = 0.0;

        return $this;
    }

    public function logQuery(string $query, array $bindings, float $time = null): void
    {
        $this->totalQueryDuration += $time ?? 0.0;

        $this->event(new QueryExecuted($query, $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    protected function getElapsedTime(float $start): float
    {
        return round((microtime(true) - $start), 5);
    }
}