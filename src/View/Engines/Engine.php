<?php

declare(strict_types=1);

namespace Imhotep\View\Engines;

use Imhotep\View\Factory;

abstract class Engine
{
    public function setCachePath(string $cachePath): void
    {

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