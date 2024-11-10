<?php

namespace Imhotep\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string|null  $name
     * @return \Imhotep\Redis\Connections\Connection
     */
    public function connection(string $name = null);
}
