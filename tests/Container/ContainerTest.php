<?php

namespace Imhotep\Tests\ContainerNew;

use Imhotep\Container\NotFoundException;
use Imhotep\Container\Container;
use Imhotep\Container\ContainerException;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    public function test_container_instantiation()
    {
        $container = Container::setInstance(new Container());

        $this->assertSame($container, Container::getInstance());

        Container::setInstance(null);

        $container2 = Container::getInstance();

        $this->assertInstanceOf(Container::class, $container2);
        $this->assertNotSame($container, $container2);
    }

    public function test_bind_closure()
    {
        $container = new Container();
        $container->bind('foo', fn() => 'bar');
        $container->bindIf('foo', fn() => 'xyz');
        $this->assertSame('bar', $container->get('foo'));
        $this->assertSame('bar', $container->get('foo'));

        // Bind if not exist in container
        $container = new Container();
        $container->bindIf('foo', fn() => 'xyz');
        $this->assertSame('xyz', $container->get('foo'));

        // Bind overridden
        $container = new Container();
        $container->bind('foo', fn() => 'bar');
        $container->bind('foo', fn() => 'xyz');
        $this->assertSame('xyz', $container->get('foo'));
    }

    public function test_bind_class()
    {
        $container = new Container();
        $instance1 = $container->get(ContainerConcreteStub::class);
        $instance2 = $container->get(ContainerConcreteStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance1);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance2);
        $this->assertNotSame($instance1, $instance2);

        $container->bind(IContainerStub::class, ImplementationContainerStub::class);
        $instance1 = $container->get(IContainerStub::class);
        $instance2 = $container->get(IContainerStub::class);
        $this->assertInstanceOf(ImplementationContainerStub::class, $instance1);
        $this->assertInstanceOf(ImplementationContainerStub::class, $instance2);
        $this->assertNotSame($instance1, $instance2);

        $container = new Container();
        $container->bind(IContainerStub::class, ImplementationContainerStub::class);
        $class = $container->make(NestedDependentStub::class);
        $this->assertInstanceOf(DependentStub::class, $class->inner);
        $this->assertInstanceOf(ImplementationContainerStub::class, $class->inner->impl);
    }

    public function test_singleton_closure()
    {
        $container = new Container();

        $container->singleton('class', fn() => new stdClass());
        $instance1 = $container->get('class');

        $container->singletonIf('class', fn() => new ContainerConcreteStub());
        $instance2 = $container->get('class');

        $this->assertInstanceOf(stdClass::class, $instance1);
        $this->assertInstanceOf(stdClass::class, $instance2);
        $this->assertSame($instance1, $instance2);

        $container->singletonIf('otherClass', fn() => new ContainerConcreteStub());
        $this->assertSame(
            $container->get('otherClass'),
            $container->get('otherClass')
        );
    }

    public function test_singleton()
    {
        $container = new Container();

        $container->singleton(ContainerConcreteStub::class);
        $instance1 = $container->get(ContainerConcreteStub::class);
        $instance2 = $container->get(ContainerConcreteStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance1);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance2);
        $this->assertSame($instance1, $instance2);

        $container->singleton(IContainerStub::class, ImplementationContainerStub::class);
        $instance1 = $container->get(IContainerStub::class);
        $instance2 = $container->get(IContainerStub::class);
        $this->assertInstanceOf(IContainerStub::class, $instance1);
        $this->assertInstanceOf(IContainerStub::class, $instance2);
        $this->assertSame($instance1, $instance2);
    }

    public function test_instance()
    {
        $container = new Container();
        $container->instance('foo', $instance = new stdClass());
        $this->assertSame($instance, $container->get('foo'));
    }

    public function test_instance_forget()
    {
        $container = new Container();

        $container->instance(ContainerConcreteStub::class, new ContainerConcreteStub());
        $this->assertTrue($container->isSingleton(ContainerConcreteStub::class));

        $container->forgetInstance(ContainerConcreteStub::class);
        $this->assertFalse($container->isSingleton(ContainerConcreteStub::class));
    }

    public function test_instance_forget_all()
    {
        $container = new Container();

        $container->instance('class1', new ContainerConcreteStub());
        $container->instance('class2', new ContainerConcreteStub());
        $container->instance('class3', new ContainerConcreteStub());

        $this->assertTrue($container->isSingleton('class1'));
        $this->assertTrue($container->isSingleton('class2'));
        $this->assertTrue($container->isSingleton('class3'));

        $container->forgetInstances();
        $this->assertFalse($container->isSingleton('class1'));
        $this->assertFalse($container->isSingleton('class2'));
        $this->assertFalse($container->isSingleton('class3'));
    }

    public function test_alias()
    {
        $container = new Container();

        $container->alias('ConcreteStub', 'alias');
        $this->assertSame('ConcreteStub', $container->getAlias('alias'));
        $this->assertTrue($container->hasAlias('alias'));

        $container->alias('alias', 'foo');
        $container->alias('foo', 'bar');
        $container->alias('bar', 'baz');
        $this->assertSame('ConcreteStub', $container->getAlias('baz'));
    }

    public function test_alias_closure()
    {
        $container = new Container();

        $container->set('foo', fn() => 'bar');
        $container->alias('foo', 'my_alias_1');
        $container->alias('my_alias_1', 'my_alias_2');

        $this->assertSame('bar', $container->get('foo'));
        $this->assertSame('bar', $container->get('my_alias_1'));
        $this->assertSame('bar', $container->get('my_alias_2'));
    }

    public function test_alias_class()
    {
        $container = new Container();

        $container->set(IContainerStub::class, ImplementationContainerStub::class);
        $container->set(ImplementationContainerStub::class, ImplementationContainerStubTwo::class);
        $container->alias(IContainerStub::class, 'stub');

        $this->assertInstanceOf(ImplementationContainerStubTwo::class, $container->get(IContainerStub::class));
    }

    public function test_alias_instance()
    {
        $container = new Container();

        $container->instance('object', new stdClass());
        $container->alias('object', 'alias');

        $this->assertSame($container->get('object'), $container->get('alias'));
    }

    public function test_alias_same_exception()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Alias [name] is aliased to itself.');

        $container = new Container();
        $container->alias('name', 'name');
    }

    public function test_scoped()
    {
        $container = new Container();

        $container->scoped('object', fn() => new stdClass());

        $this->assertTrue($container->isScoped('object'));
        $this->assertTrue($container->has('object'));

        $instance1 = $container->get('object');

        $this->assertSame($instance1, $container->get('object'));
        $this->assertTrue($container->resolved('object'));

        $container->forgetScopedInstances();

        $this->assertFalse($container->resolved('object'));

        $instance2 = $container->get('object');

        $this->assertNotSame($instance1, $instance2);

        $container->forgetScoped('object');

        $this->assertFalse($container->isScoped('object'));
        $this->assertFalse($container->resolved('object'));
        $this->assertFalse($container->has('object'));
    }

    public function test_scopedIf()
    {
        $container = new Container;

        $container->scopedIf('text', fn() => 'foo');
        $this->assertSame('foo', $container->get('text'));

        $container->scopedIf('text', fn() => 'bar');

        $this->assertSame('foo', $container->get('text'));
        $this->assertSame('foo', $container->get('text'));
    }

    public function test_has()
    {
        $container = new Container;
        $container->bind(ContainerConcreteStub::class);

        $this->assertTrue($container->has(ContainerConcreteStub::class));
        $this->assertFalse($container->has(IContainerStub::class));

        $container = new Container;
        $container->bind(IContainerStub::class, ContainerConcreteStub::class);
        $this->assertTrue($container->has(IContainerStub::class));
        $this->assertTrue($container->has(ContainerConcreteStub::class));
        //$this->assertFalse($container->has(ConcreteStub::class));
    }

    public function test_resolved()
    {
        $container = new Container;

        $container->singleton('ConcreteStub', fn () => new ContainerConcreteStub() );
        $container->alias('ConcreteStub', 'foo' );

        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->resolved('foo'));

        $container->get('ConcreteStub');

        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function test_get_interface_when_not_resolvable()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(sprintf('Target class [%s] is not instantiable.', IContainerStub::class));

        $container = new Container();
        $container->set('ConcreteStub', IContainerStub::class);
        $container->get('ConcreteStub');
    }

    public function test_get_class_when_not_found()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Target class [ConcreteStub] does not exist.');

        $container = new Container();
        $container->get('ConcreteStub');
    }

    public function test_get_class_without_constructor()
    {
        $container = new Container;
        $this->assertInstanceOf(ContainerConcreteStub::class, $container->get(ContainerConcreteStub::class));
    }

    public function test_get_class_with_constructor()
    {
        $container = new Container;
        $container->set(IContainerStub::class, ImplementationContainerStub::class);
        $instance = $container->get(DependentStub::class);

        $this->assertInstanceOf(DependentStub::class, $instance);
        $this->assertInstanceOf(ImplementationContainerStub::class, $instance->impl);
    }

    public function test_get_class_with_unresolvable_dependency()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(sprintf(
            'Target class [%s] is not instantiable while building [%s].',
            IContainerStub::class, DependentStub::class
        ));

        $container = new Container;
        $instance = $container->get(DependentStub::class);
    }

    public function test_get_class_with_int_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveIntStub::class);
        $this->assertSame(10, $instance->number);

        $instance = $container->get(PrimitiveIntStub::class, ['number' => 27]);
        $this->assertSame(27, $instance->number);

        $instance = $container->get(PrimitiveIntStub::class, ['number' => '27']);
        $this->assertSame(27, $instance->number);
    }

    public function test_get_class_with_float_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveFloatStub::class);
        $this->assertSame(0.07, $instance->number);

        $instance = $container->get(PrimitiveFloatStub::class, ['number' => 27.009]);
        $this->assertSame(27.009, $instance->number);

        $instance = $container->get(PrimitiveFloatStub::class, ['number' => '27']);
        $this->assertSame(27.0, $instance->number);
    }

    public function test_get_class_with_string_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveStringStub::class);
        $this->assertSame('hello', $instance->text);

        $instance = $container->get(PrimitiveStringStub::class, ['text' => 'imhotep']);
        $this->assertSame('imhotep', $instance->text);

        $instance = $container->get(PrimitiveStringStub::class, ['text' => 105]);
        $this->assertSame('105', $instance->text);
    }

    public function test_get_class_with_bool_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveBoolStub::class);
        $this->assertTrue($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => false]);
        $this->assertFalse($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => 1]);
        $this->assertTrue($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => 0]);
        $this->assertFalse($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => '1']);
        $this->assertTrue($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => '0']);
        $this->assertFalse($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => 'yes']);
        $this->assertTrue($instance->state);

        $instance = $container->get(PrimitiveBoolStub::class, ['state' => '']);
        $this->assertFalse($instance->state);
    }

    public function test_get_class_with_array_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveArrayStub::class);
        $this->assertSame(['hello', 'world'], $instance->list);

        $instance = $container->get(PrimitiveArrayStub::class, ['list' => ['imhotep']]);
        $this->assertSame(['imhotep'], $instance->list);


        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Imhotep\Tests\ContainerNew\PrimitiveArrayStub::__construct(): Argument #1 ($list) must be of type array, string given');

        $container->get(PrimitiveArrayStub::class, ['list' => 'imhotep']);
    }

    public function test_get_class_with_variadic_primitive_in_constructor()
    {
        $container = new Container;

        $instance = $container->get(PrimitiveVariadicStub::class);
        $this->assertSame([], $instance->list);

        $instance = $container->get(PrimitiveVariadicStub::class, ['text' => 'hello', 'number' => 10]);
        $this->assertSame(['text' => 'hello', 'number' => 10], $instance->list);

        $instance = $container->get(PrimitiveVariadicTwoStub::class, ['text' => 'hello', 'number' => 10, 'data' => 'world', 'age' => 'millions']);
        $this->assertSame(10, $instance->number);
        $this->assertSame('hello', $instance->text);
        $this->assertSame(['data' => 'world', 'age' => 'millions'], $instance->list);
    }

    public function test_get_singleton_with_parameters()
    {
        $container = new Container;

        $container->singleton('foo', fn ($container, $parameters) => $parameters );

        $this->assertEquals(['name' => 'hello'], $container->get('foo', ['name' => 'hello']));
        $this->assertEquals(['name' => 'world'], $container->get('foo', ['name' => 'world']));
    }

    public function test_get_nested_parameter()
    {
        $container = new Container;

        $container->bind('bar', fn($container, $parameters) => $parameters );
        $container->bind('foo', fn($container, $parameters) => $container->get('bar') );

        $this->assertEquals([], $container->get('foo', ['something']));
    }

    public function test_get_nested_parameter_override()
    {
        $container = new Container;

        $container->bind('bar', fn($container, $parameters) => $parameters );
        $container->bind('foo', fn($container, $parameters) => $container->get('bar', ['imhotep']) );

        $this->assertEquals(['imhotep'], $container->get('foo', ['something']));
    }

    public function test_get_class_with_inject_variable_and_interface()
    {
        $container = new Container;

        $container->bind(IContainerStub::class, PrimitiveMixedContainerStub::class);
        $instance = $container->get(IContainerStub::class, ['first' => 'hello', 'third' => 'world']);

        $this->assertSame('hello', $instance->first);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->second);
        $this->assertSame('world', $instance->third);
    }

    public function test_get_class_with_empty_default_parameters()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Unresolvable dependency resolving [$first] in class {Imhotep\Tests\ContainerNew\PrimitiveMixedContainerStub}');

        $container = new Container;
        $container->get(PrimitiveMixedContainerStub::class);
    }

    public function test_rebinding()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container();
        $container->bind('foo', fn() => null);
        $container->rebinding('foo', fn() => $_SERVER['__test.rebind'] = true);
        $container->bind('foo', fn() => null);

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function test_rebinding_instance()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->instance('foo', fn() => null);
        $container->rebinding('foo', fn() => $_SERVER['__test.rebind'] = true);
        $container->instance('foo', fn() => null);

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function test_rebinding_false()
    {
        $_SERVER['__test.rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', fn() => $_SERVER['__test.rebind'] = true);
        $container->bind('foo', fn() => null);

        $this->assertFalse($_SERVER['__test.rebind']);
    }

    public function test_rebinding_instance_false()
    {
        $_SERVER['__test.rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', fn() => $_SERVER['__test.rebind'] = true);
        $container->instance('foo', fn() => null);

        $this->assertFalse($_SERVER['__test.rebind']);
    }

    public function test_refresh()
    {
        $container = new Container;

        $container->bind(IContainerStub::class, ImplementationContainerStub::class);

        $instance = $container->get(RefreshStub::class);

        $this->assertInstanceOf(ImplementationContainerStub::class, $instance->stub);

        $container->refresh(IContainerStub::class, $instance, 'change');

        $container->bind(IContainerStub::class, ImplementationContainerStubTwo::class);

        $this->assertInstanceOf(ImplementationContainerStubTwo::class, $instance->stub);
    }
}


