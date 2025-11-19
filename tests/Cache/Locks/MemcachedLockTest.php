<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\MemcachedStore;

class MemcachedLockTest extends LockTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new MemcachedStore(MemcachedStore::memcached([
            ['host' => 'memcached', 'port' => 11211, 'weight' => 100],
        ]));

        $this->lock = $this->store->lock('test-lock', 5, 'test-owner');
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        parent::tearDown();
    }
}