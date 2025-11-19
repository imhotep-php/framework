<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\FileStore;
use Imhotep\Filesystem\Filesystem;

class FileLockTest extends LockTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        @rmdir(__DIR__ . '/locks');
        $this->store = new FileStore(__DIR__ . '/locks');
        $this->lock = $this->store->lock('test-lock', 5, 'test-owner');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory(__DIR__ . '/locks');

        parent::tearDown();
    }
}