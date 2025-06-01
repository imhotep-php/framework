<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Closure;
use Generator;
use Imhotep\Contracts\Validation\IModifyValue;
use Imhotep\Contracts\Validation\IValidator;
use Imhotep\Http\UploadedFile;
use Imhotep\Localization\Localizator;
use Imhotep\Support\MessageBag;
use Imhotep\Validation\Data\Data;
use Imhotep\Validation\Data\InputData;
use Imhotep\Validation\Rules\AbstractRule;

class Validator implements IValidator
{
    // Входные данные для валидации
    protected InputData $data;

    // Данные которые прошли проверку
    protected ?Data $validatedData = null;

    // Данные которые не прошли проверку
    protected ?Data $failedData = null;

    // Все сообщения об ошибках
    protected ?MessageBag $errors = null;

    protected bool $stopOnFirstFailure = false;

    protected array $afters = [];

    public function __construct(
        protected Factory $factory,
        array             $data, // GET / POST данные
        protected array   $rules, // Условия проверки
        protected array   $messages = [], // Сообщения об ошибках
        protected array   $aliases = []
    )
    {
        $this->data = new InputData($data);
    }

    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): static
    {
        $this->stopOnFirstFailure = $stopOnFirstFailure;

        return $this;
    }

    public function passes(): bool
    {
        $this->validatedData = new Data();
        $this->failedData = new Data();
        $this->errors = new MessageBag();

        foreach ($this->attributes() as $attribute) {
            if ($this->validateAttribute($attribute)) {
                continue;
            }

            if ($this->stopOnFirstFailure) {
                break;
            }
        }

        if ($this->errors->isEmpty()) {
            foreach ($this->afters as $after) {
                if ($after() === false) continue;

                if ($this->stopOnFirstFailure) {
                    break;
                }
            }
        }

        return $this->errors->isEmpty();
    }

    // Данные не прошли проверку валидации
    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function validate(): Data
    {
        if ($this->fails() ) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    public function data(): InputData
    {
        return $this->data;
    }

    public function validated(): Data
    {
        return $this->validatedData ?
            $this->validatedData : $this->validatedData = new Data();
    }

    public function failed(): Data
    {
        return $this->failedData ?
            $this->failedData : $this->failedData = new Data();
    }

    public function errors(): MessageBag
    {
        return $this->errors ?
            $this->errors : $this->errors = new MessageBag();
    }

    public function sometimes($attribute, $rules, callable $callback)
    {
        // TODO: Implement sometimes() method.
    }

    public function after(array|string|Closure $validates): static
    {
        if (! is_array($validates)) {
            $validates = [$validates];
        }

        foreach ($validates as $validate) {
            if (is_subclass_of($validate, Validate::class)) {
                $this->afters[] = function () use ($validate) {
                    $validate = app($validate);
                    $validate->setValidator($this);
                    app()->call($validate);
                };
            }
            elseif ($validate instanceof Closure) {
                $this->afters[] = fn() => app()->call($validate->bindTo($this, $this));
            }
        }

        return $this;
    }

    protected function attributes(): Generator
    {
        foreach ($this->rules as $attribute => $rules) {
            if (! str_contains($attribute, '.')) {
                yield new Attribute($attribute, $attribute, $rules);

                continue;
            }

            $wildcards = $this->data->wildcard($attribute);

            foreach ($wildcards as $wildcard) {
                yield new Attribute($wildcard, $attribute, $rules);
            }
        }
    }

    protected function validateAttribute(Attribute $attribute): bool
    {
        $value = $originValue = $this->data->get($attribute->key());

        $validated = true;

        foreach ($attribute->rules() as $rule) {
            if (! $rule->implicit()) {
                if ($value === '' || (is_null($value) && $attribute->hasRule('nullable')) ) {
                    continue;
                }
            }

            $rule->setData($this->data);

            if ($rule instanceof IModifyValue) {
                $value = $rule->modifyValue($value);
            }

            if (! $rule->check($value)) {
                $validated = false;

                $this->errors->add($attribute->key(), $this->prepareMessage($attribute->name(), $rule, $value));

                if ($attribute->bail()) break;
            }
        }

        if ($validated) {
            $this->validatedData->set($attribute->key(), $value);
        }
        else {
            $this->failedData->set($attribute->key(), $originValue);
        }

        return $validated;
    }

    protected function prepareMessage(string $attribute, AbstractRule $rule, mixed $value): string
    {
        if ($rule->message()) {
            return $this->replaceMessageParameters($rule->message(), $attribute, $rule->parameters());
        }

        $key = $attribute.'.'.$rule->name();

        if (isset($this->messages[$key]) && is_string($this->messages[ $key ])) {
            return $this->replaceMessageParameters($this->messages[$key], $attribute, $rule->parameters());
        }

        if ($localizator = $this->factory->getLocalizator()) {
            $key = 'validation.custom.'.$attribute.'.'.$rule->name();
            if ($localizator->has($key)) {
                return $this->replaceMessageParameters($localizator->get($key), $attribute, $rule->parameters());
            }

            $key = 'validation.'.$rule->name();

            if ($rule->typed()) {
                if (is_numeric($value)) {
                    $key.= '.numeric';
                }
                elseif (is_array($value)) {
                    $key.= '.array';
                }
                elseif ($value instanceof UploadedFile) {
                    $key.= '.file';
                }
                else {
                    $key.= '.string';
                }
            }

            if ($localizator->has($key)) {
                return $this->replaceMessageParameters($localizator->get($key), $attribute, $rule->parameters());
            }
        }

        return $rule->name();
    }

    protected function replaceMessageParameters(string $message, string $attribute, array $parameters): string
    {
        foreach ($parameters as $name => $value) {
            if (is_string($value)){
                continue;
            }
            elseif (is_array($value)) {
                $parameters[$name] = implode(", ", $value);
            }
            else {
                $parameters[$name] = (string)$value;
            }
        }

        $parameters['attribute'] = $this->aliases[$attribute] ?? $attribute;

        $keys = array_map(fn($key) => ':'.$key, array_keys($parameters));

        return str_replace($keys, array_values($parameters), $message);
    }

    public function getLocalizator(): ?Localizator
    {
        return $this->factory->getLocalizator();
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key];
    }
}