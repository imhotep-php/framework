<?php declare(strict_types=1);

namespace Imhotep\Cookie;

use BadMethodCallException;
use Imhotep\Support\Traits\DeprecatedGetters;
use InvalidArgumentException;

class Cookie
{
    use DeprecatedGetters;

    public const SAMESITE_NONE = 'none';
    public const SAMESITE_LAX = 'lax';
    public const SAMESITE_STRICT = 'strict';

    protected string $name;
    protected string $value;
    protected int $expires;
    protected string $path;
    protected string $domain;
    protected bool $secure;
    protected bool $httpOnly;
    protected ?string $sameSite;

    public function __construct(
        string  $name,
        string  $value = '',
        int     $expires = 0,
        string  $path = '',
        string  $domain = '',
        bool    $secure = false,
        bool    $httpOnly = false,
        ?string $sameSite = null
    )
    {
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;

        $this->setName($name);
        $this->setValue($value);
        $this->setPath($path);
        $this->setExpires($expires);
        $this->setSameSite($sameSite);
    }

    public function __toString(): string
    {
        $str = $this->name;
        $str .= '=';

        if (empty($this->value)) {
            $str .= 'deleted; expires='.gmdate('D, d M Y H:i:s T', time() - 3600).'; Max-Age=0';
        } else {
            $str .= rawurlencode($this->value);

            if ($this->expires > 0) {
                $str .= '; expires='.gmdate('D, d M Y H:i:s T', $this->expires).'; Max-Age='.$this->maxAge();
            }
        }

        if ($this->path) {
            $str .= '; path='.$this->path;
        }

        if ($this->domain) {
            $str .= '; domain='.$this->domain;
        }

        if ($this->secure) {
            $str .= '; secure';
        }

        if ($this->httpOnly) {
            $str .= '; httponly';
        }

        if (! is_null($this->sameSite)) {
            $str .= '; samesite='.$this->sameSite;
        }

        return $str;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        if (! preg_match("/^[A-Za-z0-9._-]+$/i", $name)) {
            throw new InvalidArgumentException('The "name" parameter value contains illegal characters.');
        }

        $this->name = $name;

        return $this;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expires(): int
    {
        return $this->expires;
    }

    public function setExpires(int $expires): static
    {
        if ($expires !== 0) {
            $expires = time() + $expires;
        }

        $this->expires = $expires;

        return $this;
    }

    public function maxAge(): int
    {
        return max($this->expires - time(), 0);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = empty($path) ? '/' : $path;

        return $this;
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function setSecure(bool $secure): static
    {
        $this->secure = $secure;

        return $this;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function setHttpOnly(bool $httpOnly): static
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    public function sameSite(): ?string
    {
        return $this->sameSite;
    }

    public function setSameSite(?string $sameSite): static
    {
        if (is_string($sameSite)) {
            $sameSite = strtolower($sameSite);
        }

        if (! in_array($sameSite, [self::SAMESITE_NONE, self::SAMESITE_LAX, self::SAMESITE_STRICT, null])) {
            throw new InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;

        return $this;
    }

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}