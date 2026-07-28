<?php declare(strict_types=1);

namespace Imhotep\Http\Traits;

use Imhotep\Cookie\Cookie;

trait HasCookies
{
    protected array $cookies = [];

    public function cookies(): array
    {
        return $this->cookies;
    }

    public function cookie(string $name): mixed
    {
        return $this->cookies[$name] ?? null;
    }

    public function setCookie(
        Cookie|string $cookie,
        string        $value = '',
        int           $expires = 0,
        string        $path = '',
        string        $domain = '',
        bool          $secure = false,
        bool          $httpOnly = false,
        ?string       $sameSite = null
    ): static
    {
        if (is_string($cookie)) {
            $cookie = new Cookie($cookie, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
        }

        $this->cookies[$cookie->name()] = $cookie;

        return $this;
    }

    public function removeCookie(Cookie|string $cookie, string $path = '', string $domain = ''): static
    {
        if (is_string($cookie)) {
            $cookie = new Cookie($cookie, '', -2628000, $path, $domain);
        }

        return $this->setCookie($cookie);
    }

    public function clearCookies(): static
    {
        $this->cookies = [];

        return $this;
    }
}