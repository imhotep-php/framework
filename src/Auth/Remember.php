<?php declare(strict_types = 1);

namespace Imhotep\Auth;

class Remember
{
    protected static array $segments = [];

    protected string $hash = '';

    public function __construct(
        public readonly string $id,
        public readonly string $password,
        public readonly string $secret,
        public readonly string $lifetime
    )
    {
        $hashData = implode("|", [$this->id, $this->lifetime, $this->password, $this->secret]);

        $this->hash = hash_hmac('sha256', $hashData, $this->secret);
    }

    public function value(): string
    {
        return implode("|", [
            $this->id, $this->lifetime, $this->hash
        ]);
    }

    public static function fromValue(string $value): false|static
    {
        if (! static::valid($value)) {
            return false;
        }

        return new static(static::$segments[0], static::$segments[1], static::$segments[2], static::$segments[3], static::$segments[4]);
    }

    public static function valid(string $value): bool
    {
        static::$segments = explode("|", $value);

        if (count(static::$segments) === 5) {
            return true;
        }

        return false;
    }
}
