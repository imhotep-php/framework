<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Http\Testing\File;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'file']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|file']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'file']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|file']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|file']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue()
    {
        $validation = $this->validator->make(
            ['foo' => File::create('test.txt', '1kb')],
            ['foo' => 'file']
        );
        $this->assertTrue($validation->passes());
    }
}