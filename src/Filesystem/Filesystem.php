<?php declare(strict_types=1);

namespace Imhotep\Filesystem;

use Imhotep\Filesystem\Drivers\LocalDriver;

class Filesystem extends LocalDriver
{
    public function __construct()
    {
        parent::__construct([]);
    }
}