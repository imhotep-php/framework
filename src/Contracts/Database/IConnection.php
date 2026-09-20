<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

use Closure;
use Imhotep\Contracts\Events\Dispatcher;
use PDO;

interface IConnection
{
    public function getName(): string;

    public function getDatabaseName(): string;

    public function getTablePrefix(): string;

    public function query(): IQueryBuilder;

    public function table(string $table, ?string $as = null): IQueryBuilder;

    public function selectFromWriteConnection(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true);

    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array;

    public function cursor(string $query, array $bindings = [], bool $useReadPdo = true): \Iterator;

    public function insert(string $query, array $bindings = []): int;

    public function lastInsertId(?string $name = null): string|false;

    public function update(string $query, array $bindings = []): int;

    public function delete(string $query, array $bindings = []): int;

    public function statement(string $query, array $bindings = [], bool $useReadPdo = false): mixed;

    public function unprepared(string $query): bool;

    public function affectingStatement(string $query, array $bindings = []): int;

    public function pretending(): bool;

    public function pretend(Closure $callback): mixed;

    public function getPdo(): ?PDO;

    public function setPdo(PDO|Closure|null $pdo): static;

    public function getReadPdo(): ?PDO;

    public function setReadPdo(PDO|Closure|null $pdo): static;

    public function getPdoForSelect(bool $useReadPdo): ?PDO;

    public function setReconnector(Closure $reconnector): static;

    public function reconnect(): mixed;

    public function reconnectIfMissingConnection(): void;

    public function event(string|object $event): static;

    public function listen(Closure $callback): static;

    public function setEventDispatcher(Dispatcher $events): static;

    public function getEventDispatcher(): Dispatcher;

    public function unsetEventDispatcher(): static;

    public function getSchemaGrammar(): ISchemaGrammar;

    public function getSchemaBuilder(): ISchemaBuilder;

    public function getQueryGrammar(): IQueryGrammar;

    public function getQueryBuilder(): IQueryBuilder;
}