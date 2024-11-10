<?php

namespace Imhotep\Tests\Redis;

use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function test_common()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'imhotep 1');
            $redis->set('two', 'imhotep 2');
            $redis->set('three', 'imhotep 3');

            $this->assertEquals('imhotep 1', $redis->get('one'));
            $this->assertEquals('imhotep 2', $redis->get('two'));
            $this->assertEquals('imhotep 3', $redis->get('three'));

            $redis->del('one');
            $this->assertNull($redis->get('one'));
            $this->assertNotNull($redis->get('two'));
            $this->assertNotNull($redis->get('three'));

            $redis->del('two', 'three');
            $this->assertNull($redis->get('two'));
            $this->assertNull($redis->get('three'));

            // getSet()
            $redis->set('one', 'imhotep');
            $this->assertSame('imhotep', $redis->getSet('one', 'framework'));
            $this->assertSame('framework', $redis->getSet('one', 'php'));

            $redis->flushall();
        }
    }

    public function test_getSet()
    {
        foreach ($this->connections() as $redis) {

        }
    }

    public function test_multi_common()
    {
        $valueSet = ['one' => 'step', 'two' => 'floor', 'three' => 'roof'];

        foreach ($this->connections() as $redis) {
            $redis->mset($valueSet);

            $this->assertEquals(
                $valueSet,
                $redis->mget(array_keys($valueSet))
            );

            $redis->flushall();
        }
    }

    protected function connections(): array
    {
        $connections = [
            'predis' => $this->redis['predis']->connection(),
        ];

        return $connections;
    }
}