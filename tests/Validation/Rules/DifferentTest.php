<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Contracts\Validation\ValidationException;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class DifferentTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => ''],
            ['foo' => 'required|int', 'bar' => 'different:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => ''],
            ['foo' => 'required|int', 'bar' => 'required|different:foo']
        );
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => null],
            ['foo' => 'required|int', 'bar' => 'nullable|different:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => null],
            ['foo' => 'required|int', 'bar' => 'required|different:foo']
        );
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 5],
            ['foo' => 'required|int', 'bar' => 'different:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2], 'bar' => [3,4]],
            ['foo' => 'required|array', 'bar' => 'array|different:foo']
        );
        $this->assertTrue($validation->passes());
    }

    public function testInvalidValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10],
            ['foo' => 'required|int', 'bar' => 'different:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['different'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => [0,1], 'bar' => [0,1]],
            ['foo' => 'required', 'bar' => 'different:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['different'], $validation->errors()->all());
    }

    public function testRequireParameters()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [field] on rule [different]');

        $this->validator->make(
            ['foo' => 10, 'bar' => 10],
            ['foo' => 'int', 'bar' => 'different']
        )->passes();
    }
}