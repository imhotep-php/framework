<?php

namespace Imhotep\Tests\Validation\Rules;

use DateTime;
use Imhotep\Validation\Rules\DateRule;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    protected DateRule $rule;

    protected function setUp(): void
    {
        $this->rule = (new DateRule())->setParameters([]);
    }

    public function testValidDatesAfterModify()
    {
        $testCases = [
            // [input, expected format after modify]
            ['2023-05-15', 'Y-m-d'],
            ['2023-05-15 14:30:00', 'Y-m-d H:i:s'],
            ['15.05.2023', 'd.m.Y'],
            ['05/15/2023', 'm/d/Y'],
            [time(), 'U'], // timestamp
        ];

        foreach ($testCases as [$input, $format]) {
            $modified = $this->rule->modifyValue($input);
            $this->assertInstanceOf(DateTime::class, $modified);
            $this->assertTrue($this->rule->check($modified));
            $this->assertEquals($input, $modified->format($format));
        }
    }

    public function testInvalidDatesAfterModify()
    {
        $invalidDates = [
            'invalid-date',
            '2023-13-01', // invalid month
            [],
            null,
            true,
            // Числовые значения, которые не должны преобразовываться
            12345.67, // float
            '12345.67', // строка с float
        ];

        foreach ($invalidDates as $date) {
            $modified = $this->rule->modifyValue($date);
            $this->assertFalse(
                $this->rule->check($modified),
                "Failed for: ".print_r($date, true)." got: ".gettype($modified)
            );
            $this->assertEquals($date, $modified);
        }
    }

    public function testDateTimeObjects()
    {
        $now = new DateTime();
        $modified = $this->rule->modifyValue($now);
        $this->assertSame($now, $modified);
        $this->assertTrue($this->rule->check($modified));
    }
}