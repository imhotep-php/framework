<?php declare(strict_types=1);

namespace Imhotep\Contracts\Localization;

interface ILocalizator
{
    public function locale(): string;

    public function setLocale(string $locale): static;

    public function fallback(): string;

    public function setFallback(string $fallback): static;

    public function addNamespace(string $namespace, string|array $path): static;

    public function loaded(): array;

    public function setLoaded(array $loaded): static;

    public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string;

    public function has(string $key, ?string $locale = null, bool $fallback = true): bool;

    public function missing(string $key, ?string $locale = null, bool $fallback = true): bool;
}