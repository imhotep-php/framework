<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Http\Testing\File;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class DimensionsTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testBasic()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:width=100,height=100']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:min_width=100,min_height=100']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:max_width=100,max_height=100']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:ratio=1']
        );
        $this->assertTrue($validation->passes());
    }

    public function testInvalidWidth()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 110, height: 100)],
            ['foo' => 'dimensions:width=100']
        );
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 90, height: 100)],
            ['foo' => 'dimensions:min-width=100']
        );
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 110, height: 100)],
            ['foo' => 'dimensions:max-width=100']
        );
        $this->assertFalse($validation->passes());
    }

    public function testInvalidHeight()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 110)],
            ['foo' => 'dimensions:height=100']
        );
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 90)],
            ['foo' => 'dimensions:min-height=100']
        );
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 110)],
            ['foo' => 'dimensions:max-height=100']
        );
        $this->assertFalse($validation->passes());
    }

    public function testValidRatio()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:ratio=1']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 150)],
            ['foo' => 'dimensions:ratio=2/3']
        );
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 150, height: 100)],
            ['foo' => 'dimensions:ratio=1.5']
        );
        $this->assertTrue($validation->passes());
    }

    public function testInvalidRatio()
    {
        $validation = $this->validator->make(
            ['foo' => File::createImage('test.jpg', width: 100, height: 100)],
            ['foo' => 'dimensions:ratio=1.5']
        );
        $this->assertFalse($validation->passes());
        $this->assertSame(['dimensions'], $validation->errors()->all());
    }
}