<?php

declare(strict_types=1);

namespace Imhotep\Cookie;

class Cookie
{
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
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false,
        string $sameSite = null
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
                $str .= '; expires='.gmdate('D, d M Y H:i:s T', $this->expires).'; Max-Age='.$this->getMaxAge();
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

    /**
     * @throws CookieException
     */
    public function name(string $name = null)
    {
        if (is_null($name)) {
            return $this->getName();
        }

        $this->setName($name);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @throws CookieException
     */
    public function setName(string $name): void
    {
        if (! preg_match("/^([A-z0-9._-]+)$/i", $name)) {
            throw new CookieException('The "name" parameter value contains illegal characters.');
        }

        $this->name = $name;
    }

    public function value(string $value = null)
    {
        if (is_null($value)) {
            return $this->getValue();
        }

        $this->setValue($value);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * @param int $expires
     */
    public function setExpires(int $expires): void
    {
        $this->expires = max($expires, 0);
    }

    /**
     * @return int
     */
    public function getMaxAge(): int
    {
        return max($this->expires - time(), 0);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = empty($path) ? '/' : $path;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param bool $secure
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @param bool $httpOnly
     */
    public function setHttpOnly(bool $httpOnly): void
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * @return string
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * @param string|null $sameSite
     * @throws CookieException
     */
    public function setSameSite(?string $sameSite): void
    {
        if (! in_array($sameSite, [self::SAMESITE_NONE, self::SAMESITE_LAX, self::SAMESITE_STRICT, null])) {
            throw new CookieException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;
    }
}