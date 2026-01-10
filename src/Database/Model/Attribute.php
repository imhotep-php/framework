<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

class Attribute
{
    public mixed $get;

    public mixed $set;

    public bool $withCaching = false;

    public bool $withObjectCaching = true;

    public function __construct(callable $get = null, callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    public static function make(callable $get = null, callable $set = null): static
    {
        return new static($get, $set);
    }

    public static function get(callable $get): static
    {
        return new static(get: $get);
    }

    public static function set(callable $set): static
    {
        return new static(set: $set);
    }

    public function shouldCache(): static
    {
        $this->withCaching = true;

        return $this;
    }

    public function withoutObjectCaching(): static
    {
        $this->withObjectCaching = false;

        return $this;
    }
}