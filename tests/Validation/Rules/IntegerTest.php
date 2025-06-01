<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class IntegerTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'int']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|int']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'int']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|int']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|int']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values       = [0, '0', 8, -8, 15.2, -15.8, '12.34', '-12.94'];
        $modifyValues = [0,   0, 8, -8,   15,   -15,      12,      -12];

        foreach ($values as $index => $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'int']);
            $this->assertTrue($validation->passes());
            $this->assertSame($modifyValues[$index], $validation->validated()->get('foo'));
        }
    }

    public function testInvalidValue()
    {
        $values = [true, '12f', 'f12'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|int']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['int'], $validation->errors()->all());
        }
    }
}