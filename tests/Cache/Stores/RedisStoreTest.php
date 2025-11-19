<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Tests\Redis\InteractsWithRedis;

class RedisStoreTest extends StoreTestCase
{
    use InteractsWithRedis;

    public function __construct()
    {
        parent::__construct();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        $this->store = new RedisStore($this->redis['phpredis'], 'default', 'default', '');
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        $this->tearDownRedis();

        parent::tearDown();
    }
}