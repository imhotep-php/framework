<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\MemcachedStore;

class MemcachedStoreTest extends StoreTestCase
{
    public function __construct()
    {
        parent::__construct();

        $this->store = new MemcachedStore(MemcachedStore::memcached([
            ['host' => 'memcached', 'port' => 11211, 'weight' => 100],
        ]));
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        parent::tearDown();
    }
}