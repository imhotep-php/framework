<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Http\UploadedFile;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class MinTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testStringValue(): void
    {
        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'required|string|min:4']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 'hello'], ['foo' => 'required|string|min:10']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['min'], $validation->errors()->all());
    }

    public function testIntegerValue(): void
    {
        $validation = $this->validator->make(['foo' => 10], ['foo' => 'required|int|min:4']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 10], ['foo' => 'required|int|min:15']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['min'], $validation->errors()->all());
    }

    public function testFloatValue(): void
    {
        $validation = $this->validator->make(['foo' => 4.1], ['foo' => 'required|float|min:4.1']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => 4.1], ['foo' => 'required|float|min:4.2']);
        $this->assertFalse($validation->passes());
        $this->assertSame(['min'], $validation->errors()->all());
    }

    public function testArrayValue(): void
    {
        $validation = $this->validator->make(
            ['foo' => [1,2,3,4,5,6,7,8,9]],
            ['foo' => 'required|array|min:4']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => [1,2]],
            ['foo' => 'required|array|min:4']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['min'], $validation->errors()->all());
    }

    public function testFileValue(): void
    {
        $file = UploadedFile::createFrom([
            'name' => pathinfo(__FILE__, PATHINFO_BASENAME),
            'type' => 'text/plain',
            'size' => 2048, // 1kb
            'tmp_name' => __FILE__,
            'error' => 0
        ], true);

        $validation = $this->validator->make(
            ['foo' => $file], ['foo' => 'required|file|min:1kb']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => $file], ['foo' => 'required|file|min:3kb']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['min'], $validation->errors()->all());
    }
}