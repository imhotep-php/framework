<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Contracts\Cache\ICacheStore;
use PHPUnit\Framework\TestCase;

abstract class StoreTestCase extends TestCase
{
    protected ICacheStore $store;

    public function testBasicOperations()
    {
        $this->assertFalse($this->store->has('test'));
        $this->assertNull($this->store->get('test'));

        $this->assertTrue($this->store->set('test', 'value'));
        $this->assertTrue($this->store->has('test'));
        $this->assertEquals('value', $this->store->get('test'));
    }

    public function testGetMany()
    {
        $result = $this->store->many(['key1', 'key2']);
        $this->assertEquals(['key1' => null, 'key2' => null], $result);

        $this->store->set('key1', 'value1');
        $this->store->set('key2', 'value2');

        $result = $this->store->many(['key1', 'key2']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result);
    }

    public function testSetMany()
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertTrue($this->store->setMany($values));
        $this->assertEquals('value1', $this->store->get('key1'));
        $this->assertEquals('value2', $this->store->get('key2'));


        $this->assertTrue($this->store->set('foo', 'bar'));
        $this->assertTrue($this->store->setMany(['fizz' => 'buz', 'quz' => 'baz']));
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'empty' => null,
        ], $this->store->many(['foo', 'fizz', 'quz', 'empty']));
    }

    public function testAdd()
    {
        $this->assertTrue($this->store->add('test', 'value'));
        $this->assertTrue($this->store->has('test'));
        $this->assertEquals('value', $this->store->get('test'));

        $this->assertFalse($this->store->add('test', 'value'));
    }

    public function testTtlExpiration()
    {
        // Set with short TTL
        $this->store->set('key', 'value', 3);

        sleep(1);
        $this->assertEquals('value', $this->store->get('key'));

        // Should expire after 3 second
        sleep(3);
        $this->assertNull($this->store->get('key'));
        $this->assertFalse($this->store->has('key'));
    }

    public function testZeroTtl()
    {
        $this->store->set('key', 'value', 0);
        $this->assertNull($this->store->get('key'));
        $this->assertFalse($this->store->has('key'));
    }

    public function testNegativeTtl()
    {
        $this->store->set('key', 'value', -1);
        $this->assertNull($this->store->get('key'));
        $this->assertFalse($this->store->has('key'));
    }

    public function testNullTtl()
    {
        $this->store->set('key', 'value');

        // Should never expire
        sleep(1);
        $this->assertEquals('value', $this->store->get('key'));
        $this->assertTrue($this->store->has('key'));
    }

    public function testDelete()
    {
        $this->store->set('key', 'value');
        $this->assertTrue($this->store->delete('key'));
        $this->assertNull($this->store->get('key'));
    }

    public function testFlush()
    {
        $this->store->set('key1', 'value1');
        $this->store->set('key2', 'value2');

        $this->assertTrue($this->store->flush());
        $this->assertNull($this->store->get('key1'));
        $this->assertNull($this->store->get('key2'));
    }


    public function testIncrementFromNonExistent()
    {
        $result = $this->store->increment('counter', 5);
        $this->assertEquals(5, $result);
        $this->assertEquals(5, $this->store->get('counter'));
    }

    public function testDecrementFromNonExistent()
    {
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(0, $result); // Should not go below 0
        $this->assertEquals(0, $this->store->get('counter'));
    }

    public function testIncrementFromExistingValue()
    {
        $this->store->set('counter', 10);
        $result = $this->store->increment('counter', 3);
        $this->assertEquals(13, $result);
        $this->assertEquals(13, $this->store->get('counter'));

        $this->store->set('counter', '0');
        $result = $this->store->increment('counter', 3);
        $this->assertEquals(3, $result);
        $this->assertEquals(3, $this->store->get('counter'));
    }

    public function testDecrementFromExistingValue()
    {
        $this->store->set('counter', 10);
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->store->get('counter'));

        $this->store->set('counter', '10');
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->store->get('counter'));
    }

    public function testIncrementFromExistingInvalidValue()
    {
        $this->store->set('counter', 'ten');
        $result = $this->store->increment('counter', 3);
        $this->assertEquals(false, $result);
        $this->assertEquals('ten', $this->store->get('counter'));

        $this->store->set('counter', '10.5');
        $result = $this->store->increment('counter', 3);
        $this->assertEquals(false, $result);
        $this->assertEquals('10.5', $this->store->get('counter'));
    }

    public function testDecrementFromExistingInvalidValue()
    {
        $this->store->set('counter', 'ten');
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(false, $result);
        $this->assertEquals('ten', $this->store->get('counter'));

        $this->store->set('counter', '10.5');
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(false, $result);
        $this->assertEquals('10.5', $this->store->get('counter'));
    }

    public function testIncrementFromNonNumericValue()
    {
        $this->store->set('counter', '10');
        $result = $this->store->increment('counter', 3);
        $this->assertEquals(13, $result);
        $this->assertEquals(13, $this->store->get('counter'));
    }

    public function testDecrementFromNonNumericValue()
    {
        $this->store->set('counter', '10');
        $result = $this->store->decrement('counter', 3);
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->store->get('counter'));
    }

    public function testIncrementWithDefaultStep()
    {
        $result = $this->store->increment('counter');
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->store->get('counter'));

        $result = $this->store->increment('counter');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $this->store->get('counter'));
    }

    public function testDecrementWithDefaultStep()
    {
        $this->store->set('counter', 5);
        $result = $this->store->decrement('counter');
        $this->assertEquals(4, $result);
        $this->assertEquals(4, $this->store->get('counter'));

        $result = $this->store->decrement('counter');
        $this->assertEquals(3, $result);
        $this->assertEquals(3, $this->store->get('counter'));
    }

    public function testDecrementBelowZero()
    {
        $this->store->set('counter', 2);
        $result = $this->store->decrement('counter', 5);
        $this->assertEquals(0, $result); // Should clamp to 0
        $this->assertEquals(0, $this->store->get('counter'));
    }

    public function testIncrementDecrementSequence()
    {
        $this->store->increment('counter', 10);
        $this->assertEquals(10, $this->store->get('counter'));

        $this->store->decrement('counter', 3);
        $this->assertEquals(7, $this->store->get('counter'));

        $this->store->increment('counter', 5);
        $this->assertEquals(12, $this->store->get('counter'));

        $this->store->decrement('counter', 15);
        $this->assertEquals(0, $this->store->get('counter'));
    }

    public function testIncrementWithTtl()
    {
        $this->store->set('counter', 10, 2);
        $this->assertEquals(11, $this->store->increment('counter'));
        sleep(1);
        $this->assertEquals(12, $this->store->increment('counter'));
        sleep(2);
        $this->assertEquals(1, $this->store->increment('counter'));
        $this->assertEquals(2, $this->store->increment('counter'));
        $this->assertEquals(3, $this->store->increment('counter', ttl:0));
        $this->assertFalse($this->store->has('counter'));

        $this->assertEquals(1, $this->store->increment('counter', ttl:2));
        $this->assertEquals(2, $this->store->increment('counter'));
        sleep(3);
        $this->assertFalse($this->store->has('counter'));
    }

    public function testIncrementDecrement()
    {
        $this->assertEquals(1, $this->store->increment('counter'));
        $this->assertEquals(2, $this->store->increment('counter'));
        $this->assertEquals(3, $this->store->increment('counter'));
        $this->assertEquals(2, $this->store->decrement('counter'));
        $this->assertEquals(1, $this->store->decrement('counter'));
        $this->assertEquals(0, $this->store->decrement('counter'));
        $this->assertEquals(0, $this->store->decrement('counter'));
        $this->assertEquals(1, $this->store->increment('counter'));
    }
}