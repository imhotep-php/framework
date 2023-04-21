<?php

declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Contracts\Localization\Localizator as LocalizatorContract;

class Localizator implements LocalizatorContract
{
    protected array $paths = [];

    protected string $locale = 'en';

    protected string $fallback = 'en';

    protected array $loaded = [];

    public function __construct(string|array $paths, string $locale, string $fallback)
    {
        $this->paths = is_array($paths) ? $paths : [$paths];
        $this->locale = $locale;
        $this->fallback = $fallback;

        $this->load();
    }

    protected function load(): void
    {
        foreach ($this->paths as $path) {
            $this->loadFrom($path);
        }
    }

    public function loadFrom(string $path): void
    {
        $locales = array_diff(scandir($path), ['..','.']);

        foreach ($locales as $locale) {
            if (! is_dir($path.DIRECTORY_SEPARATOR.$locale)) {
                continue;
            }

            if (! preg_match('/([a-z-]{2,})/i', $locale)) {
                continue;
            }

            $this->loadLocaleFrom($locale, $path.DIRECTORY_SEPARATOR.$locale);
        }
    }

    public function loadLocaleFrom(string $locale, string $path): void
    {
        $filenames = array_diff(scandir($path), ['..','.']);

        foreach ($filenames as $filename) {
            if (! is_file($path.DIRECTORY_SEPARATOR.$filename)) {
                continue;
            }

            if (! str_ends_with($filename, '.php')) {
                continue;
            }

            if (! preg_match('/([a-z]{2,})/i', $locale)) {
                continue;
            }

            if (! isset($this->loaded[$locale])) {
                $this->loaded[$locale] = [];
            }

            $group = basename($filename, '.php');
            if (! isset($this->loaded[$locale][$group])) {
                $this->loaded[$locale][$group] = [];
            }

            $this->loaded[$locale][$group] = array_replace(
                $this->loaded[$locale][$group],
                require $path.DIRECTORY_SEPARATOR.$filename
            );
        }
    }

    public function get(string $key): array|string
    {
        $vars = explode(".", $key);
        if (count($vars) === 1) {
            if (isset($this->loaded[$this->locale][$key])) {
                return $this->loaded[$this->locale][$key];
            }

            if (isset($this->loaded[$this->fallback][$key])) {
                return $this->loaded[$this->fallback][$key];
            }

            return [];
        }

        $group = array_shift($vars);
        $lang = implode(".", $vars);

        if (isset($this->loaded[$this->locale][$group][$lang])) {
            return $this->loaded[$this->locale][$group][$lang];
        }

        if (isset($this->loaded[$this->fallback][$group][$lang])) {
            return $this->loaded[$this->fallback][$group][$lang];
        }

        return $key;
    }
}