<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'array']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|array']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'array']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|array']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|array']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = [[], [1], [0 => ['3'], 1 => ['1', '2']]];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'array']);
            $this->assertTrue($validation->passes());
            $this->assertSame($value, $validation->validated()->get('foo'));
        }
    }

    public function testInvalidValue()
    {
        $values = [true, 10, '[]', '[1,2]'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|array']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['array'], $validation->errors()->all());
        }
    }
}