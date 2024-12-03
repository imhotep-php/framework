<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\ConsoleException;

class InputDefinition
{
    private array $options = [];

    private array $arguments = [];

    private array $shortcuts = [];

    public function __construct(array $definitions = [])
    {
        $this->setDefinitions($definitions);
    }

    protected function setDefinitions(array $definitions): void
    {
        $this->options = [];
        $this->arguments = [];

        foreach ($definitions as $definition) {
            if ($definition instanceof InputOption) {
                $this->addOption($definition);
            }
            elseif ($definition instanceof InputArgument) {
                $this->addArgument($definition);
            }
        }
    }

    public function getOptionsDefault(): array
    {
        $result = [];

        foreach ($this->options as $option) {
            $default = $option->getDefault();

            if (! is_null($default)) {
                $result[ $option->getName() ] = $default;
            }
        }

        return $result;
    }

    public function hasShortcut(string $name): bool
    {
        return isset($this->shortcuts[ $name ]);
    }

    public function getShortcutOption(string $name): InputOption
    {
        if (!$this->hasShortcut($name)) {
            throw new ConsoleException(sprintf('The "-%s" option does not exist.', $name));
        }

        return $this->shortcuts[ $name ];
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[ $name ]);
    }

    public function getOption(string $name): InputOption
    {
        if (!$this->hasOption($name)) {
            throw new ConsoleException(sprintf('The "--%s" option does not exist.', $name));
        }

        return $this->options[ $name ];
    }

    public function addOption(InputOption $option): void
    {
        if (isset($this->options[ $option->getName() ])) {
            throw new ConsoleException(sprintf('An option named "%s" already exists.', $option->getName()));
        }

        $this->options[ $option->getName() ] = $option;

        $shortcuts = $option->getShortcut();

        if (is_array($shortcuts)) {
            foreach ($shortcuts as $shortcut) {
                if (isset($this->shortcuts[$shortcut])) {
                    throw new ConsoleException(sprintf('An option named "%s" already exists.', $shortcut));
                }

                $this->shortcuts[ $shortcut ] = $option;
            }
        }
    }

    public function hasArgument(string|int $key): bool
    {
        $arguments = is_int($key) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$key]);
    }

    /**
     * @return InputArgument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgumentsDefault(): array
    {
        $result = [];

        foreach ($this->arguments as $argument) {
            $default = $argument->getDefault();

            if (! is_null($default)) {
                $result[ $argument->getName() ] = $default;
            }
        }

        return $result;
    }

    public function getArgument(string|int $key): InputArgument
    {
        if (! $this->hasArgument($key)) {
            throw new ConsoleException(sprintf('The "%s" argument does not exist.', $key));
        }

        $arguments = is_int($key) ? array_values($this->arguments) : $this->arguments;

        return $arguments[$key];
    }

    public function getCountArgumentRequired(): int
    {
        $count = 0;

        array_walk($this->arguments, function (InputArgument $argument) use (&$count) {
            if ($argument->isRequired()) $count++;
        });

        return $count;
    }

    public function addArgument(InputArgument $argument): void
    {
        $this->arguments[ $argument->getName() ] = $argument;
    }
}