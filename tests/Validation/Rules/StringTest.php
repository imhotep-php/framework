<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class StringTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'string']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|string']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'string']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|string']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|string']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = ['bar', '133', '-123.52'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'string']);
            $this->assertTrue($validation->passes());
        }
    }

    public function testInvalidValue()
    {
        $values = [true, ['bar'], 124];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|string']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['string'], $validation->errors()->all());
        }
    }
}