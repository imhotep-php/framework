<?php

declare(strict_types=1);

namespace Imhotep\View\Engines;

use Imhotep\View\Factory;

abstract class Engine
{
    protected bool $shouldCache = false;

    protected string $cachePath = '';

    public function setCache(bool $shouldCache, string $cachePath): void
    {
        $this->cachePath = $cachePath;

        $this->shouldCache = $shouldCache;
    }

    public function setFactory(Factory $factory): void
    {

    }

    /**
     * The view that was last to be rendered.
     *
     * @var string
     */
    protected $lastRendered;

    /**
     * Get the last view that was rendered.
     *
     * @return string
     */
    public function getLastRendered()
    {
        return $this->lastRendered;
    }
}