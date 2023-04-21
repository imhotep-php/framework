<?php

declare(strict_types=1);

namespace Imhotep\Contracts;

interface Jsonable
{
    /**
     * Convert object to JSON representation
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string;
}