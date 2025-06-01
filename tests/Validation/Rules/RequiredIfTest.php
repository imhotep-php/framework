<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Contracts\Validation\ValidationException;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class RequiredIfTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testValidValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => 'imhotep', 'bar' => 'php'],
            ['bar' => 'required_if:foo,imhotep']
        );

        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 'framework', 'bar' => 'php'],
            ['bar' => 'required_if:foo,imhotep,framework']
        );

        $this->assertTrue($validation->passes());
    }

    public function testInvalidValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => 'imhotep'],
            ['bar' => 'required_if:foo,imhotep']
        );

        $this->assertFalse($validation->passes());
        $this->assertSame(['required_if'], $validation->errors()->all());
    }

    public function testRequireParameterField()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [field] on rule [required_if]');

        $this->validator->make(
            ['foo' => 'imhotep', 'bar' => ''],
            ['bar' => 'required_if']
        )->passes();
    }

    public function testRequireParameterValues()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter [values] on rule [required_if]');

        $this->validator->make(
            ['foo' => 'imhotep', 'bar' => ''],
            ['bar' => 'required_if:foo']
        )->passes();
    }
}