<?php declare(strict_types=1);

namespace Imhotep\Contracts;

interface Renderable
{
    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function render(): string;
}