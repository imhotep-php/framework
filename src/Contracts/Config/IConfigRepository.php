<?php declare(strict_types=1);

namespace Imhotep\Contracts\Config;

use ArrayAccess;
use Closure;

interface IConfigRepository extends ArrayAccess
{
    public function has(string $key): bool;

    public function all(): array;

    public function required(string $key, string $type = '', ?string $message = null): mixed;

    public function get(string|array $key, mixed $default = null): mixed;

    public function getOrFail(string $key, ?string $message = null): mixed;

    public function getMany(array $keys): array;

    public function string(string $key, Closure|string $default = null): ?string;

    public function stringOrFail(string $key, ?string $message = null): string;

    public function int(string $key, Closure|int $default = null): ?int;

    public function intOrFail(string $key, ?string $message = null): int;

    public function float(string $key, Closure|float $default = null): ?float;

    public function floatOrFail(string $key, ?string $message = null): float;

    public function bool(string $key, Closure|bool $default = null): ?bool;

    public function boolOrFail(string $key, ?string $message = null): bool;

    public function array(string $key, Closure|array $default = null): ?array;

    public function arrayOrFail(string $key, ?string $message = null): array;

    public function set(string|array $key, mixed $value = null): void;

    public function prepend(string $key, mixed $value): void;

    public function push(string $key, mixed $value): void;

    public function subset(string $key, Closure|array $default = []): static;

    public function subsetOrFail(string $key, ?string $message = null): static;
}