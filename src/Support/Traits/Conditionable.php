<?php declare(strict_types=1);

namespace Imhotep\Support\Traits;

use Closure;

trait Conditionable
{
    public function when(Closure|bool|null $value = null, ?callable $callback = null, ?callable $default = null): static
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if ($value) {
            return $callback ? ($callback($this, $value) ?? $this) : $this;
        }

        return $default ? ($default($this, $value) ?? $this) : $this;
    }

    public function unless(Closure|bool|null $value = null, ?callable $callback = null, ?callable $default = null): static
    {
        return $this->when(
            ! ($value instanceof Closure ? $value($this) : $value),
            $callback,
            $default
        );
    }
}