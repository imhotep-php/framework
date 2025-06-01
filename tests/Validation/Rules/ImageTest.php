<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Http\Testing\File;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'image']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|image']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'image']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|image']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|image']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpeg')],
            ['foo' => 'image']
        );
        $this->assertTrue($validation->passes());

        // Valid because the extension is jpeg
        $validation = $this->validator->make(
            ['foo' => File::create('test.jpeg', '1kb')],
            ['foo' => 'image']
        );
        $this->assertTrue($validation->passes());
    }

    public function testInvalidValue()
    {
        $validation = $this->validator->make(
            ['foo' => File::create('test.txt', '1kb')],
            ['foo' => 'image']
        );
        $this->assertFalse($validation->passes());
    }
}