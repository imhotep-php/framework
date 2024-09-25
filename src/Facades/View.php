<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\SimpleS3\S3Client;
use Imhotep\View\Engines\Engine;

/**
 * @method static View make(string $view, array $data = [])
 * @method static Engine getEngine(array $file)
 * @method static void share(string|array $key, mixed $value = null)
 * @method static array getShare(string $key, mixed $default = null)
 * @method static array getShared()
 * @method static bool exists(string $view)
 *
 * @see \Imhotep\View\Factory
 */

class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}