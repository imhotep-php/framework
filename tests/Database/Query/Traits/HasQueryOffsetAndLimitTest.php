<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

trait HasQueryOffsetAndLimitTest
{
    public function testLimit(): void
    {
        $result = $this->users()
            ->limit(3)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals(3, $result[2]->id);
    }

    public function testOffset(): void
    {
        $result = $this->users()
            ->limit(2)
            ->offset(2)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals(3, $result[0]->id);
        $this->assertEquals(4, $result[1]->id);
    }
}