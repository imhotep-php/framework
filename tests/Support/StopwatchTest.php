<?php

namespace Imhotep\Tests\Support;

use Imhotep\Support\Stopwatch;
use PHPUnit\Framework\TestCase;

class StopwatchTest extends TestCase
{
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function test_common()
    {
        $stopwatch = new Stopwatch(4);
        usleep(200000);
        $this->assertGreaterThanOrEqual(0.2, $stopwatch->stop()->last());
        $this->assertLessThanOrEqual(0.22, $stopwatch->last());

        $stopwatch->start();
        usleep(200000);
        $this->assertGreaterThanOrEqual(0.2, $stopwatch->stop()->last());
        $this->assertLessThanOrEqual(0.22, $stopwatch->last());

        $stopwatch->start();
        usleep(200000);
        $this->assertGreaterThanOrEqual(0.2, $stopwatch->stop()->last());
        $this->assertLessThanOrEqual(0.22, $stopwatch->last());

        $this->assertGreaterThanOrEqual(0.6, $stopwatch->total());
        $this->assertLessThanOrEqual(0.62, $stopwatch->total());
    }
}