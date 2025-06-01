<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

interface IRule
{
    public function name(): string;

    public function setName(string $name): static;

    public function setData(IData $data): static;

    public function check(mixed $value): bool;

    public function message(): ?string;

    public function implicit(): bool;

    public function setImplicit(bool $implicit): static;

    public function setParameters(array $parameters): static;

    public function requireParameters(array $parameters): void;
}
