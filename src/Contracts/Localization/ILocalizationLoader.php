<?php declare(strict_types=1);

namespace Imhotep\Contracts\Localization;

interface ILocalizationLoader
{
    public function paths(): array;

    public function addPath(array|string $path): void;

    public function namespaces(): array;

    public function addNamespace(string $ns, string|array $paths): static;

    public function load(string $locale, string $ns = '*', string $group = '*'): array;
}