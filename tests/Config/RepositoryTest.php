<?php

namespace Imhotep\Tests\Config;

use Imhotep\Config\Repository;
use InvalidArgumentException;
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
            'number' => 123,
            'position' => 30.12,
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

    public function testAll()
    {
        $this->assertSame($this->config, $this->repo->all());
    }

    public function testRequired()
    {
        $this->assertSame('bar', $this->repo->required('foo'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required configuration [not-exist] is not set.');
        $this->repo->required('not-exist');
    }

    public function testRequiredWithCustomMessage()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom error message for key [not-exist].');
        $this->repo->required('not-exist', '', 'Custom error message for key [:key].');
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repo->get('foo'));
    }

    public function testGetOrFail()
    {
        $this->assertSame('bar', $this->repo->getOrFail('foo'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required configuration [not-exist] is not set.');
        $this->repo->getOrFail('not-exist');
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

    public function testGetStringOrNull()
    {
        $this->assertSame('bar', $this->repo->string('foo'));
        $this->assertNull($this->repo->string('null'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [number] must be a string, integer given.');
        $this->repo->string('number');
    }

    public function testGetStringOrFail()
    {
        $this->assertSame('bar', $this->repo->stringOrFail('foo'));

        $this->expectException(InvalidArgumentException::class);
        $this->repo->stringOrFail('null');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->stringOrFail('not-exist');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->stringOrFail('number');
    }

    public function testGetIntOrNull()
    {
        $this->assertSame(123, $this->repo->int('number'));
        $this->assertNull($this->repo->int('null'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [foo] must be an integer, string given.');
        $this->repo->int('foo');
    }

    public function testGetIntOrFail()
    {
        $this->assertSame(123, $this->repo->intOrFail('number'));

        $this->expectException(InvalidArgumentException::class);
        $this->repo->intOrFail('null');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->intOrFail('not-exist');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->intOrFail('string');
    }

    public function testGetFloatOrNull()
    {
        $this->assertSame(30.12, $this->repo->float('position'));
        $this->assertNull($this->repo->float('null'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [foo] must be a float, string given.');
        $this->repo->float('foo');
    }

    public function testGetFloatOrFail()
    {
        $this->assertSame(30.12, $this->repo->floatOrFail('position'));

        $this->expectException(InvalidArgumentException::class);
        $this->repo->floatOrFail('null');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->floatOrFail('not-exist');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->floatOrFail('int');
    }

    public function testGetBoolOrNull()
    {
        $this->assertSame(true, $this->repo->bool('boolean'));
        $this->assertNull($this->repo->bool('null'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [number] must be a bool, integer given.');
        $this->repo->bool('number');
    }

    public function testGetBoolOrFail()
    {
        $this->assertTrue($this->repo->boolOrFail('boolean'));

        $this->expectException(InvalidArgumentException::class);
        $this->repo->boolOrFail('null');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->boolOrFail('not-exist');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->boolOrFail('int');
    }

    public function testGetArrayOrNull()
    {
        $this->assertSame(['aaa', 'zzz'], $this->repo->array('array'));
        $this->assertNull($this->repo->array('null'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [number] must be an array, integer given.');
        $this->repo->array('number');
    }

    public function testGetArrayOrFail()
    {
        $this->assertSame(['aaa', 'zzz'], $this->repo->arrayOrFail('array'));

        $this->expectException(InvalidArgumentException::class);
        $this->repo->arrayOrFail('null');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->arrayOrFail('not-exist');

        $this->expectException(InvalidArgumentException::class);
        $this->repo->arrayOrFail('int');
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

    public function testSubset()
    {
        $subset = $this->repo->subset('associate');
        $this->assertInstanceOf(Repository::class, $subset);
        $this->assertSame('xxx', $subset->get('x'));
        $this->assertSame('yyy', $subset->get('y'));
    }

    public function testSubsetWithDefault()
    {
        $subset = $this->repo->subset('not-exist', ['default' => 'value']);
        $this->assertInstanceOf(Repository::class, $subset);
        $this->assertSame('value', $subset->get('default'));
    }

    public function testSubsetOrFail()
    {
        $subset = $this->repo->subsetOrFail('associate');
        $this->assertInstanceOf(Repository::class, $subset);
        $this->assertSame('xxx', $subset->get('x'));

        // With default exception message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required configuration [not-exist] is not set.');
        $this->repo->subsetOrFail('not-exist');

        // With custom exception message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom subset error for key not-exist');
        $this->repo->subsetOrFail('not-exist', 'Custom subset error for key :key');
    }

    public function testRequiredExceptionShowsFullPath()
    {
        $subset = $this->repo->subset('associate');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required configuration [associate.z] is not set.');
        $subset->required('z');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration value for key [associate.x] must be an integer, string given.');
        $subset->required('x', 'int');

        // Subset nested path
        $nestedSubset = $subset->subset('x');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required configuration [associate.x.nonexistent] is not set.');
        $nestedSubset->required('nonexistent');
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

        $this->assertArrayNotHasKey('associate', $this->repo->all());
        $this->assertNull($this->repo->get('associate'));
    }
}