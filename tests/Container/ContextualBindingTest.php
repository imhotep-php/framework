<?php

namespace Imhotep\Tests\ContainerNew;

use Imhotep\Config\Repository;
use Imhotep\Container\Container;
use PHPUnit\Framework\TestCase;

class ContextualBindingTest extends TestCase
{
    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind(IContainerContextStub::class, ContainerContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind(IContainerContextStub::class, ContainerContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextStub::class)->give(function ($container) {
            return $container->make(ContainerContainerContextImplementationStubTwo::class);
        });

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $two->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance(IContainerContextStub::class, new ImplementationContainerStub);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $container->make(ContainerTestContextInjectOne::class)->impl);
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $container->instance(IContainerContextStub::class, new ImplementationContainerStub);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ImplementationContainerStub);
        $container->alias('stub', IContainerContextStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $container->instance('stub', new ImplementationContainerStub);
        $container->alias('stub', IContainerContextStub::class);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $container->bind('stub', ContainerContainerContextImplementationStub::class);
        $container->alias('stub', IContainerContextStub::class);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksForMultipleClasses()
    {
        $container = new Container;

        $container->bind(IContainerContextStub::class, ContainerContainerContextImplementationStub::class);

        $container->when([ContainerTestContextInjectTwo::class, ContainerTestContextInjectThree::class])->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectThree::class)->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerContainerContextImplementationStub);
        $container->alias('stub', IContainerContextStub::class);

        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextStub::class)->give(ContainerContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContainerContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance(IContainerContextStub::class, new ImplementationContainerStub);
        $container->instance(ContainerTestContainerContextInjectInstantiations::class, new ContainerTestContainerContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContainerContextInjectInstantiations::$instantiations);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextStub::class)->give(ContainerTestContainerContextInjectInstantiations::class);

        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);

        $this->assertEquals(1, ContainerTestContainerContextInjectInstantiations::$instantiations);
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(100);
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(function ($container) {
            return $container->make(ContainerConcreteStub::class);
        });
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->something);
    }

    public function testContextualBindingWorksWithAliasedTargets()
    {
        $container = new Container;

        $container->bind(IContainerContextStub::class, ContainerContainerContextImplementationStub::class);
        $container->alias(IContainerContextStub::class, 'interface-stub');

        $container->alias(ContainerContainerContextImplementationStub::class, 'stub-1');

        $container->when(ContainerTestContextInjectOne::class)->needs('interface-stub')->give('stub-1');
        $container->when(ContainerTestContextInjectTwo::class)->needs('interface-stub')->give(ContainerContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $two->impl);
    }

    public function testContextualBindingWorksForNestedOptionalDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectTwoInstances::class)->needs(ContainerTestContextInjectTwo::class)->give(function () {
            return new ContainerTestContextInjectTwo(new ContainerContainerContextImplementationStubTwo);
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectTwoInstances::class);
        $this->assertInstanceOf(
            ContainerTestContextWithOptionalInnerDependency::class,
            $resolvedInstance->implOne
        );
        $this->assertNull($resolvedInstance->implOne->inner);

        $this->assertInstanceOf(
            ContainerTestContextInjectTwo::class,
            $resolvedInstance->implTwo
        );
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->implTwo->impl);
    }

    public function testContextualBindingWorksForVariadicDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextStub::class)->give(function ($c) {
            return [
                $c->make(ContainerContainerContextImplementationStub::class),
                $c->make(ContainerContainerContextImplementationStubTwo::class),
            ];
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingWorksForVariadicDependenciesWithNothingBound()
    {
        $container = new Container;

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingWorksForVariadicAfterNonVariadicDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadicAfterNonVariadic::class)->needs(IContainerContextStub::class)->give(function ($c) {
            return [
                $c->make(ContainerContainerContextImplementationStub::class),
                $c->make(ContainerContainerContextImplementationStubTwo::class),
            ];
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadicAfterNonVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingWorksForVariadicAfterNonVariadicDependenciesWithNothingBound()
    {
        $container = new Container;

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadicAfterNonVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingWorksForVariadicDependenciesWithoutFactory()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextStub::class)->give([
            ContainerContainerContextImplementationStub::class,
            ContainerContainerContextImplementationStubTwo::class,
        ]);

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesTagsForArrayWithNoTagsDefined()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectArray::class)->needs('$stubs')->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectArray::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingGivesTagsForVariadicWithNoTagsDefined()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextStub::class)->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingGivesTagsForArray()
    {
        $container = new Container;

        $container->tag([
            ContainerContainerContextImplementationStub::class,
            ContainerContainerContextImplementationStubTwo::class,
        ], ['stub']);

        $container->when(ContainerTestContextInjectArray::class)->needs('$stubs')->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectArray::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesTagsForVariadic()
    {
        $container = new Container;

        $container->tag([
            ContainerContainerContextImplementationStub::class,
            ContainerContainerContextImplementationStubTwo::class,
        ], ['stub']);

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextStub::class)->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesValuesFromConfigOptionalValueNull()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('laravel', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertNull($resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigOptionalValueSet()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                    'alias' => 'lumen',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$alias')
            ->giveConfig('test.alias');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('laravel', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertSame('lumen', $resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigWithDefault()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'password' => 'hunter42',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username', 'DEFAULT_USERNAME');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('DEFAULT_USERNAME', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertNull($resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigArray()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                    'alias' => 'lumen',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigArray::class)
            ->needs('$settings')
            ->giveConfig('test');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigArray::class);

        $this->assertSame('laravel', $resolvedInstance->settings['username']);
        $this->assertSame('hunter42', $resolvedInstance->settings['password']);
        $this->assertSame('lumen', $resolvedInstance->settings['alias']);
    }

    public function testContextualBindingWorksForMethodInvocation()
    {
        $container = new Container;

        $container
            ->when(ContainerTestContextInjectMethodArgument::class)
            ->needs(IContainerContextStub::class)
            ->give(ContainerContainerContextImplementationStub::class);

        $object = new ContainerTestContextInjectMethodArgument;

        // array callable syntax...
        $valueResolvedUsingArraySyntax = $container->call([$object, 'method']);
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $valueResolvedUsingArraySyntax);

        // first class callable syntax...
        $valueResolvedUsingFirstClassSyntax = $container->call($object->method(...));
        $this->assertInstanceOf(ContainerContainerContextImplementationStub::class, $valueResolvedUsingFirstClassSyntax);
    }
}

interface IContainerContextStub
{
    //
}

class ContainerNonIContextStub
{
    //
}

class ContainerContainerContextImplementationStub implements IContainerContextStub
{
    //
}

class ContainerContainerContextImplementationStubTwo implements IContainerContextStub
{
    //
}

class ContainerTestContainerContextInjectInstantiations implements IContainerContextStub
{
    public static $instantiations;

    public function __construct()
    {
        static::$instantiations++;
    }
}

class ContainerTestContextInjectOne
{
    public $impl;

    public function __construct(IContainerContextStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwo
{
    public $impl;

    public function __construct(IContainerContextStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectThree
{
    public $impl;

    public function __construct(IContainerContextStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwoInstances
{
    public $implOne;
    public $implTwo;

    public function __construct(ContainerTestContextWithOptionalInnerDependency $implOne, ContainerTestContextInjectTwo $implTwo)
    {
        $this->implOne = $implOne;
        $this->implTwo = $implTwo;
    }
}

class ContainerTestContextWithOptionalInnerDependency
{
    public $inner;

    public function __construct(?ContainerTestContextInjectOne $inner = null)
    {
        $this->inner = $inner;
    }
}

class ContainerTestContextInjectArray
{
    public $stubs;

    public function __construct(array $stubs)
    {
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectVariadic
{
    public $stubs;

    public function __construct(IContainerContextStub ...$stubs)
    {
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectVariadicAfterNonVariadic
{
    public $other;
    public $stubs;

    public function __construct(ContainerNonIContextStub $other, IContainerContextStub ...$stubs)
    {
        $this->other = $other;
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectFromConfigIndividualValues
{
    public $username;
    public $password;
    public $alias = null;

    public function __construct($username, $password, $alias = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->alias = $alias;
    }
}

class ContainerTestContextInjectFromConfigArray
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }
}

class ContainerTestContextInjectMethodArgument
{
    public function method(IContainerContextStub $dependency)
    {
        return $dependency;
    }
}

class ContainerInjectVariableStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}