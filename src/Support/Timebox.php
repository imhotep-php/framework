<?php declare(strict_types=1);

namespace Imhotep\Support;

use Closure;

class Timebox
{
    protected bool $returnEarly = false;

    public function call(Closure $callback, int $microseconds): mixed
    {
        $start = microtime(true);

        $result = $callback($this);

        $remainder = intval(  $microseconds - ((microtime(true) - $start) * 1000000));

        if (! $this->returnEarly && $remainder > 0) {
            usleep($remainder);
        }

        return $result;
    }

    public function returnEarly(): static
    {
        $this->returnEarly = true;

        return $this;
    }

    public function dontReturnEarly(): static
    {
        $this->returnEarly = false;

        return $this;
    }
}