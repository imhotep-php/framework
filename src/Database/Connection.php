<?php declare(strict_types=1);

namespace Imhotep\Database;

use Closure;
use DateTimeInterface;
use Exception;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Contracts\Database\QueryException;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Database\Events\QueryExecuted;
use Imhotep\Database\Query\Builder as QueryBuilder;
use Imhotep\Database\Query\Grammar as QueryGrammar;
use Imhotep\Database\Schema\Builder as SchemaBuilder;
use Imhotep\Database\Schema\Grammar as SchemaGrammar;
use Imhotep\Database\Traits\ConnectionLogger;
use Imhotep\Database\Traits\ConnectionTransactions;
use Imhotep\Database\Traits\DetectsErrors;
use Imhotep\Support\Arr;
use PDO;
use PDOStatement;
use Throwable;

abstract class Connection implements IConnection
{
    use ConnectionLogger, ConnectionTransactions, DetectsErrors;

    protected PDO|Closure|null $pdo = null;

    protected PDO|Closure|null $readPdo = null;

    protected Closure $reconnector;

    protected string $database;

    protected string $tablePrefix;

    protected array $config;

    protected int $fetchMode = PDO::FETCH_OBJ;

    protected bool $recordsModified = false;

    public function __construct($pdo, array $config = [])
    {
        $this->pdo = $pdo;

        $this->readPdo = $pdo;

        $this->config = $config;

        $this->database = $config['database'];

        $this->tablePrefix = $config['prefix'] ?? '';
    }

    public function getName(): string
    {
        return $this->config['name'] ?? '';
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function getSchema(): string
    {
        if (empty($this->config['schema'])) {
            throw new DatabaseException(sprintf(
                "For connection [%s] schema not configured.",
                $this->getName() ?? 'default'
            ));
        }

        return $this->config['schema'];
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function raw(mixed $value): Expression
    {
        return new Expression($value);
    }

    /*
    |--------------------------------------------------------------------------
    | Querying
    |--------------------------------------------------------------------------
    */

    public function query(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }

    public function table(string $table, ?string $as = null): QueryBuilder
    {
        return $this->query()->from($table, $as);
    }

    public function selectFromWriteConnection(string $query, array $bindings = []): array
    {
        return $this->select($query, $bindings, false);
    }

    public function selectFromWrite(string $query, array $bindings = []): array
    {
        return $this->select($query, $bindings, false);
    }

    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
    {
        $items = $this->select($query, $bindings, $useReadPdo);

        return array_shift($items);
    }

    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array
    {
        $statement = $this->statement($query, $bindings, $useReadPdo);

        return $statement->fetchAll();
    }

    public function cursor(string $query, array $bindings = [], bool $useReadPdo = true): \Iterator
    {
        $statement = $this->statement($query, $bindings, $useReadPdo);

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    public function insert(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function statement(string $query, array $bindings = [], bool $useReadPdo = false): PDOStatement|false
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return false;
            }

            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

            $statement->setFetchMode($this->fetchMode);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement;
        });
    }

    public function unprepared(string $query): bool
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $this->recordsHaveBeenModified(
                $change = $this->getPdo()->exec($query) !== false
            );

