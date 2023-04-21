<?php

declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\ConsoleException;
use Imhotep\Contracts\Console\Input as InputContract;

class InputArgv implements InputContract
{
    protected InputDefinition $definition;

    protected array $argv = [];

    protected array $parsed = [];

    protected array $options = [];

    protected array $arguments = [];

    public function __construct(array $argv = null, ?InputDefinition $definition = null)
    {
        if (is_null($argv)) {
            $argv = $_SERVER['argv'] ?? [];
        }
        $this->definition = is_null($definition) ? new InputDefinition() : $definition;

        array_shift($argv); // Remove application name

        $this->argv = $argv;
    }

    public function bind(InputDefinition $definition): void
    {
        $this->definition = $definition;
        $this->options = [];
        $this->arguments = [];

        $this->parse();
    }

    protected function parse(): void
    {
        $this->parsed = $this->argv;

        while(null !== $val = array_shift($this->parsed)){
            if (str_starts_with($val, "-")) {
                $this->parseOption($val);
            }
            else {
                $this->parseArgument($val);
            }
        }

        $arguments = $this->definition->getArguments();
        foreach ($arguments as $argument) {
            if ($argument->isRequired() && !array_key_exists($argument->getName(), $this->arguments)) {
                throw new ConsoleException(
                    sprintf('Not enough arguments (missing: "%s").', $argument->getName())
                );
            }
        }
    }

    protected function parseOption($val): void
    {
        if (! preg_match("/^(\-{1,2})([a-z]+)(=([^$]*))?/i", $val, $match)) {
            throw new ConsoleException(sprintf('The "%s" option invalid', $val));
        }

        $isLong = ($match[1] == '--');
        $name = $match[2];
        $value = $match[4] ?? null;

        if (is_null($value) && isset($this->parsed[0])) {
            if ($this->parsed[0] === '=') {
                array_shift($this->parsed);
                $value = array_shift($this->parsed);
            }
            elseif (! str_starts_with($this->parsed[0], '-')) {
                $value = array_shift($this->parsed);
            }
        }

        $option = ($isLong) ? $this->definition->getOption($name) : $this->definition->getShortcutOption($name);

        if ($option->isValueRequired() && is_null($value)) {
            throw new ConsoleException(sprintf('The "%s" option required value.', $name));
        }

        if ($option->isArray()) {
            $value = is_null($value) ? [] : [$value];
        }

        $this->setOption($option->getName(), $value);

        $shortcuts = $option->getShortcut();
        foreach ($shortcuts as $shortcut) {
            $this->setOption($shortcut, $value);
        }
    }

    protected function parseArgument($val): void
    {
        $key = count($this->arguments);

        if (! $this->definition->hasArgument($key)) {
            return;
        }

        $argument = $this->definition->getArgument($key);

        $this->setArgument($argument->getName(), $val);
    }


    public function getFirstArgument()
    {
        return array_values($this->arguments)[0] ?? null;
    }

    public function getArgument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    public function setArgument(string $name, mixed $value): void
    {
        $this->arguments[$name] = $value;
    }

    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function setOption(string $name, mixed $value = null): void
    {
        if (is_array($value)) {
            if (!isset($this->options[$name])) {
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
}