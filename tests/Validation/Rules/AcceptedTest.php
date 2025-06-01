<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class AcceptedTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'accepted']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|accepted']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'accepted']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|accepted']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|accepted']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = [true, 'true', 1, '1', 'yes', 'y', 'on'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'accepted']);
            $this->assertTrue($validation->passes());
        }
    }
    public function testInvalidValue()
    {
        $values = [false, 'no', 0];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'accepted']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['accepted'], $validation->errors()->all());
        }
    }
}