<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Http\Testing\File;
use Imhotep\Http\UploadedFile;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class GreaterThanTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue(): void
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'gt:5']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|gt:5']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue(): void
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'gt:5']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|gt:5']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|gt:5']);
        $this->assertFalse($validation->passes());
    }

    public function testStringValue(): void
    {
        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'required|string|gt:4']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 'hello', 'bar' => 'world!'],
            ['bar' => 'gt:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'required|string|gt:10']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 'hello', 'bar' => 'world'],
            ['bar' => 'gt:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());
    }

    public function testIntegerValue(): void
    {
        $validation = $this->validator->make(['foo' => 10], ['foo' => 'int|gt:4']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 11],
            ['bar' => 'gt:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 10], ['foo' => 'int|gt:15']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10],
            ['bar' => 'gt:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());
    }

    public function testFloatValue(): void
    {
        $validation = $this->validator->make(['foo' => 4.2], ['foo' => 'float|gt:4.1']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 4.1, 'bar' => 4.2],
            ['bar' => 'gt:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 4.1], ['foo' => 'float|gt:4.1']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 4.1, 'bar' => 4.1],
            ['bar' => 'gt:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());
    }

    public function testArrayValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5]],
            ['foo' => 'array|gt:4']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5], 'bar' => [0,1,2,3,4,5]],
            ['bar' => 'array|gt:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2]],
            ['foo' => 'array|gt:4']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5], 'bar' => [0,1,2,3,4]],
            ['bar' => 'array|gt:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());
    }

    public function testFileValue(): void
    {
        $file_1kb = File::create('test.txt', '1kb');
        $file_2kb = File::create('test.txt', '2kb');

        $validation = $this->validator->make(
            ['foo' => $file_2kb], ['foo' => 'file|gt:1kb']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => $file_1kb, 'bar' => $file_2kb], ['bar' => 'file|gt:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => $file_2kb], ['foo' => 'file|gt:3kb']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => $file_2kb, 'bar' => $file_2kb], ['bar' => 'file|gt:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gt'], $validation->errors()->all());
    }
}