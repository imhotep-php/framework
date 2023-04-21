<?php

namespace Imhotep\Tests\Encryption;

use Imhotep\Events\Events;
use PHPUnit\Framework\TestCase;
use Exception;

class EventsDispatcherTest extends TestCase
{
    protected $events;

    protected function setUp(): void
    {
        unset($_SERVER['__event.test']);

        $this->events = $this->getDispatcher();
    }

    public function getDispatcher()
    {
        return new Events();
    }

    public function test_basic()
    {
        $this->events->listen('update', function ($name) {
            $_SERVER['__event.test'] = (empty($_SERVER['__event.test']) ? '' : $_SERVER['__event.test'].'_').$name;
        });

        $this->assertFalse(isset($_SERVER['__event.test']));

        $this->events->dispatch('update', ['name' => 'hello']);
        $this->events->dispatch('update', ['name' => 'john']);

        $this->assertSame('hello_john', $_SERVER['__event.test']);
    }

    public function test_halting()
    {
        $this->events->listen('foo', function ($foo) {
            $this->assertTrue(true);

            return $foo;
        });

        $this->events->listen('foo', function () {
            throw new Exception("should not be called");
        });

        $response = $this->events->dispatch('foo', ['bar'], true);
        $this->assertSame('bar', $response);

        $response = $this->events->until('foo', ['bar']);
        $this->assertSame('bar', $response);
    }

    public function test_responses()
    {
        $response = $this->events->dispatch('foo');

        $this->assertEquals([], $response);

        $response = $this->events->dispatch('foo', [], true);
        $this->assertNull($response);
    }

    public function test_propagation()
    {
        $this->events->listen('foo', function ($foo) {
            return $foo;
        });

        $this->events->listen('foo', function ($foo) {
            $_SERVER['__event.test'] = $foo;

            return false;
        });

        $this->events->listen('foo', function ($foo) {
            throw new Exception('should not be called');
        });

        $response = $this->events->dispatch('foo', ['bar']);

        $this->assertEquals(['bar'], $response);
        $this->assertSame('bar', $_SERVER['__event.test']);
    }

    public function test_falsy_propagation()
    {
        $this->events->listen('foo', function () {
            return 0;
        });
        $this->events->listen('foo', function () {
            return [];
        });
        $this->events->listen('foo', function () {
            return '';
        });
        $this->events->listen('foo', function () {

        });

        $response = $this->events->dispatch('foo', ['bar']);

        $this->assertEquals([0, [], '', null], $response);
    }
}