<?php

namespace Validation\Rules;

use Imhotep\Contracts\Validation\ValidationException;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class RequiredUnlessTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testValidValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => 'simple', 'bar' => 'php'],
            ['bar' => 'required_unless:foo,imhotep']
        );

        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 'work', 'bar' => 'php'],
            ['bar' => 'required_unless:foo,imhotep,framework']
        );

        $this->assertTrue($validation->passes());
    }

    public function testInvalidValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => 'not work'],
            ['bar' => 'required_unless:foo,imhotep']
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(['required_unless'], $validation->errors()->all());
    }

    public function testRequireParameterField()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [field] on rule [required_unless]');

        $this->validator->make(
            ['foo' => 'imhotep', 'bar' => ''],
            ['bar' => 'required_unless']
        )->passes();
    }

    public function testRequireParameterValues()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [values] on rule [required_unless]');

        $this->validator->make(
            ['foo' => 'imhotep', 'bar' => ''],
            ['bar' => 'required_unless:foo']
        )->passes();
    }
}