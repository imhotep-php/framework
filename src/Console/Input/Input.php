<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\Input as InputContract;

abstract class Input implements InputContract
{
    protected array $options = [];

    protected array $arguments = [];

    public function __construct(
        protected ?InputDefinition $definition = null
    )
    {
        $this->definition = $definition ?? new InputDefinition();
    }

    public function bind(InputDefinition $definition): void
    {
        $this->definition = $definition;

        $this->options = [];

        $this->arguments = [];

        $this->parse();
    }

    abstract protected function parse(): void;

    public function getFirstArgument(): ?string
    {
        return array_values($this->arguments)[0] ?? null;
    }

    public function getArguments(): array
    {
        return array_merge($this->definition->getArgumentsDefault(), $this->arguments);
    }

    public function getArgument(string $name, mixed $default = null): mixed
    {
        $arguments = $this->getArguments();

        return $arguments[$name] ?? $default;
    }

    public function setArgument(string $name, mixed $value): void
    {
        $this->arguments[$name] = $value;
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getOptions(): array
    {
        return array_merge($this->definition->getOptionsDefault(), $this->options);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        $options = $this->getOptions();

        return $options[$name] ?? $default;
    }

    public function setOption(string $name, mixed $value = null): void
    {
        if (is_array($value)) {
            if (! isset($this->options[$name])) {
                $this->options[$name] = $value;
            }
            else {
                $this->options[$name] = array_merge($this->options[$name], $value);
            }
        }
        else {
            $this->options[$name] = $value;
        }
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    abstract public function hasRawOption(string $name, bool $onlyParams = false): bool;

    abstract public function getRawOption(string $name, mixed $default = false, bool $onlyParams = false): mixed;
}