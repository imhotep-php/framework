<?php

namespace Imhotep\Contracts\Queue;

interface Queue
{
    public function connectionName(string $name = null): string|static;
}