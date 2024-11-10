<?php declare(strict_types=1);

namespace Imhotep\Support;

class Stopwatch
{
    protected float $started = 0;

    protected float $last = 0;

    protected float $total = 0;

    protected int $precision;

    public function __construct(
        int $precision = 2,
        bool $start = true
    )
    {
        $this->precision = $precision;

        if ($start) static::start();
    }

    public function start(): static
    {
        $this->started = microtime(true);

        return $this;
    }

    public function stop(): static
    {
        $this->last = microtime(true) - $this->started;

        $this->total += $this->last;

        return $this;
    }

    public function last(bool $format = false): float|string
    {
        return $format ? $this->getFormatted($this->last) : round($this->last, $this->precision);
    }

    public function total(bool $format = false): float|string
    {
        return $format ? $this->getFormatted($this->total) : round($this->total, $this->precision);
    }

    protected function getFormatted(float $time): string
    {
        return number_format($time, $this->precision);
    }

    public static function force(\Closure $callback, &$time): mixed
    {
        $stopwatch = (new static())->start();

        $result = $callback();

        $time = $stopwatch->stop()->last();

        return $result;
    }
}