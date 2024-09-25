<?php

namespace Imhotep\Tests\ContainerNew;

use Imhotep\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerExtendTest extends TestCase
{

    public function testExtendedBindings()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });

        $this->assertSame('foobar', $container->make('foo'));

        $container = new Container;

        $container->singleton('foo', function () {
            return (object) ['name' => 'imhotep'];
        });
        $container->extend('foo', function ($old, $container) {
            $old->age = 26;

            return $old;
        });

        $result = $container->make('foo');

        $this->assertSame('imhotep', $result->name);
        $this->assertEquals(26, $result->age);
        $this->assertSame($result, $container->make('foo'));
    }

    public function testExtendInstancesArePreserved()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        $obj = new stdClass;
        $obj->foo = 'foo';
        $container->instance('foo', $obj);
        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });
        $container->extend('foo', function ($obj, $container) {
            $obj->baz = 'foo';

            return $obj;
        });

        $this->assertSame('foo', $container->make('foo')->foo);
        $this->assertSame('baz', $container->make('foo')->bar);
        $this->assertSame('foo', $container->make('foo')->baz);
    }

    public function testExtendIsLazyInitialized()
    {
        LazyExtendStub::$initialized = false;

        $container = new Container;
        $container->bind(LazyExtendStub::class);
        $container->extend(LazyExtendStub::class, function ($obj, $container) {
            $obj->init();

            return $obj;
        });
        $this->assertFalse(LazyExtendStub::$initialized);
        $container->make(LazyExtendStub::class);
        $this->assertTrue(LazyExtendStub::$initialized);
    }

    public function testExtendCanBeCalledBeforeBind()
    {
        $container = new Container;
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container['foo'] = 'foo';

        $this->assertSame('foobar', $container->make('foo'));
    }

    public function testExtendInstanceRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container();
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });

        $container->instance('foo', new stdClass);

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testExtendBindRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });
        $container->bind('foo', function () {
            return new stdClass;
        });

        $this->assertFalse($_SERVER['_test_rebind']);

        $container->make('foo');

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testExtensionWorksOnAliasedBindings()
    {
        $container = new Container;
        $container->singleton('something', function () {
            return 'some value';
        });
        $container->alias('something', 'something-alias');
        $container->extend('something-alias', function ($value) {
            return $value.' extended';
        });

        $this->assertSame('some value extended', $container->make('something'));
    }

    public function testMultipleExtends()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container->extend('foo', function ($old, $container) {
            return $old.'baz';
        });

        $this->assertSame('foobarbaz', $container->make('foo'));
    }

    public function testUnsetExtend()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });

        unset($container['foo']);
        $container->forgetExtends('foo');

        $container->bind('foo', function () {
            return 'foo';
        });

        $this->assertSame('foo', $container->make('foo'));
    }
}

class LazyExtendStub
{
    public static $initialized = false;

    public function init()
    {
        static::$initialized = true;
    }
}