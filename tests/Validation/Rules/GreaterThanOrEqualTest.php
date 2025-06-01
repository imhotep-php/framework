<?php

namespace Validation\Rules;

use Imhotep\Http\Testing\File;
use Imhotep\Http\UploadedFile;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class GreaterThanOrEqualTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue(): void
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'gte:5']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|gte:5']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue(): void
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'gte:5']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|gte:5']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|gte:5']);
        $this->assertFalse($validation->passes());
    }

    public function testStringValue(): void
    {
        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'string|gte:5']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 'hello', 'bar' => 'world!'],
            ['bar' => 'gte:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'string|gte:6']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 'hello', 'bar' => 'test'],
            ['bar' => 'gte:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());
    }

    public function testIntegerValue(): void
    {
        $validation = $this->validator->make(['foo' => 10], ['foo' => 'int|gte:10']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 10],
            ['bar' => 'gte:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 10], ['foo' => 'int|gte:11']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 10, 'bar' => 9],
            ['bar' => 'gte:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());
    }

    public function testFloatValue(): void
    {
        $validation = $this->validator->make(['foo' => 4.1], ['foo' => 'float|gte:4.1']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => 4.2, 'bar' => 4.2],
            ['bar' => 'gte:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 4.2], ['foo' => 'float|gte:4.25']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => 4.2, 'bar' => 4.1],
            ['bar' => 'gte:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());
    }

    public function testArrayValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5]],
            ['foo' => 'array|gte:5']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5], 'bar' => [0,1,2,3,4]],
            ['bar' => 'array|gte:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2,3]],
            ['foo' => 'array|gte:4']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5], 'bar' => [0,1,2,3]],
            ['bar' => 'array|gte:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());
    }

    public function testFileValue(): void
    {
        $file_1kb = File::create('test.txt', '1kb');
        $file_2kb = File::create('test.txt', '2kb');

        $validation = $this->validator->make(
            ['foo' => $file_1kb], ['foo' => 'file|gte:1kb']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => $file_1kb, 'bar' => $file_2kb], ['bar' => 'file|gte:foo']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => $file_2kb], ['foo' => 'file|gte:3kb']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());

        $validation = $this->validator->make(
            ['foo' => $file_2kb, 'bar' => $file_1kb], ['bar' => 'file|gte:foo']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['gte'], $validation->errors()->all());
    }
}