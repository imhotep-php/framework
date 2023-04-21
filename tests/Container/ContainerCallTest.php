<?php

namespace Imhotep\Tests\Container;

use Closure;
use Error;
use Exception;
use Imhotep\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerCallTest extends TestCase
{
    public function test_call_with_at_sign_based_class_references_without_method_throws_exception()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined function ContainerTestCallStub()');

        $container = new Container;
        $container->call('ContainerTestCallStub');
    }

    public function test_call_with_at_sign_based_class_references()
    {
        $container = new Container;

        $result = $container->call(ContainerTestCallStub::class.'@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $result = $container->call(ContainerTestCallStub::class.'@inject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);

        $result = $container->call(ContainerTestCallStub::class.'@inject', ['default' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('foo', $result[1]);

        $result = $container->call(ContainerTestCallStub::class, ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function test_call_with_callable_array()
    {
        $container = new Container;
        $stub = new ContainerTestCallStub;
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function test_call_with_static_method_name_string()
    {
        $container = new Container;
        $result = $container->call('Imhotep\Tests\Container\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);
    }

    public function test_call_with_global_method_name()
    {
        $container = new Container;
        $result = $container->call('Imhotep\Tests\Container\containerTestInject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);
    }

    public function test_call_with_dependencies()
    {
        $container = new Container;

        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        });

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals([], $result[1]);

        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'value']);

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertSame('value', $result[1]);

        $stub = new ContainerCallConcreteStub;
        $result = $container->call(function (stdClass $foo, ContainerCallConcreteStub $bar) {
            return func_get_args();
        }, [ContainerCallConcreteStub::class => $stub]);

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertSame($stub, $result[1]);

        // Wrap a function...
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'value']);

        $this->assertInstanceOf(Closure::class, $result);
        $result = $result();

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertSame('value', $result[1]);
    }

    public function test_closure_call_with_injected_dependency()
    {
        $container = new Container;


        $result = $container->call(function (ContainerCallConcreteStub $stub) {
            return func_get_args();
        }, ['foo' => 'bar']);

        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('bar', $result[1]);


        $result = $container->call(function (ContainerCallConcreteStub $stub) {
            return func_get_args();
        }, ['foo' => 'bar', 'stub' => new ContainerCallConcreteStub]);

        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('bar', $result[1]);
        $this->assertArrayNotHasKey(2, $result);
    }

    public function test_call_with_variadic_dependency()
    {
        $stub1 = new ContainerCallConcreteStub;
        $stub2 = new ContainerCallConcreteStub;

        $container = new Container;
        $container->bind(ContainerCallConcreteStub::class, function () use ($stub1, $stub2) {
            return [
                $stub1,
                $stub2,
            ];
        });

        $result = $container->call(function (stdClass $foo, ContainerCallConcreteStub ...$bar) {
            return func_get_args();
        });

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[1]);
        $this->assertSame($stub1, $result[1]);
        $this->assertSame($stub2, $result[2]);
    }

    public function test_call_with_callable_object()
    {
        $container = new Container;
        $callable = new ContainerCallCallableStub;
        $result = $container->call($callable);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);
    }

    public function test_call_with_callable_class_string()
    {
        $container = new Container;
        $result = $container->call(ContainerCallCallableClassStringStub::class);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);
        $this->assertInstanceOf(ContainerTestCallStub::class, $result[2]);
    }

    public function test_call_without_required_params_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Imhotep\Tests\Container\ContainerTestCallStub');

        $container = new Container;
        $container->call(ContainerTestCallStub::class.'@unresolvable');
    }

    public function test_call_with_unnamed_parameters_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Imhotep\Tests\Container\ContainerTestCallStub');

        $container = new Container;
        $container->call([new ContainerTestCallStub, 'unresolvable'], ['foo', 'bar']);
    }

    public function test_call_without_required_params_on_closure_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Imhotep\Tests\Container\ContainerCallTest');

        $container = new Container;
        $container->call(function ($foo, $bar = 'default') {
            return $foo;
        });
    }

    public function testCallWithBoundMethod()
    {
        $container = new Container;
        $container->bindMethod(ContainerTestCallStub::class.'@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });

        $result = $container->call(ContainerTestCallStub::class.'@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod(ContainerTestCallStub::class.'@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['_stub' => 'foo', 'default' => 'bar']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('bar', $result[1]);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['_stub' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('value', $result[1]);
    }

    public function testBindMethodAcceptsAnArray()
    {
        $container = new Container;
        $container->bindMethod([ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call(ContainerTestCallStub::class.'@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod([ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);
    }
}

class ContainerTestCallStub
{
    public function work()
    {
        return func_get_args();
    }

    public function inject(ContainerCallConcreteStub $stub, $default = 'value')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
    }
}

class ContainerCallConcreteStub
{
    //
}

function containerTestInject(ContainerCallConcreteStub $stub, $default = 'value')
{
    return func_get_args();
}

class ContainerStaticMethodStub
{
    public static function inject(ContainerCallConcreteStub $stub, $default = 'value')
    {
        return func_get_args();
    }
}

class ContainerCallCallableStub
{
    public function __invoke(ContainerCallConcreteStub $stub, $default = 'value')
    {
        return func_get_args();
    }
}

class ContainerCallCallableClassStringStub
{
    public $stub;

    public $default;

    public function __construct(ContainerCallConcreteStub $stub, $default = 'value')
    {
        $this->stub = $stub;
        $this->default = $default;
    }

    public function __invoke(ContainerTestCallStub $dependency)
    {
        return [$this->stub, $this->default, $dependency];
    }
}