<?php

namespace Imhotep\Tests\ContainerNew;

use Imhotep\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTaggingTest extends TestCase
{
    public function test_tags()
    {
        $container = new Container;
        $container->tag(ImplementationTaggedStub::class, 'foo', 'bar');
        $container->tag(ImplementationTaggedStubTwo::class, ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $barResults = [];
        foreach ($container->tagged('bar') as $bar) {
            $barResults[] = $bar;
        }

        $this->assertInstanceOf(ImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ImplementationTaggedStub::class, $barResults[0]);
        $this->assertInstanceOf(ImplementationTaggedStubTwo::class, $fooResults[1]);

        $container = new Container;
        $container->tag([ImplementationTaggedStub::class, ImplementationTaggedStubTwo::class], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ImplementationTaggedStubTwo::class, $fooResults[1]);

        $this->assertCount(0, $container->tagged('this_tag_does_not_exist'));
    }

    public function test_tagged_services_lazy_loaded()
    {
        $container = $this->createPartialMock(Container::class, ['get']);
        $container->expects($this->once())->method('get')->willReturn(new ImplementationTaggedStub);

        $container->tag(ImplementationTaggedStub::class, 'foo');
        $container->tag(ImplementationTaggedStubTwo::class, 'foo');

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
            break;
        }

        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf(ImplementationTaggedStub::class, $fooResults[0]);
    }

    public function test_lazy_loaded_can_be_looped_over_multiple_times()
    {
        $container = new Container;
        $container->tag(ImplementationTaggedStub::class, 'foo');
        $container->tag(ImplementationTaggedStubTwo::class, ['foo']);

        $services = $container->tagged('foo');

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ImplementationTaggedStubTwo::class, $fooResults[1]);

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ImplementationTaggedStubTwo::class, $fooResults[1]);
    }
}

interface ITaggedStub { }

class ImplementationTaggedStub implements ITaggedStub { }

class ImplementationTaggedStubTwo implements ITaggedStub { }