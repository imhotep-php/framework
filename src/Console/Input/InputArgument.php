<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Console\Utils\SignatureParser;
use Imhotep\Contracts\Console\ConsoleException;
use Stringable;

class InputArgument implements Stringable
{
    public const OPTIONAL = 1 << 0;
    public const REQUIRED = 1 << 1;
    public const ARRAY = 1 << 2;

    public function __construct(
        protected string $name,
        protected ?int $mode = null,
        protected string $description = '',
        protected string|int|bool|float|array|null $default = null
    )
    {
        if (is_null($mode)) {
            $this->mode = static::OPTIONAL;
        }

        $this->setDefault($this->default);
    }

    protected function setDefault(string|int|bool|float|array $default = null): void
    {
        if ($this->isRequired() && ! is_null($default)) {
            throw new ConsoleException('Cannot set default value for required argument.');
        }

        if ($this->isArray()) {
            if (is_null($default)) {
                $default = [];
            }
            elseif (! is_array($default)) {
                $default = [$default];
            }
        }

        $this->default = $default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefault(): string|int|bool|float|array|null
    {
        return $this->default;
    }

    public function isOptional(): bool
    {
        return (bool)($this->mode & static::OPTIONAL);
    }

    public function isRequired(): bool
    {
        return (bool)($this->mode & static::REQUIRED);
    }

    public function isArray(): bool
    {
        return (bool)($this->mode & static::ARRAY);
    }

    public static function builder(string $name): InputArgumentBuilder
    {
        return new InputArgumentBuilder($name);
    }

    public static function fromString(string $expression)
    {
        return SignatureParser::argument($expression);
    }

    public function toString(): string
    {
        $result = $this->name;

        if ($this->isOptional()) {
            $result.= '?';
        }

        if ($this->isArray()) {
            $result.= '*';
        }

        if (is_array($this->default) && ! empty($this->default)) {
            $result.= '='.$this->default[0];
        }
        elseif (! is_array($this->default) && ! is_null($this->default)) {
            $result.= '='.$this->default;
        }

        if (! empty($this->description)) {
            $result.= ' : '.$this->description;
        }

        return $result;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}