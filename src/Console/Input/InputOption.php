<?php

declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\ConsoleException;

class InputOption
{
    public const VALUE_REQUIRED = 1;

    public const VALUE_OPTIONAL = 2;

    public const VALUE_IS_ARRAY = 4;

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
        if ($this->mode == 0 && ! is_null($default)) {
            throw new ConsoleException('Cannot set default value for none value option.');
        }

        if ($this->isValueRequired() && ! is_null($default)) {
            throw new ConsoleException('Cannot set default value for required value option.');
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

    public function isValueRequired(): bool
    {
        return self::VALUE_REQUIRED === (self::VALUE_REQUIRED & $this->mode);
    }

    public function isValueOptional(): bool
    {
        return self::VALUE_OPTIONAL === (self::VALUE_OPTIONAL & $this->mode);
    }

    public function isArray(): bool
    {
        return self::VALUE_IS_ARRAY === (self::VALUE_IS_ARRAY & $this->mode);
    }

    public static function builder(string $name, string $shortcut = null): InputOptionBuilder
    {
        return new InputOptionBuilder($name, $shortcut);
    }
}