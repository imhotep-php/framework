<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class UppercaseTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'uppercase']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|uppercase']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'uppercase']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|uppercase']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|uppercase']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $values = ['USERNAME', 'USER NAME', 'USER_NAME', '1234', '#@$%^!'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'uppercase']);
            $this->assertTrue($validation->passes());
        }
    }

    public function testInvalidValue()
    {
        $values = ['username', 'Username', 'user Name'];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required|uppercase']);
            $this->assertFalse($validation->passes());
            $this->assertSame(['uppercase'], $validation->errors()->all());
        }
    }
}