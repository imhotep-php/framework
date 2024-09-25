<?php

namespace Imhotep\Queue\Drivers\Null;

use Imhotep\Queue\Queue;

class NullQueue extends Queue
{
    public function size(string $queue = null): int
    {
        return 0;
    }

    public function push(mixed $job, mixed $data = null, string $queue = null)
    {
        //
    }

    public function later(int $dalay, mixed $job, mixed $data = null, string $queue = null)
    {
        //
    }

    public function pop(string $queue = null)
    {
        //
    }
}