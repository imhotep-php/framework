<?php

declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\SimpleS3\S3Client;

/**
 * @method static LocalDriver|S3Client disk(string $name = null)
 * @method static LocalDriver|S3Client cloud(string $name = null)
 *
 * @see \Imhotep\Filesystem\FilesystemManager
 */

class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filesystem';
    }
}