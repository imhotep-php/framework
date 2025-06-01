<?php

namespace Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class LowercaseTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'lowercase']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|lowercase']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'lowercase']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|lowercase']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|lowercase']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = ['username', 'user name', 'user_name', '1234', '#@$%^!'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'lowercase']);
            $this->assertTrue($validation->passes());
        }
    }

    public function testInvalidValue()
    {
        $values = ['USERNAME', 'Username', 'user Name'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|lowercase']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['lowercase'], $validation->errors()->all());
        }
    }
}