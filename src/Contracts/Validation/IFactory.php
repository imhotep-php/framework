<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

use Closure;
use Imhotep\Contracts\Localization\Localizator;

interface IFactory
{
    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $aliases
     * @return IValidator
     */
    public function make(array $data, array $rules, array $messages = [], array $aliases = []): IValidator;

    public function validate(array $data, array $rules, array $messages = [], array $aliases = []): IData;

    /**
     * Register a custom validator extension.
     *
     * @param  string  $rule
     * @param  Closure|string  $extension
     * @return static
     */
    public function extend(string $rule, Closure|string $extension): static;

    /**
     * Register a custom implicit validator message replacer.
     *
     * @param  string  $rule
     * @param  Closure|string  $replacer
     * @return void
     */
    public function replacer(string $rule, Closure|string $replacer): void;

    public function setMessages(array $messages): static;

    public function addMessages(array $messages): static;

    public function forgetMessages(): static;

    public function setAliases(array $aliases): static;

    public function addAliases(array $aliases): static;

    public function forgetAliases(): static;

    public function setLocalizator(Localizator $localizator): static;

    public function getLocalizator(): ?Localizator;
}
