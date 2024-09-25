<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Contracts\Validation\Rule as RuleContract;
use Imhotep\Contracts\Validation\ValidationException;

abstract class Rule implements RuleContract, \ArrayAccess
{
    protected string $key = '';

    public bool $beforeValidate = false;

    protected bool $implicit = false;

    protected string $message = '';

    protected array $parameters = [];

    protected ?Attribute $attribute = null;

    abstract public function check(mixed $value): bool;

    public function message(): string
    {
        $message = str_replace(':attribute', $this->attribute->getInputKey(), $this->message);

        foreach ($this->parameters as $name => $value) {
            $message = str_replace(':'.$name, (string)$value, $message);
        }

        return $message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }


    public function setAttribute(Attribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function isImplicit(): bool
    {
        return $this->implicit;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function parameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function requireParameters(array $parameters): void
    {
        foreach ($parameters as $parameter) {
            if (! isset($this->parameters[$parameter])) {
                throw new ValidationException("Missing required parameter [{$parameter}] on rule [{$this->key}]");
            }
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key = null): static
    {
        $this->key = $key;
        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->parameters);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->parameters[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->parameters[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->parameters[$offset]);
    }

    public function __get(string $key)
    {
        return $this[$key];
    }

    public function __set(string $key, mixed $value)
    {
        $this[$key] = $value;
    }
}