<?php

declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Contracts\Localization\Localizator;
use Imhotep\Contracts\Validation\Factory as FactoryContract;
use Imhotep\Contracts\Validation\ValidationException;

class Factory implements FactoryContract
{
    protected Localizator $lang;

    protected array $rules = [
        'required' => Rules\Required::class,
        'required_if' => Rules\RequiredIf::class,
        'required_unless' => Rules\RequiredUnless::class,
        'email' => Rules\Email::class,
        'default' => Rules\Defaults::class,
        'lowercase' => Rules\Lowercase::class,
        'uppercase' => Rules\Uppercase::class,
        'same' => Rules\Same::class,
        'different' => Rules\Different::class,
        'file' => Rules\UploadedFile::class,
        'image' => Rules\UploadedFile::class,
        'max' => Rules\Max::class,
        'min' => Rules\Min::class,
        'dimensions' => Rules\Dimensions::class,
        'nullable' => Rules\Nullable::class,
        'string' => Rules\StringType::class,
        'int' => Rules\IntegerType::class,
        'float' => Rules\FloatType::class,
        'bool' => Rules\BooleanType::class,
        'array' => Rules\ArrayType::class,
    ];

    public function __construct(Localizator $lang)
    {
        $this->lang = $lang;
    }

    public function __invoke(string $rule, ...$params)
    {
        if (! array_key_exists($rule, $this->rules)) {
            throw new ValidationException("Rule [{$rule}] not found");
        }

        return (new $this->rules[$rule])->setParameters($params)->setKey($rule);
    }

    public function make(array $inputs, array $rules, array $messages = [], array $customAttributes = []): Validator
    {
        return new Validator($this, $inputs, $rules, $messages, $customAttributes);
    }

    /**
     * @throws ValidationException
     */
    public function validate(array $inputs, array $rules, array $messages = [], array $customAttributes = []): array
    {
        return (new Validator($this, $inputs, $rules, $messages, $customAttributes))->validate();
    }

    public function extend($rule, $extension, $message = null)
    {
        // TODO: Implement extend() method.
    }

    public function extendImplicit($rule, $extension, $message = null)
    {
        // TODO: Implement extendImplicit() method.
    }

    public function replacer($rule, $replacer)
    {
        // TODO: Implement replacer() method.
    }

    public function setMessages(array $messages): static
    {
        return $this;
    }

    public function getLocalizator(): Localizator
    {
        return $this->lang;
    }
}