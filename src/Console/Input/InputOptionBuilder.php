<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

class InputOptionBuilder
{
    protected int $mode = InputOption::VALUE_NONE;
    protected string $name;
    protected string $shortcut = '';
    protected string $description = '';
    protected string|int|bool|float|array|null $default = null;

    public function __construct(string $name, string $shortcut = null)
    {
        $this->name = $name;

        if (! is_null($shortcut)) {
            $this->shortcut = $shortcut;
        }
    }

    public function valueOptional(): static
    {
        $this->mode &= ~ InputOption::VALUE_NONE;

        $this->mode = $this->mode | InputOption::VALUE_OPTIONAL;

        return $this;
    }

    public function valueRequired(): static
    {
        $this->mode &= ~ InputOption::VALUE_NONE;

        $this->mode = $this->mode | InputOption::VALUE_REQUIRED;

        return $this;
    }

    public function array(): static
    {
        $this->mode = $this->mode | InputOption::VALUE_ARRAY;

        return $this;
    }

    public function negatable(): static
    {
        $this->mode = $this->mode | InputOption::VALUE_NEGATABLE;

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

    public function build(): InputOption
    {
        return new InputOption($this->name, $this->shortcut, $this->mode, $this->description, $this->default);
    }
}