class ContainerConcreteStub { }

interface IContainerStub { }

class ImplementationContainerStub implements IContainerStub { }

class ImplementationContainerStubTwo implements IContainerStub { }

class DependentStub
{
    public IContainerStub $impl;

    public function __construct(IContainerStub $impl)
    {
        $this->impl = $impl;
    }
}

class NestedDependentStub
{
    public $inner;

    public function __construct(DependentStub $inner)
    {
        $this->inner = $inner;
    }
}

class PrimitiveIntStub {
    public int $number;

    public function __construct(int $number = 10)
    {
        $this->number = $number;
    }
}

class PrimitiveStringStub {
    public string $text;

    public function __construct(string $text = 'hello')
    {
        $this->text = $text;
    }
}

class PrimitiveFloatStub {
    public float $number;

    public function __construct(float $number = 0.07)
    {
        $this->number = $number;
    }
}

class PrimitiveBoolStub {
    public bool $state;

    public function __construct(bool $state = true)
    {
        $this->state = $state;
    }
}

class PrimitiveArrayStub {
    public array $list;

    public function __construct(array $list = ['hello', 'world'])
    {
        $this->list = $list;
    }
}

class PrimitiveVariadicStub {
    public array $list;

    public function __construct(...$list)
    {
        $this->list = $list;
    }
}

class PrimitiveVariadicTwoStub {
    public int $number;
    public string $text;
    public array $list;

    public function __construct(int $number, string $text, ...$list)
    {
        $this->number = $number;
        $this->text = $text;
        $this->list = $list;
    }
}

class PrimitiveMixedContainerStub implements IContainerStub
{
    public $first;
    public $second;
    public $third;

    public function __construct($first, ContainerConcreteStub $second, $third)
    {
        $this->first = $first;
        $this->second = $second;
        $this->third = $third;
    }
}

class RefreshStub
{
    public IContainerStub $stub;

    public function __construct(IContainerStub $stub)
    {
        $this->stub = $stub;
    }

    public function change(IContainerStub $stub)
    {
        $this->stub = $stub;
    }
}