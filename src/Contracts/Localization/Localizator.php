<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Localization;

interface Localizator
{
    public function setLocale(string $locale): static;

    public function addNamespace(string $namespace, string|array $path): static;

    public function getLoaded(): array;

    public function get(string $key, array $replace = [], string $locale = null, bool $fallback = true): array|string;
}