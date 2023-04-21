<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Console;

interface Command
{
    public function getName(): string;

    public function getDescription(): string;

    public function handle(): void;

    public function getArguments(): array;
}