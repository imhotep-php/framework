<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Filesystem\Adapters\BaseAdapter;
use Imhotep\Filesystem\Drivers\FtpDriver;

class FtpDiskTest extends AbstractDiskTestCase
{
    protected function createDisk(): IFilesystem
    {
        $driver = new FtpDriver(new Repository([
            'throw' => true,
            'host' => 'ftp',
            'port' => 21,
            'username' => 'imhotep',
            'password' => 'password',
        ]));

        return new BaseAdapter($driver);
    }
}