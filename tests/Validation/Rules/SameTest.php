<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Contracts\Validation\ValidationException;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class SameTest extends TestCase
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
            ['foo' => 'required|int', 'bar' => 'same:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => ''],
            ['foo' => 'required|int', 'bar' => 'required|same:foo']
        );
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => null],
            ['foo' => 'required|int', 'bar' => 'same:foo']
        );
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => null],
            ['foo' => 'required|int', 'bar' => 'nullable|same:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => null],
            ['foo' => 'required|int', 'bar' => 'required|same:foo']
        );
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10],
            ['foo' => 'required|int', 'bar' => 'same:foo']
        );
        $this->assertTrue($validation->passes());

        // Same, since bar is converted from a float to an integer.
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10.0],
            ['foo' => 'required|int', 'bar' => 'int|same:foo']
        );
        $this->assertTrue($validation->passes());
        $this->assertSame(['foo' => 10, 'bar' => 10], $validation->validated()->toArray());
    }

    public function testInvalidValue()
    {
        // Not same, foo is an integer and bar is a string.
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => '10'],
            ['foo' => 'required|int', 'bar' => 'same:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['same'], $validation->errors()->all());

        // Not same, foo is an integer and bar is a float.
        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10.0],
            ['foo' => 'required|int', 'bar' => 'same:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['same'], $validation->errors()->all());
    }

    public function testRequireParameters()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [field] on rule [same]');

        $this->validator->make(
            ['foo' => 10, 'bar' => '10'],
            ['foo' => 'int', 'bar' => 'same']
        )->passes();
    }
}