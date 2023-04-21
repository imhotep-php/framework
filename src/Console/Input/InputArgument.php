<?php

declare(strict_types=1);

namespace Imhotep\Console\Input;

use Imhotep\Contracts\Console\ConsoleException;

class InputArgument
{
    public const REQUIRED = 1;
    public const IS_ARRAY = 2;

    public function __construct(
        protected string $name,
        protected ?int $mode = null,
        protected string $description = '',
        protected string|int|bool|float|array|null $default = null
    )
    {
        if ($mode < 1 | $mode > 3) {
            throw new ConsoleException(sprintf('Argument mode "%s" is not valid.', $mode));
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

    public function isRequired(): bool
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    public function isArray(): bool
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }

    public static function builder(string $name): InputArgumentBuilder
    {
        return new InputArgumentBuilder($name);
    }
}