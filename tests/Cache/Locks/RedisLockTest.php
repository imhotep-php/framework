<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\RedisStore;
use Imhotep\Tests\Redis\InteractsWithRedis;

class RedisLockTest extends LockTestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        $this->store = new RedisStore($this->redis['phpredis'], 'default', 'default', '');

        $this->lock = $this->store->lock('test-lock', 5, 'test-owner');
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        $this->tearDownRedis();

        parent::tearDown();
    }
}