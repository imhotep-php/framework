<?php

namespace Imhotep\Validation;

use Imhotep\Contracts\Validation\IValidator;
use Imhotep\Support\MessageBag;

abstract class Validate
{
    protected IValidator $validator;

    //abstract public function __invoke(...$args): void;

    public function setValidator(IValidator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    protected function errors(): MessageBag
    {
        return $this->validator->errors();
    }

    public function __get(string $name): mixed
    {
        return $this->validator->$name;
    }

    public function __set(string $name, $value): void
    {
        $this->validator->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->validator->$name);
    }
}