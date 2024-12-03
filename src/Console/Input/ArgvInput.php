<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\ConsoleException;

class ArgvInput extends Input
{
    protected array $argv = [];

    protected array $parsed = [];

    public function __construct(array $argv = null, ?InputDefinition $definition = null)
    {
        parent::__construct($definition);

        if (is_null($argv)) {
            $argv = $_SERVER['argv'] ?? [];
        }

        array_shift($argv); // Remove application name

        $this->argv = $argv;
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

        if (! $this->definition->hasOption($name) && ! $this->definition->hasShortcut($name)) {
            return;
        }

        $option = ($isLong) ? $this->definition->getOption($name) : $this->definition->getShortcutOption($name);

        if ($option->isValueRequired() && is_null($value)) {
            throw new ConsoleException(sprintf('The "%s" option required value.', $name));
        }

        if ($option->isArray()) {
            $value = is_null($value) ? [] : [$value];
        }

        $this->setOption($option->getName(), $value);

        $shortcuts = (array)$option->getShortcut();
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

    public function hasRawOption(string $name, bool $onlyParams = false): bool
    {
        if (empty($name)) return false;

        foreach ($this->argv as $argv) {
            if ($onlyParams && '--' === $argv) {
                return false;
            }

            if ($argv === $name) {
                return true;
            }

            // Options with values:
            // For long options, test for '--option=' at beginning
            // For short options, test for '-o' at beginning
            $leading = str_starts_with($name, '--') ? $name.'=' : $name;
            if (str_starts_with($argv, $leading)) {
                return true;
            }
        }

        return false;
    }

    public function getRawOption(string $name, mixed $default = false, bool $onlyParams = false): mixed
    {
        if (empty($name)) return value($default);

        $argv = $this->argv;

        while (! empty($argv)) {
            $value = array_shift($argv);

            if ($onlyParams && '--' === $value) {
                return value($default);
            }

            if ($value === $name) {
                return array_shift($argv);
            }

            // Options with values:
            // For long options, test for '--option=' at beginning
            // For short options, test for '-o' at beginning
            $leading = str_starts_with($name, '--') ? $name.'=' : $name;
            if (str_starts_with($value, $leading)) {
                return substr($value, strlen($leading));
            }
        }

        return value($default);
    }

    public function argv(): array
    {
        return $this->argv;
    }
}