            return $change;
        });
    }

    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    protected function run(string $query, array $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        }
        catch (QueryException $e) {
            $result = $this->handleQueryException(
                $e, $query, $bindings, $callback
            );
        }

        $this->logQuery(
            $query, $bindings, $this->getElapsedTime($start)
        );

        return $result;
    }

    protected function runQueryCallback(string $query, array $bindings, Closure $callback): mixed
    {
        try {
            return $callback($query, $bindings);
        }

        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }
    }

    protected function handleQueryException(Throwable $e, string $query, array $bindings, Closure $callback): mixed
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR
                },
            );
        }
    }

    protected function prepareBindings(array $bindings): array
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    protected function recordsHaveBeenModified(bool $value = false)
    {
        if (! $this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pretend
    |--------------------------------------------------------------------------
    */

    protected bool $pretending = false;

    public function pretending(): bool
    {
        return $this->pretending;
    }

    public function pretend(Closure $callback): mixed
    {
        return $this->withFreshQueryLog(function () use ($callback) {
            $this->pretending = true;

            // Basically to make the database connection "pretend", we will just return
            // the default values for all the query methods, then we will return an
            // array of queries that were "executed" within the Closure callback.
            $callback($this);

            $this->pretending = false;

            return $this->queryLog;
        });
    }

    protected function withFreshQueryLog($callback): mixed
    {
        $loggingQueries = $this->loggingQueries;

        // First we will back up the value of the logging queries property and then
        // we'll be ready to run callbacks. This query log will also get cleared
        // so we will have a new log of all the queries that are executed now.
        $this->enableQueryLog();

        $this->queryLog = [];

        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of query logging and give back the
        // value of the callback so the original callers can have the results.
        $result = $callback();

        $this->loggingQueries = $loggingQueries;

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | PDO
    |--------------------------------------------------------------------------
    */

    /**
     * @return PDO
     */
    public function getPdo(): ?PDO
    {
        if ($this->pdo instanceof Closure) {
            $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    public function getRawPdo(): PDO|Closure|null
    {
        return $this->pdo;
    }

    public function setPdo(PDO|Closure|null $pdo): static
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    public function getReadPdo(): ?PDO
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        // TODO: sticky

        if ($this->readPdo instanceof Closure) {
            $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    public function getRawReadPdo(): PDO|Closure|null
    {
        return $this->readPdo;
    }

    public function setReadPdo(PDO|Closure|null $pdo): static
    {
        $this->readPdo = $pdo;

        return $this;
    }

    public function getPdoForSelect(bool $useReadPdo): ?PDO
    {
        if ($useReadPdo) {
            if (!is_null($readPdo = $this->getReadPdo())) {
                return $readPdo;
            }
        }

        return $this->getPdo();
    }

    public function setReconnector(Closure $reconnector): static
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    public function reconnect(): mixed
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new DatabaseException("Lost connection, reconnector not available.");
    }

    public function reconnectIfMissingConnection(): void
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    public function disconnect(): void
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    protected ?Dispatcher $events = null;

    public function event(string|object $event): static
    {
        $this->events?->dispatch($event);

        return $this;
    }

    public function listen(Closure $callback): static
    {
        $this->events?->listen(QueryExecuted::class, $callback);

        return $this;
    }

    public function setEventDispatcher(Dispatcher $events): static
    {
        $this->events = $events;

        return $this;
    }

    public function getEventDispatcher(): Dispatcher
    {
        return $this->events;
    }

    public function unsetEventDispatcher(): static
    {
        $this->events = null;

        return $this;
    }


    /*
    |--------------------------------------------------------------------------
    | Schema
    |--------------------------------------------------------------------------
    */

    protected ?SchemaGrammar $schemaGrammar = null;

    protected ?QueryGrammar $queryGrammar = null;

    public function getSchemaGrammar(): SchemaGrammar
    {
        return $this->schemaGrammar ?? $this->buildSchemaGrammar();
    }

    public function getSchemaBuilder(): SchemaBuilder
    {
        return $this->createSchemaBuilder();
    }

    public function getQueryGrammar(): QueryGrammar
    {
        return $this->queryGrammar ?? $this->buildQueryGrammar();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder();
    }

    abstract protected function createSchemaGrammar(): SchemaGrammar;

    abstract protected function createSchemaBuilder(): SchemaBuilder;

    abstract protected function createQueryGrammar(): QueryGrammar;

    abstract protected function createQueryBuilder(): QueryBuilder;

    protected function buildSchemaGrammar(): SchemaGrammar
    {
        $this->schemaGrammar = $this->createSchemaGrammar();
        $this->configureSchemaGrammar($this->schemaGrammar);

        return $this->schemaGrammar;
    }

    protected function buildQueryGrammar(): QueryGrammar
    {
        $this->queryGrammar = $this->createQueryGrammar();
        $this->configureQueryGrammar($this->queryGrammar);

        return $this->queryGrammar;
    }

    protected function configureSchemaGrammar($grammar): void
    {
        $grammar->setTablePrefix($this->tablePrefix);
    }

    protected function configureQueryGrammar($grammar): void
    {
        $grammar->setTablePrefix($this->tablePrefix);
    }
}