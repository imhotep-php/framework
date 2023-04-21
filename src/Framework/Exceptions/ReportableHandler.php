<?php

declare(strict_types=1);

namespace Imhotep\Framework\Exceptions;

use Closure;
use Throwable;

class ReportableHandler
{
    protected bool $shouldStop = false;

    public function __construct(
        protected Closure $callback
    ) {}

    public function __invoke(Throwable $e): bool
    {
        $result = call_user_func($this->callback, $e);

        if ($result === false) {
            return false;
        }

        return ! $this->shouldStop;
    }

    public function handles(Throwable $e): bool
    {
        /*foreach ($this->firstClosureParameterTypes($this->callback) as $type) {
            if (is_a($e, $type)) {
                return true;
            }
        }*/

        return false;
    }

    public function stop(): static
    {
        $this->shouldStop = true;

        return $this;
    }
}