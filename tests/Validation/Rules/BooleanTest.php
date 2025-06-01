<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class BooleanTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'bool']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|bool']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'bool']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|bool']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|bool']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = [true, false, 1, 0, '1', '0', 'yes', 'no', 'y', 'n', 'on', 'off', 'true', 'false'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'bool']);
            $this->assertTrue($validation->passes());
        }
    }

    public function testInvalidValue()
    {
        $values = ['11', ['yes'], 10, -1];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|bool']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['bool'], $validation->errors()->all());
        }
    }
}