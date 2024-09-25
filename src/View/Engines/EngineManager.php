<?php

declare(strict_types=1);

namespace Imhotep\View\Engines;

use Imhotep\Container\Container;

class EngineManager
{
    protected Container $container;

    protected array $extensions = [
        'moon.php' => 'moon',
        'blade.php' => 'moon',
        'php' => 'php',
        'html' => 'file',
        'css' => 'file',
        'scss' => 'scss',
        'js' => 'js'
    ];

    protected array $resolvers = [];

    protected array $resolved = [];

    public function __construct(Container $container)
    {
        $this->register('file', function () {
            return new FileEngine();
        });

        $this->register('php', function () {
            return new PhpEngine();
        });

        $this->register('moon', function () {
            return new MoonEngine();
        });

        $this->register('scss', function () {
            return new ScssEngine();
        });

        $this->register('js', function () {
            return new JsEngine();
        });
    }

    public function register($name, \Closure $resolver): void
    {
        unset($this->resolved[$name]);

        $this->resolvers[$name] = $resolver;
    }

    public function resolveByExtension($extension): Engine
    {
        if (isset($this->extensions[$extension])) {
            return $this->resolve($this->extensions[$extension]);
        }

        throw new \InvalidArgumentException("Engine with extension [{$extension}] not found.");
    }

    public function resolve($name)
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (isset($this->resolvers[$name])) {
            return $this->resolved[$name] = call_user_func($this->resolvers[$name]);
        }

        throw new \InvalidArgumentException("Engine [{$name}] not found.");
    }
}