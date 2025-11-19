<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

use Imhotep\Support\Str;
use PDOException;
use Throwable;

class QueryException extends DatabaseException
{
    protected string $sql;

    protected array $bindings;

    protected array|null $errorInfo;

    public function __construct(string $sql, array $bindings, Throwable $previous)
    {
        parent::__construct('', 0, $previous);

        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = 0;
        $this->message = $this->formatMessage($sql, $bindings, $previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    protected function formatMessage(string $sql, array $bindings, Throwable $previous): string
    {
        return $previous->getMessage().' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}