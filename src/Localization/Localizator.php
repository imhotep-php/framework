<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Contracts\Localization\Localizator as LocalizatorContract;
use Imhotep\Support\Str;

// Реализовать:
// Смена locale
// Callback, если перевод основной не найден
// Callback, если перевод запасной не найден
// Callback, если произошла ошибка обработки выражения

class Localizator implements LocalizatorContract
{
    protected FileLoader $loader;

    protected array $paths = [];

    protected string $locale = '';

    protected string $fallback = '';

    protected array $loaded = [];

    protected array $parsedKeys = [];

    protected array $plurals = [];

    protected array $callbacks = [];

    public function __construct(FileLoader $loader, string $locale, string $fallback)
    {
        $this->loader = $loader;
        $this->locale = $locale;
        $this->fallback = $fallback;
    }

    public function get(string $key, array $replace = [], string $locale = null, bool $fallback = true): array|string
    {
        [$ns, $group, $item] = $this->parseKey($key);

        $locale = $locale ?: $this->locale;

        $this->load($locale, $group, $ns);

        $value = $this->getValue($locale, $group, $ns, $item);

        if (is_null($value) && $fallback) {
            $this->load($this->fallback, $group, $ns);

            $value = $this->getValue($this->fallback, $group, $ns, $item);
        }

        if (is_null($value)) {
            $this->callCallbacks('not_found_key', [$key, $locale, $this->fallback]);

            $value = $key;
        }

        if (is_array($value)) {
            return $value;
        }

        $value = $this->makeReplaces($value, $replace);

        return $this->makeExpressions($value, $locale);
    }

    protected function parseKey(string $key): array
    {
        if (isset($this->parsedKeys[$key])) {
            return $this->parsedKeys[$key];
        }

        $namespace = null; $item = $key;
        if (str_contains($key, '::')) {
            list($namespace, $item) = explode('::', $key);
        }

        $group = null;
        if (! is_null($item) && str_contains($item, '.')) {
            list($group, $item) = explode('.', $item, 2);
        }

        return $this->parsedKeys[$key] = [$namespace ?: '*', $group ?: '*', $item];
    }

    protected function load(string $locale, string $group, string $namespace): void
    {
        if (! isset($this->loaded[$namespace][$group][$locale])) {
            $this->loaded[$namespace][$group][$locale] = $this->loader->load($locale, $group, $namespace);
        }
    }

    protected function getValue(string $locale, string $group, string $namespace, string $item): null|string|array
    {
        if ($item === '*') {
            return $this->loaded[$namespace][$group][$locale];
        }

        if (array_key_exists($item, $this->loaded[$namespace][$group][$locale])) {
            return $this->loaded[$namespace][$group][$locale][$item];
        }

        return null;
    }

    protected function makeReplaces(string $string, array $replace): string
    {
        if (empty($replace)) {
            return $string;
        }

        $replaceRules = [];
        foreach ($replace as $key => $val) {
            if (is_string($val)) {
                $replaceRules[':ucfirst:'.$key] = Str::ucfirst($val);
                $replaceRules[':upper:'.$key] = Str::upper($val);
                $replaceRules[':lower:'.$key] = Str::lower($val);
            }

            $replaceRules[':'.$key] = $val;
        }

        return strtr($string, $replaceRules);
    }

    protected function makeExpressions(string $string, string $locale): string
    {
        return preg_replace_callback("/{([^}]+)}/", function ($match) use ($locale) {
            $original = $match[0];

            $values = explode('|', $match[1]);

            $number = trim(array_shift($values));
            if (! is_numeric($number)) return $original;
            $number = (int)$number;

            $isPluralValues = true; $isChoiceValues = true;
            foreach ($values as $key => $value) {
                if (preg_match("/\[(?:([\d*]+),)?([\d*]+)\]/s", $value, $match)) {
                    $isPluralValues = false;
                    $values[$key] = ['choice' => $match, 'value' => trim(str_replace($match[0], "", $value))];
                } else $isChoiceValues = false;
            }

            if ($isPluralValues) {
                $plural = $this->plural($number, $locale);

                if (isset($values[$plural])) {
                    return $values[$plural];
                }

                return $original;
            }
            elseif ($isChoiceValues) {
                $format = function ($value) {
                    if (is_numeric($value)) return (int)$value;
                    if ($value === '*') return $value;
                    return null;
                };

                foreach ($values as $value) {
                    $min = $format($value['choice'][1]);
                    $max = $format($value['choice'][2]);
                    if (is_null($min)) $min = $max;
                    if ($min === '*') $min = $number-1;
                    if ($max === '*') $max = $number+1;

                    if ($number >= $min && $number <= $max) {
                        return $value['value'];
                    }
                }
            }

            return $original;
        }, $string);
    }

    protected function plural(int $number, string $locale): int
    {
        if (isset($this->plurals[$locale])) {
            $plural = $this->plurals[$locale]();

            if (is_int($plural)) {
                return $plural;
            }
        }

        $plural = ($number == 1) ? 0 : 1;

        if ($locale === 'ru') {
            if ($number % 10 === 1 && $number % 100 !== 11) {
                $plural = 0;
            }
            elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
                $plural = 1;
            }
            else {
                $plural = 2;
            }
        }

        return $plural;
    }

    public function addPlural(string $locale, \Closure $plural): static
    {
        $this->plurals[$locale] = $plural;

        return $this;
    }


    public function addNotFoundKeyCallback(\Closure $callback): static
    {
        $this->callbacks['not_found_key'][] = $callback;

        return $this;
    }

    protected function callCallbacks(string $type, array $parameters): void
    {
        $callbacks = $this->callbacks[$type] ?? [];

        foreach ($callbacks as $callback) {
            $callback(...$parameters);
        }
    }


    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    public function setFallback(string $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function addNamespace(string $namespace, string|array $path): static
    {
        $this->loader->addNamespace($namespace, $path);

        return $this;
    }

    public function getLoaded(): array
    {
        return $this->loaded;
    }

    public function setLoaded(array $loaded): static
    {
        $this->loaded = $loaded;

        return $this;
    }
}