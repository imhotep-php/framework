<?php

namespace Imhotep\Tests\Cache;

use DateInterval;
use Imhotep\Cache\Repository;
use Imhotep\Cache\Stores\ArrayStore;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    protected Repository $cache;
    protected ArrayStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new ArrayStore();
        $this->cache = new Repository($this->store, true);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        parent::tearDown();
    }

    public function testMissingMethod()
    {
        $this->assertTrue($this->cache->missing('nonexistent'));

        $this->cache->set('exists', 'value');
        $this->assertFalse($this->cache->missing('exists'));
    }

    public function testGetWithDefault()
    {
        $result = $this->cache->get('nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetWithClosureDefault()
    {
        $result = $this->cache->get('nonexistent', fn() => 'closure_default');
        $this->assertEquals('closure_default', $result);
    }

    public function testManyWithDefault()
    {
        $result = $this->cache->many(['key1', 'key2']);
        $this->assertEquals(['key1' => null, 'key2' => null], $result);

        $result = $this->cache->many(['key1', 'key2'], 'default_value');
        $this->assertEquals(['key1' => 'default_value', 'key2' => 'default_value'], $result);

        $result = $this->cache->many(['key1', 'key2'], fn() => 'closure_default');
        $this->assertEquals(['key1' => 'closure_default', 'key2' => 'closure_default'], $result);
    }

    public function testManyWithIterable()
    {
        $generator = function () {
            yield 'key1';
            yield 'key2';
        };

        $result = $this->cache->many($generator(), 'default');
        $this->assertEquals(['key1' => 'default', 'key2' => 'default'], $result);
    }

    public function testPullWithDefault()
    {
        $this->assertEquals('default', $this->cache->pull('nonexistent', 'default'));

        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->pull('key'));
        $this->assertNull($this->cache->get('key'));
    }

    public function testRememberWithClosure()
    {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'cached_value_' . $callCount;
        };

        // First call should execute callback
        $result1 = $this->cache->remember('key', $callback, 1);
        $this->assertEquals('cached_value_1', $result1);
        $this->assertEquals(1, $callCount);

        // Second call should use cached value
        $result2 = $this->cache->remember('key', $callback);
        $this->assertEquals('cached_value_1', $result2);
        $this->assertEquals(1, $callCount); // Callback should not be called again

        sleep(2);

        $result3 = $this->cache->remember('key', $callback);
        $this->assertEquals('cached_value_2', $result3);
        $this->assertEquals(2, $callCount);
    }

    public function testDeleteMultipleWithIterable()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $keys = new \ArrayIterator(['key1', 'key2']);
        $this->assertTrue($this->cache->deleteMany($keys));

        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function testKeyValidationEnabled()
    {
        $cache = new Repository($this->store, true);

        // Valid key should work
        $cache->set('valid_key', 'value');
        $this->assertEquals('value', $cache->get('valid_key'));

        // Invalid keys should throw exceptions
        $this->expectException(InvalidArgumentException::class);
        $cache->set('', 'value');

        $this->expectException(InvalidArgumentException::class);
        $cache->set(str_repeat('a', 251), 'value');

        $this->expectException(InvalidArgumentException::class);
        $cache->set("key\nwithnewline", 'value');
    }

    public function testKeyValidationDisabled()
    {
        $cache = new Repository($this->store, false);

        // Should not validate keys when disabled
        $cache->set('', 'empty_key_value');
        $this->assertEquals('empty_key_value', $cache->get(''));

        $longKey = str_repeat('a', 300);
        $cache->set($longKey, 'long_key_value');
        $this->assertEquals('long_key_value', $cache->get($longKey));
    }

    public function testArrayAccessImplementation()
    {
        // Set via array access
        $this->cache['array_key'] = 'array_value';
        $this->assertEquals('array_value', $this->cache['array_key']);
        $this->assertTrue(isset($this->cache['array_key']));

        // Unset via array access
        unset($this->cache['array_key']);
        $this->assertFalse(isset($this->cache['array_key']));
        $this->assertNull($this->cache['array_key']);
    }

    public function testMacroFunctionality()
    {
        // Define a macro
        Repository::macro('customMethod', function ($arg1, $arg2) {
            return "{$arg1}-{$arg2}-macro";
        });

        // Call the macro
        $result = $this->cache->customMethod('hello', 'world');
        $this->assertEquals('hello-world-macro', $result);
    }


    public function testParseTtlWithNull()
    {
        $result = $this->callParseTtl(null);
        $this->assertNull($result);
    }

    public function testParseTtlWithInteger()
    {
        $result = $this->callParseTtl(60);
        $this->assertEquals(60, $result);

        $result = $this->callParseTtl(0);
        $this->assertEquals(0, $result);

        $result = $this->callParseTtl(-60);
        $this->assertEquals(-60, $result);
    }

    public function testParseTtlWithDateInterval()
    {
        // Test 1 minute interval
        $interval = new DateInterval('PT1M');
        $result = $this->callParseTtl($interval);
        $this->assertEquals(60, $result, '1 minute interval should convert to 60 seconds');

        // Test 2 hours 30 minutes interval
        $interval = new DateInterval('PT2H30M');
        $result = $this->callParseTtl($interval);
        $this->assertEquals(9000, $result, '2 hours 30 minutes should convert to 9000 seconds');

        // Test 1 day interval
        $interval = new DateInterval('P1D');
        $result = $this->callParseTtl($interval);
        $this->assertEquals(86400, $result, '1 day interval should convert to 86400 seconds');

        // Test complex interval: 1 day, 2 hours, 3 minutes, 4 seconds
        $interval = new DateInterval('P1DT2H3M4S');
        $result = $this->callParseTtl($interval);
        $this->assertEquals(93784, $result, 'Complex interval should convert correctly');

        // Test edge case: zero interval
        $interval = new DateInterval('PT0S');
        $result = $this->callParseTtl($interval);
        $this->assertEquals(0, $result, 'Zero interval should convert to 0');
    }

    private function callParseTtl($ttl)
    {
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('parseTtl');
        $method->setAccessible(true);

        return $method->invoke($this->cache, $ttl);
    }


    public function testValidateKeysCalledOnce()
    {
        $cache = new MockRepository($this->store, true);

        // Test has method
        $cache->validateCallCount = 0;
        $cache->has('test_key');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for has()');


        // Test pull method
        $cache->validateCallCount = 0;
        $cache->pull('test_key', 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for pull()');

        // Test get method
        $cache->validateCallCount = 0;
        $cache->get('test_key', 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for get()');

        // Test many method
        $cache->validateCallCount = 0;
        $cache->many(['test_key1', 'test_key2'], 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for many()');

        // Test add method
        $cache->validateCallCount = 0;
        $cache->add('test_key', 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for add()');

        // Test setMany method
        $cache->validateCallCount = 0;
        $cache->setMany(['test_key1' => 'value1', 'test_key2' => 'value2']);
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for setMany()');

        // Test forever method
        $cache->validateCallCount = 0;
        $cache->forever('test_key', 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for forever()');

        // Test remember method
        $cache->validateCallCount = 0;
        $cache->remember('test_key', fn() => 'default');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for remember()');

        // Test increment method
        $cache->validateCallCount = 0;
        $cache->increment('test_key');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for increment()');

        // Test decrement method
        $cache->validateCallCount = 0;
        $cache->decrement('test_key');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for decrement()');

        // Test delete method
        $cache->validateCallCount = 0;
        $cache->delete('test_key');
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for delete()');

        // Test deleteMany method
        $cache->validateCallCount = 0;
        $cache->deleteMany(['test_key1','test_key2']);
        $this->assertEquals(1, $cache->validateCallCount, 'validateKeys should be called once for deleteMany()');
    }
}

class MockRepository extends Repository
{
    public int $validateCallCount = 0;

    protected function validateKeys(array|string $keys): void
    {
        $this->validateCallCount++;

        parent::validateKeys($keys);
    }
}