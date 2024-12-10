<?php

namespace Imhotep\Tests\Cache;

use Imhotep\Cache\Stores\ArrayStore;
use PHPUnit\Framework\TestCase;

class CacheArrayStoreTest extends TestCase
{
    protected ArrayStore $store;

    protected int $ttl = 5;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->store = new ArrayStore();
    }

    public function testItemCanBeSetAndGet()
    {
        $result = $this->store->set('foo', 'bar', 10);
        $this->assertTrue($result);
        $this->assertSame('bar', $this->store->get('foo'));
    }

    public function testManyItemsCanBeSetAndGet()
    {
        $result = $this->store->set('foo', 'bar', 10);
        $resultMany = $this->store->setMany([
            'fizz' => 'buz',
            'quz' => 'baz',
        ], 10);
        $this->assertTrue($result);
        $this->assertTrue($resultMany);
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $this->store->many(['foo', 'fizz', 'quz', 'norf']));
    }

    public function testIncrement()
    {
        $this->store->set('foo', 1, 10);
        $result = $this->store->increment('foo');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $this->store->get('foo'));

        $result = $this->store->increment('foo', 2);
        $this->assertEquals(4, $result);
        $this->assertEquals(4, $this->store->get('foo'));
    }

    public function testValueStringToIncrementOrDecrement()
    {
        $this->store->set('foo', '1', 10);
        $result = $this->store->increment('foo');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $this->store->get('foo'));

        $this->store->set('bar', '1', 10);
        $result = $this->store->decrement('bar');
        $this->assertEquals(0, $result);
        $this->assertEquals(0, $this->store->get('bar'));
    }

    public function testIncrementNonNumericValues()
    {
        $this->store->set('foo', 'I am string', 10);
        $result = $this->store->increment('foo');
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->store->get('foo'));
    }

    public function testNonExistingKeysCanBeIncremented()
    {
        $result = $this->store->increment('foo');
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->store->get('foo'));
    }

    public function testValuesCanBeDecremented()
    {
        $this->store->set('foo', 1, 10);
        $result = $this->store->decrement('foo');
        $this->assertEquals(0, $result);
        $this->assertEquals(0, $this->store->get('foo'));

        $result = $this->store->decrement('foo', 2);
        $this->assertEquals(-2, $result);
        $this->assertEquals(-2, $this->store->get('foo'));
    }

    public function testRemoved()
    {
        $this->store->set('foo', 'bar', 10);
        $this->assertTrue($this->store->delete('foo'));
        $this->assertNull($this->store->get('foo'));
        $this->assertTrue($this->store->delete('foo'));
    }

    public function testItemsCanBeFlushed()
    {
        $this->store->set('foo', 'bar', 10);
        $this->store->set('baz', 'boom', 10);
        $result = $this->store->flush();
        $this->assertTrue($result);
        $this->assertNull($this->store->get('foo'));
        $this->assertNull($this->store->get('baz'));
    }


    public function testItemExpire()
    {
        $this->store->set('foo', 'bar', 1);
        sleep(2);
        $result = $this->store->get('foo');

        $this->assertNull($result);
    }

    public function testExpiredKeysAreIncrementedLikeNonExistingKeys()
    {
        $this->store->set('foo', 999, 1);
        sleep(2);
        $result = $this->store->increment('foo');
        $this->assertEquals(1, $result);
    }
}