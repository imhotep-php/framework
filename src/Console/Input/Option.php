<?php

declare(strict_types=1);

namespace Imhotep\Console\Input;

class Option
{
    protected ?string $shortName = null;

    protected ?string $longName = null;

    protected bool $required = false;

    protected bool $hasArg = false;

    protected bool $hasArgs = false;


    public function __construct(string $shortName = null)
    {

    }

    public static function builder(string $name, string $shortcut = null): OptionBuilder
    {
        return new OptionBuilder($name, $shortcut);
    }
}