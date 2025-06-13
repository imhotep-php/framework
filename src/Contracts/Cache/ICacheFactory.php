<?php declare(strict_types=1);

namespace Imhotep\Contracts\Cache;

interface ICacheFactory
{
    public function store(?string $name = null): ICache;
}
