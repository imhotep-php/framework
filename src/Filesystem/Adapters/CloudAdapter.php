<?php

declare(strict_types=1);

namespace Imhotep\Filesystem\Adapters;

use Imhotep\Contracts\Filesystem\Cloud;
use Imhotep\Contracts\Filesystem\Driver;

class CloudAdapter implements Cloud
{
    protected Driver $driver;

    protected array $config;

    public function __construct(Driver $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }
}