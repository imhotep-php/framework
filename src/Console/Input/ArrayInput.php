<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

class ArrayInput extends Input
{
    public function __construct(
        protected array $parameters =   [],
        protected ?InputDefinition $definition = null
    )
    {
        parent::__construct($this->definition);
    }

    public function parse(): void
    {
        foreach ($this->parameters as $name => $value) {
            if (str_starts_with($name, '--') && strlen($name) > 2) {
                $this->setOption(substr($name,2), $value);
            }
            elseif (str_starts_with($name, '-') && strlen($name) > 1) {
                $this->setOption(substr($name,2), $value);
            }
            else {
                $this->setArgument($name, $value);
            }
        }
    }

    public function hasRawOption(string $name, bool $onlyParams = false): bool
    {
        return false;
    }

    public function getRawOption(string $name, mixed $default = false, bool $onlyParams = false): mixed
    {
        return null;
    }
}