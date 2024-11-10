<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Database;

use Closure;
use Imhotep\Contracts\Events\Dispatcher;
use Imhotep\Database\Query\Builder as QueryBuilder;
use Imhotep\Database\Query\Grammar as QueryGrammar;
use Imhotep\Database\Schema\Builder as SchemaBuilder;
use Imhotep\Database\Schema\Grammar as SchemaGrammar;
use PDO;

interface Connection
{
    public function getName(): string;

    public function getDatabaseName(): string;

    public function getTablePrefix(): string;

    public function query(): QueryBuilder;

    public function table(string $table, string $as = null): QueryBuilder;

    public function selectFromWriteConnection(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true);

    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array;

    public function cursor(string $query, array $bindings = [], bool $useReadPdo = true): \Iterator;

    public function insert(string $query, array $bindings = []): int;

    public function lastInsertId(string $name = null): string|false;

    public function update(string $query, array $bindings = []): int;

    public function delete(string $query, array $bindings = []): int;

    public function statement(string $query, array $bindings = [], bool $useReadPdo = false): mixed;

    public function unprepared(string $query): bool;

    public function affectingStatement(string $query, array $bindings = []): int;

    public function pretending(): bool;

    public function pretend(Closure $callback): mixed;

    public function getPdo(): ?PDO;

    public function setPdo($pdo): static;

    public function getReadPdo(): ?PDO;

    public function setReadPdo($pdo): static;

    public function getPdoForSelect(bool $useReadPdo): ?PDO;

    public function setReconnector(Closure $reconnector): static;

    public function reconnect(): mixed;

    public function reconnectIfMissingConnection(): void;

    public function event(string|object $event): static;

    public function listen(Closure $callback): static;

    public function setEventDispatcher(Dispatcher $events): static;

    public function getEventDispatcher(): Dispatcher;

    public function unsetEventDispatcher(): static;

    public function useSchemaGrammar(): static;

    public function getSchemaGrammar(): SchemaGrammar;

    public function getSchemaBuilder(): SchemaBuilder;

    public function useQueryGrammar(): static;

    public function getQueryGrammar(): QueryGrammar;

    public function getQueryBuilder(): QueryBuilder;
}