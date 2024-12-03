<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Console\Utils\SignatureParser;
use Imhotep\Contracts\Console\ConsoleException;
use Stringable;

class InputOption implements Stringable
{
    // This is the default option without value (e.g. --flag)
    public const VALUE_NONE = 1 << 0;

    // The option has optional value (e.g. --flag or --flag=en)
    public const VALUE_OPTIONAL = 1 << 1;

    // The option has required value (e.g. --flag=en)
    public const VALUE_REQUIRED = 1 << 2;

    // The option has array value (e.g. --flag=en --flag=ru)
    public const VALUE_ARRAY = 1 << 3;

    // The option have positive or negative value (e.g. --flag or --no-flag).
    public const VALUE_NEGATABLE = 1 << 4;

    public function __construct(
        protected string $name,
        protected string|array|null $shortcut = null,
        protected int $mode = 0,
        protected string $description = '',
        protected string|bool|int|float|array|null $default = null
    )
    {
        $this->setName($name);
        $this->setShortcut($shortcut);
        $this->setDefault($default);
    }
    protected function setName($name): void
    {
        $name = ltrim(strtolower($name), '-');

        if (empty($name)){
            throw new ConsoleException('An option name cannot be empty.');
        }

        $this->name = $name;
    }

    protected function setShortcut($shortcut): void
    {
        if (empty($shortcut)) {
            $shortcut = null;
        }

        if (! is_null($shortcut)) {
            $shortcut = explode("|", $shortcut);

            foreach($shortcut as $key => $val){
                if(!is_string($val) || strlen($val) != 1 || !preg_match('/^([A-z])$/', $val)){
                    $shortcut[$key] = '';
                }
            }

            $shortcut = array_filter($shortcut);

            if (empty($shortcut)) {
                $shortcut = null;
            }
        }

        $this->shortcut = $shortcut;
    }

    protected function setDefault(mixed $default): void
    {
        if ($this->isValueNone() && ! is_null($default)) {
            throw new ConsoleException('Cannot set default value for none value option ['.$this->name.'].');
        }

        if ($this->isValueRequired() && ! is_null($default)) {
            throw new ConsoleException('Cannot set default value for required value option ['.$this->name.'].');
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

    public function getShortcut(): ?array
    {
        return $this->shortcut;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefault(): string|int|bool|float|array|null
    {
        return $this->default;
    }

    public function isValueNone(): bool
    {
        return (bool)($this->mode & static::VALUE_NONE);
    }

    public function isValueRequired(): bool
    {
        return (bool)($this->mode & static::VALUE_REQUIRED);
    }

    public function isValueOptional(): bool
    {
        return (bool)($this->mode & static::VALUE_OPTIONAL);
    }

    public function isArray(): bool
    {
        return (bool)($this->mode & static::VALUE_ARRAY);
    }

    public function isNegatable(): bool
    {
        return (bool)($this->mode & static::VALUE_NEGATABLE);
    }

    public static function builder(string $name, string $shortcut = null): InputOptionBuilder
    {
        return new InputOptionBuilder($name, $shortcut);
    }

    public static function fromString(string $expression)
    {
        return SignatureParser::option($expression);
    }

    public function toString(): string
    {
        $name = [];
        if (is_array($this->shortcut)) $name[] = $this->shortcut[0];
        elseif (is_string($this->shortcut)) $name[] = $this->shortcut;
        $name[] = $this->name;

        $result = '--'.implode("|", $name);

        if ($this->isValueOptional()) $result.= '=?';

        if ($this->isValueRequired()) $result.= '=';

        if ($this->isArray()) $result.= '*';

        if (is_array($this->default) && ! empty($this->default)) {
            $result.= $this->default[0];
        }
        elseif (! is_array($this->default) && ! is_null($this->default)) {
            $result.= $this->default;
        }

        if (! empty($this->description)) {
            $result.= ' : '.$this->description;
        }

        return $result;
    }

    public function __toString()
    {
        return $this->toString();
    }
}