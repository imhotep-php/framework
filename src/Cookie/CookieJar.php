<?php

declare(strict_types=1);

namespace Imhotep\Cookie;

class CookieJar
{
    protected string $path = '/';

    protected string $domain = '';

    protected bool $secure = false;

    protected bool $httpOnly = true;

    protected string $sameSite = 'lax';

    protected array $queued = [];

    public function make(string $name, string $value, int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null): Cookie
    {
        $path = is_null($path) ? $this->path : $path;
        $domain = is_null($domain) ? $this->domain : $domain;
        $secure = is_null($secure) ? $this->secure : $secure;
        $httpOnly = is_null($httpOnly) ? $this->httpOnly : $httpOnly;
        $sameSite = is_null($sameSite) ? $this->sameSite : $sameSite;

        $expires = ($minutes === 0) ? 0 : time() + ($minutes * 60);

        return new Cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function forever(string $name, string $value, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null): Cookie
    {
        return $this->make($name, $value, 576000, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function forget($name, $path = null, $domain = null): Cookie
    {
        return $this->make($name, '', -2628000, $path, $domain);
    }

    public function hasQueued(string $name, string $path = null): bool
    {
        return ! is_null($this->queued($name, null, $path));
    }

    public function queue(Cookie|string $cookie, string $value = '', int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null): void
    {
        if (is_string($cookie)) {
            $cookie = $this->make($cookie, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
        }

        if (! isset($this->queued[$cookie->getName()])) {
            $this->queued[$cookie->getName()] = [];
        }

        $this->queued[$cookie->getName()][$cookie->getPath()] = $cookie;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @param string|null $path
     * @return mixed|Cookie
     */
    public function queued(string $name, mixed $default = null, string $path = null): mixed
    {
        if (! isset($this->queued[$name])) {
            return $default;
        }

        $queued = $this->queued[$name];

        if (is_null($path)) {
            return end($queued);
        }

        return $queued[$path] ?? $default;
    }

    public function unqueue(string $name, string $path = null): void
    {
        if (is_null($path)) {
           unset($this->queued[$name]);

           return;
        }

        unset($this->queued[$name][$path]);

        if (empty($this->queued[$name])) {
            unset($this->queued[$name]);
        }
    }

    public function expire(string $name, string $path = null, string $domain = null): void
    {
        $this->queue($this->forget($name, $path, $domain));
    }

    public function getQueuedCookies(): array
    {
        $cookies = [];

        foreach ($this->queued as $queued) {
            foreach ($queued as $cookie) {
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    public function flushQueuedCookies(): static
    {
        $this->queued = [];

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    public function setDefault(string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null): static
    {
        if (! is_null($path)) $this->path = $path;
        if (! is_null($domain)) $this->domain = $domain;
        if (! is_null($secure)) $this->secure = $secure;
        if (! is_null($httpOnly)) $this->httpOnly = $httpOnly;
        if (! is_null($sameSite)) $this->sameSite = $sameSite;

        return $this;
    }
}