<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

interface CacheFactoryInterface
{
    public function store(?string $name = null): CacheInterface;
}
