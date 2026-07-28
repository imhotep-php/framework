<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Closure;
use Imhotep\Contracts\Localization\ILocalizator;
use Imhotep\Contracts\Validation\IData;
use Imhotep\Contracts\Validation\IFactory;
use Imhotep\Contracts\Validation\IValidationRule;
use Imhotep\Validation\Rules\AbstractRule;
use InvalidArgumentException;

class Factory implements IFactory
{
    protected ?ILocalizator $lang;

    protected array $messages = [];

    protected array $aliases = [];

    public function __construct(?ILocalizator $lang = null)
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
        if (is_string($extension) &&
            !is_subclass_of($extension, AbstractRule::class) &&
            !is_subclass_of($extension, IValidationRule::class)) {

            throw new InvalidArgumentException(
                sprintf('Rule [%s] must extend %s or implement %s',
                    $rule,
                    AbstractRule::class,
                    IValidationRule::class
                )
            );
        }

        $rule = strtolower(trim($rule));

        if (!preg_match('/^[a-z_]+$/', $rule)) {
            throw new InvalidArgumentException(
                sprintf('Rule name [%s] must contain only English letters and underscores (e.g., "custom_rule")', $rule)
            );
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

    public function setLocalizator(ILocalizator $localizator): static
    {
        $this->lang = $localizator;

        return $this;
    }

    public function getLocalizator(): ?ILocalizator
    {
        return $this->lang;
    }
}