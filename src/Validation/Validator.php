<?php

declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Contracts\Localization\Localizator;
use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Contracts\Validation\Validator as ValidatorContract;
use Imhotep\Support\Arr;
use Imhotep\Support\MessageBag;

class Validator implements ValidatorContract
{
    protected array $attributes = [];

    protected array $aliases = [];

    // Все сообщения об ошибках
    protected ?MessageBag $errors = null;

    // Данные которые прошли проверку
    protected array $validData = [];

    // Данные которые не прошли проверку
    protected array $invalidData = [];

    public function __construct(
        protected Factory $factory,
        protected array $inputs, // GET / POST данные
        array $rules, // Условия проверки
        protected array $messages = [], // Сообщения об ошибках
        protected array $customAttributes = []
    )
    {
        foreach ($rules as $inputKey => $inputRules) {
            $this->addAttribute($inputKey, $inputRules);
        }
    }

    protected function addAttribute(string $inputKey, string $rules)
    {
        $attribute = new Attribute($this, $inputKey, $this->resolveRules($rules));
        $this->attributes[$inputKey] = $attribute;
    }

    protected function resolveRules(string $rules): array
    {
        $rules = explode("|", $rules);

        $result = [];
        foreach ($rules as $rule) {
            list($name, $params) = $this->parseRule($rule);
            $result[] = call_user_func_array($this->factory, array_merge([$name], $params));
        }

        return $result;
    }

    protected function parseRule(string $rule): array
    {
        $exp = explode(":", $rule);

        $params = isset($exp[1]) ? explode(",", $exp[1]) : [];

        return [$exp[0], $params];
    }

    // Данные прошли проверку валидации
    public function passes(): bool
    {
        $this->errors = new MessageBag();

        foreach ($this->attributes as $attribute) {
            $this->validateAttribute($attribute);
        }

        return $this->errors->isEmpty();
    }

    // Данные не прошли проверку валидации
    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function validate(): array
    {
        if ($this->fails() ) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    public function validateAttribute(Attribute $attribute): void
    {
        $value = $this->getValue($attribute->getInputKey());

        $valid = true;
        foreach ($attribute->getRules() as $rule) {
            $rule->setAttribute($attribute);

            if ($rule instanceof ModifyValue) {
                $value = $rule->modifyValue($value);
            }

            if (! $rule->check($value)) {
                $valid = false;
                $this->errors->add($attribute->getInputKey(), $rule->message());
                if ($rule->isImplicit()) break;
            }
        }

        if ($valid) {
            $this->setValidData($attribute, $value);
        }
        else {
            $this->setInvalidData($attribute, $value);
        }
    }

    public function getValue(string $key): mixed
    {
        return Arr::get($this->inputs, $key);
    }

    public function validated(): array
    {
        return $this->validData;
    }

    protected function setValidData(Attribute $attribute, $value)
    {
        $this->validData[ $attribute->getInputKey() ] = $value;
    }

    public function failed(): array
    {
        return $this->invalidData;
    }

    protected function setInvalidData(Attribute $attribute, $value)
    {
        $this->invalidData[ $attribute->getInputKey() ] = $value;
    }

    public function errors(): MessageBag
    {
        if (is_null($this->errors)) {
            $this->errors = new MessageBag();
        }

        return $this->errors;
    }

    public function sometimes($attribute, $rules, callable $callback)
    {
        // TODO: Implement sometimes() method.
    }

    public function after($callback)
    {
        // TODO: Implement after() method.
    }

    public function getLocalizator(): Localizator
    {
        return $this->factory->getLocalizator();
    }



    public function alias(string $attribute, string $alias): static
    {
        $this->aliases[$alias] = $attribute;

        return $this;
    }

    public function aliases(array $aliases): static
    {
        $this->aliases = array_merge($this->aliases, $aliases);

        return $this;
    }
}