<?php

namespace Imhotep\Tests\ContainerNew;

use Imhotep\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerMagicAccessTest extends TestCase
{
    public function test_array_access()
    {
        $container = new Container();

        $container['foo'] = 'bar';

        $this->assertTrue(isset($container['foo']));
        $this->assertSame('bar', $container['foo']);

        $container['foo'] = 'baz';
        $this->assertSame('baz', $container['foo']);

        unset($container['foo']);
        $this->assertFalse(isset($container['foo']));
    }

    public function test_array_access_instance()
    {
        $container = new Container;
        $container->instance('object', new stdClass);
        $container->alias('object', 'alias');

        $this->assertTrue(isset($container['object']));
        $this->assertTrue(isset($container['alias']));
    }

    public function test_magic_access()
    {
        $container = new Container;
        $container->foo = 'bar';

        $this->assertTrue(isset($container['foo']));
        $this->assertSame('bar', $container->foo);

        $container->foo = 'baz';
        $this->assertSame('baz', $container->foo);
    }
}