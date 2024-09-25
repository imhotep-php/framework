<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Contracts\Validation\Validator;

class Attribute
{
    public function __construct(
        protected Validator $validator,
        protected string $inputKey,
        protected array $rules
    )
    {

    }

    public function getInputKey(): string
    {
        return $this->inputKey;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getValue(string $key = null): mixed
    {
        return $this->validator->getValue($key ?: $this->inputKey);
    }

    public function hasRules(string $key): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->getKey() === $key) return true;
        }

        return false;
    }
}