<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Console;

use Imhotep\Console\Input\InputDefinition;

interface Input
{
    public function bind(InputDefinition $definition): void;

    public function getFirstArgument();

    public function getArgument(string $name, mixed $default = null): mixed;

    public function setArgument(string $name, mixed $value): void;

    public function hasArgument(string $name): bool;

    public function getOption(string $name, mixed $default = null): mixed;

    public function setOption(string $name, mixed $value = null): void;

    public function hasOption(string $name): bool;
}