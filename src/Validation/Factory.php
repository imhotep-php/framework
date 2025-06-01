<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Closure;
use Imhotep\Contracts\Localization\Localizator;
use Imhotep\Contracts\Validation\IData;
use Imhotep\Contracts\Validation\IFactory;
use Imhotep\Validation\Rules\AbstractRule;

class Factory implements IFactory
{
    protected ?Localizator $lang;

    protected array $messages = [];

    protected array $aliases = [];

    public function __construct(Localizator $lang = null)
    {
        $this->lang = $lang;
    }

    public function make(array $data, array $rules, array $messages = [], array $aliases = []): Validator
    {
        return new Validator($this, $data, $rules, $this->messages + $messages, $this->aliases + $aliases);
    }

    public function validate(array $data, array $rules, array $messages = [], array $aliases = []): IData
    {
        return $this->make($data, $rules, $messages, $aliases)->validate();
    }

    public function extend(string $rule, string|Closure $extension): static
    {
        if (is_string($extension) && ! is_subclass_of($extension, AbstractRule::class)) {
            throw new \InvalidArgumentException("Extension [{$rule}] not a valid");
        }

        RuleParser::$rules[$rule] = $extension;

        return $this;
    }

    public function replacer(string $rule, Closure|string $replacer): void
    {
        // TODO: Implement replacer() method.
    }

    public function setMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    public function addMessages(array $messages): static
    {
        $this->messages += $messages;

        return $this;
    }

    public function forgetMessages(): static
    {
        $this->messages = [];

        return $this;
    }

    public function setAliases(array $aliases): static
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function addAliases(array $aliases): static
    {
        $this->aliases += $aliases;

        return $this;
    }

    public function forgetAliases(): static
    {
        $this->aliases = [];

        return $this;
    }

    public function setLocalizator(Localizator $localizator): static
    {
        $this->lang = $localizator;

        return $this;
    }

    public function getLocalizator(): ?Localizator
    {
        return $this->lang;
    }
}