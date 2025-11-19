<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Rules\CoordsRule;
use PHPUnit\Framework\TestCase;

class CoordsTest extends TestCase
{
    protected CoordsRule $rule;

    protected function setUp(): void
    {
        $this->rule = new CoordsRule();
    }

    public function testValidCoordinates()
    {
        $validCoords = [
            [45.1234, -120.5678],
            [-45.1234, 120.5678],
            [90.0, 180.0], // edge cases
            [-90.0, -180.0],
            [0.0, 0.0],
        ];

        foreach ($validCoords as $coord) {
            $this->assertTrue($this->rule->check($coord), "Failed for: ".print_r($coord, true));
        }
    }

    public function testInvalidCoordinates()
    {
        $invalidCoords = [
            ['45.1234', '-120.5678'], // strings instead of floats
            [91.0, 180.0], // latitude out of range
            [-91.0, -180.0],
            [45.0, 181.0], // longitude out of range
            [45.0, -181.0],
            [45.0], // only one coordinate
            [45.0, -120.0, 100.0], // too many coordinates
            'not-an-array',
            123,
            null,
            true,
        ];

        foreach ($invalidCoords as $coord) {
            $this->assertFalse($this->rule->check($coord), "Failed for: ".print_r($coord, true));
        }
    }

    public function testModifyValue()
    {
        // Test numeric string conversion
        $result = $this->rule->modifyValue(['45.1234', '-120.5678']);
        $this->assertIsFloat($result[0]);
        $this->assertIsFloat($result[1]);
        $this->assertEquals(45.1234, $result[0]);
        $this->assertEquals(-120.5678, $result[1]);

        // Test integer conversion
        $result = $this->rule->modifyValue([45, -120]);
        $this->assertEquals(45.0, $result[0]);
        $this->assertEquals(-120.0, $result[1]);

        // Test invalid array
        $invalid = ['not-a-number', 'another-string'];
        $result = $this->rule->modifyValue($invalid);
        $this->assertEquals($invalid, $result);

        // Test non-array input
        $result = $this->rule->modifyValue('invalid-input');
        $this->assertEquals('invalid-input', $result);
    }

    public function testEdgeCases()
    {
        // Test exactly on boundaries
        $this->assertTrue($this->rule->check([90.0, 180.0]));
        $this->assertTrue($this->rule->check([-90.0, -180.0]));

        // Test just beyond boundaries
        $this->assertFalse($this->rule->check([90.0001, 180.0]));
        $this->assertFalse($this->rule->check([-90.0001, -180.0]));
        $this->assertFalse($this->rule->check([45.0, 180.0001]));
        $this->assertFalse($this->rule->check([45.0, -180.0001]));
    }
}