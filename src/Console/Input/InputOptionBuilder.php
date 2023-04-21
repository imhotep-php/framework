<?php

declare(strict_types=1);

namespace Imhotep\Console\Input;

class InputOptionBuilder
{
    protected int $mode = 0;
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

    public function valueRequired(): static
    {
        $this->mode = $this->mode ? ($this->mode & 1) : 1;

        return $this;
    }

    public function valueOptional(): static
    {
        $this->mode = $this->mode ? ($this->mode & 2) : 2;

        return $this;
    }

    public function array(): static
    {
        $this->mode = $this->mode ? ($this->mode & 4) : 4;

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