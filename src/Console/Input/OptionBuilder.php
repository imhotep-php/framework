<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

class OptionBuilder
{
    private ?string $longName = null;
    private ?string $shortName = null;
    private ?string $description = null;
    private bool $argument = false;
    private bool $required = false;
    private bool $optional = false;
    private bool $hasValue = false;
    private bool $hasValues = false;
    private string|int|float|bool|array|null $default = null;

    public function __construct(string $name, string $shortcut = null){
        $this->longName = $name;
        $this->shortName = $shortcut;
    }

    public function argument(): static
    {
        $this->argument = true;

        return $this;
    }

    public function required(): static
    {
        $this->required = true;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function optional(): static
    {
        $this->optional = true;

        return $this;
    }

    public function hasValue(): static
    {
        $this->hasValue = true;

        return $this;
    }

    public function hasValues(): static
    {
        $this->hasValues = true;

        return $this;
    }

    public function default(string|array $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function build(): Option
    {
        return new Option();
    }
};