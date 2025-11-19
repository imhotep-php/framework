<?php

namespace Imhotep\Tests\Cache\Locks;

use Imhotep\Cache\Locks\Lock;
use Imhotep\Contracts\Cache\ICacheStore;
use Imhotep\Contracts\Cache\LockTimeoutException;
use PHPUnit\Framework\TestCase;

abstract class LockTestCase extends TestCase
{
    protected ICacheStore $store;
    protected Lock $lock;

    public function testAcquireAndRelease()
    {
        $this->assertTrue($this->lock->acquire());
        $this->assertTrue($this->lock->isOwned());
        $this->assertTrue($this->lock->release());
    }

    public function testCannotAcquireLockedResource()
    {
        $this->assertTrue($this->lock->acquire());

        // Another lock with same name should not be acquirable
        $anotherLock = $this->store->lock('test-lock', 5, 'another-owner');
        $this->assertFalse($anotherLock->acquire());
        $this->assertFalse($anotherLock->isOwned());
    }

    public function testCannotReleaseNotOwnedLock()
    {
        $this->assertTrue($this->lock->acquire());

        $anotherLock = $this->store->lock('test-lock', 5, 'another-owner');
        $this->assertFalse($anotherLock->release()); // Cannot release lock owned by someone else
    }

    public function testForceRelease()
    {
        $this->assertTrue($this->lock->acquire());

        $anotherLock = $this->store->lock('test-lock', 5, 'another-owner');

        $this->assertFalse($anotherLock->acquire());

        $anotherLock->forceRelease();

        // After force release, lock should be available
        $this->assertTrue($anotherLock->acquire());
    }

    public function testLockExpiration()
    {
        $lock = $this->store->lock('test-lock', 1); // 1 second TTL
        $this->assertTrue($lock->acquire());

        // Lock should expire after 1 second
        sleep(2);

        $anotherLock = $this->store->lock('test-lock', 5, 'another-owner');
        $this->assertTrue($anotherLock->acquire()); // Should be able to acquire expired lock
    }

    public function testGetWithCallback()
    {
        $result = $this->lock->get(fn() => 'callback-result');
        $this->assertEquals('callback-result', $result);
        $this->assertFalse($this->lock->isOwned()); // Lock should be released after callback
    }

    public function testBlockWithTimeout()
    {
        // Acquire lock first
        $this->assertTrue($this->lock->acquire());

        $anotherLock = $this->store->lock('test-lock', 1, 'another-owner');

        // This should timeout quickly since lock is held
        $start = microtime(true);
        try {
            $anotherLock->block(1); // 1 second timeout
            $this->fail('Expected LockTimeoutException');
        } catch (LockTimeoutException $e) {
            $duration = microtime(true) - $start;
            $this->assertLessThan(1.5, $duration); // Should timeout within reasonable time
        }
    }

    public function testBlockWithCallback()
    {
        $result = $this->lock->block(1, fn() => 'block-result');
        $this->assertEquals('block-result', $result);
    }

    public function testInfiniteLock()
    {
        $lock = $this->store->lock('infinite-lock', 0); // 0 means infinite
        $this->assertTrue($lock->acquire());

        // Lock should never expire
        sleep(1);

        $anotherLock = $this->store->lock('infinite-lock', 5, 'another-owner');
        $this->assertFalse($anotherLock->acquire());
    }

    public function testRestoreLock()
    {
        $this->assertTrue($this->lock->acquire());

        // Simulate restoring the same lock
        $restoredLock = $this->store->restoreLock('test-lock', 'test-owner');
        $this->assertTrue($restoredLock->isOwned());
        $this->assertTrue($restoredLock->release());
    }
}