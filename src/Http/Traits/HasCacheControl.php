<?php declare(strict_types=1);

namespace Imhotep\Http\Traits;

use InvalidArgumentException;

trait HasCacheControl
{
    protected array $cacheDirectives = [];

    private const VALID_CACHE_DIRECTIVES = [
        'public', 'private', 'no-cache', 'no-store', 'no-transform',
        'must-revalidate', 'must-understand', 'proxy-revalidate',
        'max-age', 's-maxage', 'immutable',
        'stale-while-revalidate','stale-if-error',
    ];

    private const CACHE_DIRECTIVES_WITH_VALUE = [
        'max-age', 's-maxage', 'stale-while-revalidate', 'stale-if-error'
    ];

    public function cacheControl(?string $directive = null): string|true|int|null
    {
        if ($directive !== null) {
            return $this->cacheDirectives[$directive] ?? null;
        }

        $parts = [];

        ksort($this->cacheDirectives);

        foreach ($this->cacheDirectives as $directive => $value) {
            $parts[] = $value === true ? $directive : sprintf('%s=%d', $directive, $value);
        }

        return implode(', ', $parts);
    }

    public function setCacheControl(string $directive, ?int $value = null, bool $force = false): static
    {
        $directive = strtolower($directive);

        if ($force) {
            $this->cacheDirectives[$directive] = is_null($value) ? true : $value;

            return $this;
        }

        if (!in_array($directive, self::VALID_CACHE_DIRECTIVES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid Cache-Control directive: %s', $directive)
            );
        }

        if (in_array($directive, self::CACHE_DIRECTIVES_WITH_VALUE, true)) {
            if (!is_int($value) || $value < 0) {
                throw new InvalidArgumentException(
                    sprintf('Directive %s requires a non-negative integer value', $directive)
                );
            }

            $this->cacheDirectives[$directive] = $value;
        }
        else {
            if ($value !== null) {
                throw new InvalidArgumentException(
                    sprintf('Directive %s does not accept a value', $directive)
                );
            }

            $this->cacheDirectives[$directive] = true;
        }

        $this->removeConflictingDirectives($directive);

        return $this;
    }

    public function hasCacheDirective(string $directive): bool
    {
        return isset($this->cacheDirectives[$directive]);
    }

    public function removeCacheDirective(string $directive): static
    {
        unset($this->cacheDirectives[$directive]);

        return $this;
    }

    public function clearCacheControl(): static
    {
        $this->cacheDirectives = [];

        return $this;
    }

    private function removeConflictingDirectives(string $newDirective): void
    {
        $conflicts = [
            'no-store' => ['public', 'private', 'max-age', 's-maxage'],
            'no-cache' => ['public', 'private', 'max-age', 's-maxage', 'immutable'],
            'private' => ['public'],
            'public' => ['private'],
        ];

        if (isset($conflicts[$newDirective])) {
            foreach ($conflicts[$newDirective] as $conflict) {
                unset($this->cacheDirectives[$conflict]);
            }
        }
    }

    public function disableCache(): static
    {
        return $this
            ->clearCacheControl()
            ->setCacheControl('no-store')
            ->setCacheControl('no-cache')
            ->setCacheControl('must-revalidate');
    }

    public function enableBrowserCache(int $maxAge, bool $public = true): static
    {
        $this->clearCacheControl();

        if ($public) {
            $this->setCacheControl('public');
        } else {
            $this->setCacheControl('private');
        }

        return $this
            ->setCacheControl('max-age', $maxAge)
            ->setCacheControl('immutable');
    }

    public function enableCdnCache(int $browserMaxAge, int $cdnMaxAge): static
    {
        return $this
            ->clearCacheControl()
            ->setCacheControl('public')
            ->setCacheControl('max-age', $browserMaxAge)
            ->setCacheControl('s-maxage', $cdnMaxAge);
    }
}
