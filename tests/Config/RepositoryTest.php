<?php

namespace Imhotep\Tests\Cache;

use Imhotep\Config\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    protected Repository $repo;

    protected array $config;

    protected function setUp(): void
    {
        $this->repo = new Repository($this->config = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'array' => [
                'aaa',
                'zzz',
            ],
            'x' => [
                'z' => 'zoo',
            ],
            'a.b' => 'c',
            'a' => [
                'b.c' => 'd',
            ],
        ]);

        parent::setUp();
    }

    public function testGetValueWhenKeyContainDot()
    {
        $this->assertSame(
            $this->repo->get('a.b'), 'c'
        );
        $this->assertNull(
            $this->repo->get('a.b.c')
        );
    }

    public function testGetBooleanValue()
    {
        $this->assertTrue(
            $this->repo->get('boolean')
        );
    }

    public function testGetNullValue()
    {
        $this->assertNull(
            $this->repo->get('null')
        );
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Repository::class, $this->repo);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repo->has('foo'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repo->has('not-exist'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repo->get('foo'));
    }

    public function testGetWithArrayOfKeys()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
        ], $this->repo->get([
            'foo',
            'bar',
            'none',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
        ], $this->repo->get([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
        ]));
    }

    public function testGetMany()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
        ], $this->repo->getMany([
            'foo',
            'bar',
            'none',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
        ], $this->repo->getMany([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
        ]));
    }

    public function testGetWithDefault()
    {
        $this->assertSame('default', $this->repo->get('not-exist', 'default'));
    }

    public function testSet()
    {
        $this->repo->set('key', 'value');
        $this->assertSame('value', $this->repo->get('key'));
    }

    public function testSetArray()
    {
        $this->repo->set([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3',
            'key4' => [
                'foo' => 'bar',
                'bar' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
        $this->assertSame('value1', $this->repo->get('key1'));
        $this->assertSame('value2', $this->repo->get('key2'));
        $this->assertNull($this->repo->get('key3'));
        $this->assertSame('bar', $this->repo->get('key4.foo'));
        $this->assertSame('bar', $this->repo->get('key4.bar.foo'));
        $this->assertNull($this->repo->get('key5'));
    }

    public function testPrepend()
    {
        $this->assertSame('aaa', $this->repo->get('array.0'));
        $this->assertSame('zzz', $this->repo->get('array.1'));
        $this->repo->prepend('array', 'xxx');
        $this->assertSame('xxx', $this->repo->get('array.0'));
        $this->assertSame('aaa', $this->repo->get('array.1'));
        $this->assertSame('zzz', $this->repo->get('array.2'));
    }

    public function testPush()
    {
        $this->assertSame('aaa', $this->repo->get('array.0'));
        $this->assertSame('zzz', $this->repo->get('array.1'));
        $this->repo->push('array', 'xxx');
        $this->assertSame('aaa', $this->repo->get('array.0'));
        $this->assertSame('zzz', $this->repo->get('array.1'));
        $this->assertSame('xxx', $this->repo->get('array.2'));
    }

    public function testPrependWithNewKey()
    {
        $this->repo->prepend('new_key', 'xxx');
        $this->assertSame(['xxx'], $this->repo->get('new_key'));
    }

    public function testPushWithNewKey()
    {
        $this->repo->push('new_key', 'xxx');
        $this->assertSame(['xxx'], $this->repo->get('new_key'));
    }

    public function testAll()
    {
        $this->assertSame($this->config, $this->repo->all());
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->repo['foo']));
        $this->assertFalse(isset($this->repo['not-exist']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->repo['not-exist']);
        $this->assertSame('bar', $this->repo['foo']);
        $this->assertSame([
            'x' => 'xxx',
            'y' => 'yyy',
        ], $this->repo['associate']);
    }

    public function testOffsetSet()
    {
        $this->assertNull($this->repo['key']);

        $this->repo['key'] = 'value';

        $this->assertSame('value', $this->repo['key']);
    }

    public function testOffsetUnset()
    {
        $this->assertArrayHasKey('associate', $this->repo->all());
        $this->assertSame($this->config['associate'], $this->repo->get('associate'));

        unset($this->repo['associate']);

        $this->assertArrayHasKey('associate', $this->repo->all());
        $this->assertNull($this->repo->get('associate'));
    }
}