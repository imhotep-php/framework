<?php declare(strict_types=1);

namespace Imhotep\Contracts\Support;

interface MessageBag
{
    public function messages(): array;

    public function any(): bool;

    public function isEmpty(): bool;

    public function isNotEmpty(): bool;

    public function count(): int;

    public function keys(): array;

    public function add(array|string $key, array|string $value = null): static;

    public function addIf(bool $boolean, array|string $key, array|string $value = null): static;

    public function get(string $key, string $format = null): array;

    public function all(string $format = null): array;

    public function unique(string $format = null): array;

    public function first(string $key = null, string $format = null): string;

    public function has(array|string $key = null): bool;

    public function hasAny(array|string $key = null): bool;

    public function format(string $format = null): static|string;

    public function getFormat(): string;

    public function setFormat(string $format): static;
}