<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IData;
use Imhotep\Contracts\Validation\IRule as RuleContract;
use Imhotep\Contracts\Validation\ValidationException;

abstract class AbstractRule implements RuleContract
{
    protected string $name = '';

    protected bool $typed = false;

    protected bool $implicit = false;

    protected ?string $message = null;

    protected array $parameters = [];

    protected ?IData $data = null;

    abstract public function check(mixed $value): bool;

    public function message(): ?string
    {
        return $this->message;
    }

    public function implicit(): bool
    {
        return $this->implicit;
    }

    public function setImplicit(bool $implicit): static
    {
        $this->implicit = $implicit;

        return $this;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setParameter(string $key, mixed $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function parameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function requireParameters(array $parameters): void
    {
        foreach ($parameters as $parameter) {
            if (! isset($this->parameters[$parameter])) {
                throw new ValidationException("Missing required parameter [{$parameter}] on rule [{$this->name}]");
            }
        }
    }

    public function setData(IData $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function typed(): bool
    {
        return $this->typed;
    }

    public function __get(string $name): mixed
    {
        return $this->parameter($name);
    }
}