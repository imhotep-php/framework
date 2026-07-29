<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Filesystem\Adapters\BaseAdapter;
use Imhotep\Filesystem\Drivers\CloudDriver;
use Imhotep\SimpleS3\S3Client;

class CloudDiskTest extends AbstractDiskTestCase
{
    protected function createDisk(): IFilesystem
    {
        $s3client = new S3Client('imhotep-user', 'imhotep-pass', 'http://minio:9000');

        $driver = new CloudDriver(
            new Repository(['bucket' => 'imhotep-bucket']),
            $s3client
        );

        return new BaseAdapter($driver, '/tests');
    }

}