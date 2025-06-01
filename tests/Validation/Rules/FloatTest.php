<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class FloatTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'float']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|float']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'float']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|float']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|float']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values       = [0,   '0', 8,     -8, 15.2, -15.8, '12.34', '-12.94'];
        $modifyValues = [0.0, 0.0, 8.0, -8.0, 15.2, -15.8,   12.34,   -12.94];

        foreach ($values as $index => $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'float']);
            $this->assertTrue($validation->passes());
            $this->assertSame($modifyValues[$index], $validation->validated()->get('foo'));
        }
    }

    public function testInvalidValue()
    {
        $values = [true, '12f', 'f12', [0.4]];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|float']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['float'], $validation->errors()->all());
        }
    }
}