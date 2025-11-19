<?php declare(strict_types=1);

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Stores\ArrayStore;

class ArrayLockTest extends LockTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new ArrayStore();
        $this->lock = $this->store->lock('test-lock', 5, 'test-owner');
    }
}