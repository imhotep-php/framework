<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Closure;
use Imhotep\Contracts\Localization\ILocalizationLoader;
use Imhotep\Contracts\Localization\ILocalizator;
use Imhotep\Support\Arr;
use InvalidArgumentException;

class Localizator implements ILocalizator
{
    protected ILocalizationLoader $loader;

    protected array $paths = [];

    protected string $locale = '';

    protected string $fallback = '';

    protected array $loaded = [];

    protected array $parsedKeys = [];

    protected array $callbacks = [];

    protected mixed $localesResolver = null;

    protected array $modifiers = [];

    public function __construct(ILocalizationLoader $loader, string $locale, string $fallback)
    {
        $this->loader = $loader;
        $this->locale = $locale;
        $this->fallback = $fallback;
    }


    public function locale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function fallback(): string
    {
        return $this->fallback;
    }

    public function setFallback(string $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function paths(): array
    {
        return $this->loader->paths();
    }

    public function addPath(array|string $path): static
    {
        $this->loader->addPath($path);

        return $this;
    }

    public function namespaces(): array
    {
        return $this->loader->namespaces();
    }

    public function addNamespace(string $namespace, string|array $path): static
    {
        $this->loader->addNamespace($namespace, $path);

        return $this;
    }

    public function loaded(): array
    {
        return $this->loaded;
    }

    public function setLoaded(array $loaded): static
    {
        $this->loaded = $loaded;

        return $this;
    }

    public function add(string $key, string $value, ?string $locale = null, ?string $namespace = null): static
    {
        $locale = $locale ?? $this->locale;
        $namespace = $namespace ?? '*';

        $this->loaded[$locale][$namespace]['*'][$key] = $value;

        return $this;
    }

    public function addMany(array $values, ?string $locale = null, ?string $namespace = null): static
    {
        foreach ($values as $key => $value) {
            $this->add($key, $value, $locale, $namespace);
        }

        return $this;
    }

    public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string
    {
        $locale = $locale ?: $this->locale;

        $value = $this->find($key, $locale, $fallback);

        if (is_null($value)) {
            $this->callCallbacks('not_found', [$key, $locale, $this->fallback]);

            return $key;
        }

        if (empty($replace)) {
            return $value;
        }

        $value = $this->applyParameters($value, $replace);

        return $this->applyExpressions($value, $locale);
    }

    public function has(string $key, ?string $locale = null, bool $fallback = true): bool
    {
        return ! is_null($this->find($key, $locale ?: $this->locale, $fallback, true));
    }

    public function missing(string $key, ?string $locale = null, bool $fallback = true): bool
    {
        return ! $this->has($key, $locale, $fallback);
    }

    protected function find(string $key, string $locale, bool $fallback, bool $silent = false): string|null
    {
        if ($fallback) {
            $locales = array_unique(array_filter([$locale, $this->fallback]));

            if ($this->localesResolver) {
                $locales = call_user_func($this->localesResolver, $locales);

                if (! is_array($locales)) {
                    // @TODO: Need correct message
                    throw new InvalidArgumentException('Callable in setLocaleResolver method needs to be return an array');
                }
            }
        }
        else {
            $locales = [$locale];
        }

        [$ns, $group, $item] = $this->parseKey($key);

        $primaryLocale = null;

        foreach ($locales as $locale) {
            // First load: global flat keys from json files
            $this->load($locale);

            $line = $this->loaded[$locale]['*']['*'][$key] ?? null;

            if (is_string($line)) {
                return $line;
            }

            // Second load: group keys from php files
            if (! is_null($group)) {
                $this->load($locale, $ns, $group);

                $line = Arr::get($this->loaded[$locale][$ns][$group], $item);

                if (is_string($line)) {
                    return $line;
                }
            }

            if (! $silent) {
                $primaryLocale = is_null($primaryLocale);
                $type = $primaryLocale ? 'primary_not_found' : 'fallback_not_found';
                $this->callCallbacks($type, [$key, $locale]);
            }
        }

        return null;
    }

    protected function load(string $locale, string $ns = '*', string $group = '*'): void
    {
        if (! isset($this->loaded[$locale][$ns][$group])) {
            $this->loaded[$locale][$ns][$group] = $this->loader->load($locale, $ns, $group);
        }
    }

    protected function parseKey(string $key): array
    {
        if (isset($this->parsedKeys[$key])) {
            return $this->parsedKeys[$key];
        }

        $namespace = null; $item = $key;
        if (str_contains($key, '::')) {
            $exploded = explode('::', $key);

            if (preg_match("/^[A-Za-z0-9_-]+$/", $exploded[0])) {
            //if (ctype_alnum(str_replace(['_', '-'], '', $exploded[0]))) {
                $namespace = $exploded[0];
                $item = $exploded[1];
            }
        }

        $group = null;
        if (! is_null($item) && str_contains($item, '.')) {
            $exploded = explode('.', $item, 2);

            if (preg_match("/^[A-Za-z0-9_-]+$/", $exploded[0])) {
            //if (ctype_alnum(str_replace(['_', '-'], '', $exploded[0]))) {
                $group = $exploded[0];
                $item = $exploded[1];
            }
        }

        return $this->parsedKeys[$key] = [$namespace ?? '*', $group ?? '*', $item];
    }


    protected function applyParameters(string $text, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $value = (string)$value;

            $text = str_replace(":{$key}", $value,
                $this->applyModifiers($text, $key, $value)
            );
        }

        return $text;
    }

    protected function applyModifiers(string $text, string $key, string $value): string
    {
        foreach ($this->modifiers as $modifier => $callback) {
            $placeholder = ":{$modifier}:{$key}";

            if (str_contains($text, $placeholder)) {
                $modified = is_callable($callback) ? $callback($value) : $callback($value);
                $text = str_replace($placeholder, $modified, $text);
            }
        }

        return $text;
    }

    protected function applyExpressions(string $text, string $locale): string
    {
        $expressions = Expression::parse($text);

        foreach ($expressions as $expression) {
            $text = $expression->apply($text, $locale);
        }

        return $text;
    }

    public function setLocaleResolver(callable $resolver): static
    {
        $this->localesResolver = $resolver;

        return $this;
    }

    public function addModifier(string $name, callable $callback): static
    {
        $this->modifiers[$name] = $callback;

        return $this;
    }

    public function addPlural(string $locale, callable $plural): static
    {
        Expression::$plurals[$locale] = $plural;

        return $this;
    }

    public function handleNotFound(callable $callback): static
    {
        $this->callbacks['not_found'][] = $callback;

        return $this;
    }

    public function handlePrimaryNotFound(callable $callback): static
    {
        $this->callbacks['primary_not_found'][] = $callback;

        return $this;
    }

    public function handleFallbackNotFound(callable $callback): static
    {
        $this->callbacks['fallback_not_found'][] = $callback;

        return $this;
    }

    protected function callCallbacks(string $type, array $parameters): void
    {
        $callbacks = $this->callbacks[$type] ?? [];

        foreach ($callbacks as $callback) {
            $callback(...$parameters);
        }
    }
}