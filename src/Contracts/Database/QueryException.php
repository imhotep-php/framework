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

    public function isDuplicate(): bool
    {
        $sqlState = $this->errorInfo[0] ?? null;
        $driverCode = $this->errorInfo[1] ?? null;

        // MySQL: 1062 / 1586, PostgreSQL: 23505, SQLite: 19 (with message)
        if ($sqlState === '23505' || $driverCode === 1062 || $driverCode === 1586) {
            return true;
        }
        elseif ($sqlState === '23000' && $driverCode === 19) {
            if (str_contains($this->message, 'UNIQUE constraint failed')) {
                return true;
            }
        }

        return false;
    }

    public function isForeignKey(): bool
    {
        $sqlState = $this->errorInfo[0] ?? null;
        $driverCode = $this->errorInfo[1] ?? null;

        // MySQL: 1451 (нельзя удалить родителя), 1452 (нельзя добавить/обновить ребёнка)
        // PostgreSQL: 23503
        // SQLite: 19 + message "FOREIGN KEY constraint failed"
        if ($sqlState === '23503' || in_array($driverCode, [1451, 1452], true)) {
            return true;
        }
        elseif ($sqlState === '23000' && $driverCode === 19) {
            if (str_contains($this->message, 'FOREIGN KEY constraint failed')) {
                return true;
            }
        }

        return false;
    }

    public function isNotNull(): bool
    {
        $sqlState = $this->errorInfo[0] ?? null;
        $driverCode = $this->errorInfo[1] ?? null;

        // MySQL: 1048, PostgreSQL: 23502, SQLite: 19 + message "NOT NULL constraint failed"
        if ($sqlState === '23502' || $driverCode === 1048) {
            return true;
        }
        elseif ($sqlState === '23000' && $driverCode === 19) {
            if (str_contains($this->message, 'NOT NULL constraint failed')) {
                return true;
            }
        }

        return false;
    }

    public function isDataTooLong(): bool
    {
        $sqlState = $this->errorInfo[0] ?? null;
        $driverCode = $this->errorInfo[1] ?? null;

        // MySQL: 1406, PostgreSQL: 22001, SQLite: only message
        if ($sqlState === '22001' || $driverCode === 1406) {
            return true;
        }
        elseif (str_contains($this->message, 'Data too long for column')) {
            return true;
        }

        return false;
    }
}