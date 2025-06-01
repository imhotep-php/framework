<?php declare(strict_types = 1);

namespace Imhotep\Contracts\Validation;

use Imhotep\Contracts\Arrayable;

interface IData extends \ArrayAccess, Arrayable
{
    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function set(string $key, mixed $value): static;

    public function forget(string $key): static;

    public function only(array $keys): array;

    public function except(array $keys): array;

    public function merge(array $data): static;
}