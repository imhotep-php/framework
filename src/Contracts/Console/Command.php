<?php declare(strict_types=1);

namespace Imhotep\Contracts\Console;

interface Command
{
    public static function getDefaultName(): string;

    public static function getDefaultDescription(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function handle(): int;

    public function getArguments(): array;

    public function hasArguments(): bool;

    public function getOptions(): array;

    public function hasOptions(): bool;
}