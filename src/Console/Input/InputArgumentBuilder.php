<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

class InputArgumentBuilder
{
    protected int $mode = InputArgument::OPTIONAL;

    protected string $name;

    protected string $description = '';

    protected string|int|bool|float|array|null $default = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function required(): static
    {
        $this->mode &= ~ InputArgument::OPTIONAL;

        $this->mode = $this->mode | InputArgument::REQUIRED;

        return $this;
    }

    public function array(): static
    {
        $this->mode = $this->mode | InputArgument::ARRAY;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function default(string|int|bool|float|array $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function build(): InputArgument
    {
        return new InputArgument($this->name, $this->mode, $this->description, $this->default);
    }
}