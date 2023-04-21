<?php

declare(strict_types=1);

namespace Imhotep\View;

use Imhotep\Container\Container;
use Imhotep\View\Engines\Engine;
use Imhotep\View\Engines\EngineManager;

class Factory
{
    protected Container $container;

    protected Finder $finder;

    protected EngineManager $engine;

    protected array $shared = [];

    protected string $cachePath = '';

    public function __construct(Container $container, Finder $finder, EngineManager $engine, array $config)
    {
        $this->container = $container;
        $this->finder = $finder;
        $this->engine = $engine;

        if (isset($config['cache_path']) && is_dir($config['cache_path'])) {
            $this->cachePath = $config['cache_path'];
        }

        $this->share('__env', $this);
    }

    public function make(string $view, array $data = []): View
    {
        $file = $this->finder->find($view);

        $data = array_merge($this->shared, $data);

        return new View($this, $this->getEngine($file), $view, $file['path'], $data);
    }

    public function getEngine(array $file): Engine
    {
        $engine = $this->engine->resolveByExtension($file['extension']);

        $engine->setFactory($this);
        $engine->setCachePath($this->cachePath);

        return $engine;
    }

    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        }
        else {
            $this->shared[$key] = $value;
        }
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    public function exists(string $view): bool
    {

        return $this->finder->exists($view);;
    }


    protected static array $sectionContents = [];
    protected static array $sectionStack = [];

    public function startSection(string $name, string $content = null): void
    {
        if (is_null($content)) {
            ob_start();
            static::$sectionStack[] = $name;

            //dump(static::$sectionStack);
        }
        else {
            $this->extendSection($name, $content);
        }
    }

    public function extendSection(string $name, string $content): void
    {
        //var_dump($name, $content, static::$sectionContents[$name] ?? null);

        if (isset(static::$sectionContents[$name])) {
            $content = str_replace('@parent', $content, static::$sectionContents[$name]);

            //$content = str_replace('##__PARENT_SECTION_CONTENT__##', $content, static::$sectionStack[$name]);
        }

        static::$sectionContents[$name] = $content;

        //dump(static::$sectionContents);
    }

    public function stopSection($overwrite = false): string
    {
        if (empty(static::$sectionStack)) {
            throw new \InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $name = array_pop(static::$sectionStack);

        if ($overwrite) {
            static::$sectionContents[$name] = ob_get_clean();
        }
        else {
            $this->extendSection($name, ob_get_clean());
        }

        return $name;
    }

    public function yieldSection()
    {
        //dump(static::$sectionStack);

        if (empty(static::$sectionStack)) {
            return '';
        }

        $name = $this->stopSection();

        return $this->yieldContent($name);
    }

    public function yieldContent($name)
    {
        //dump(static::$sectionStack);
        //dump($name, static::$sectionContents);
        //dump($name, static::$sectionContents[$name] );

        return static::$sectionContents[$name] ?? '';
    }
}