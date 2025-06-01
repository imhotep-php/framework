<?php

namespace Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class AcceptedIfTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testValidValue()
    {
        $values = [true, 'true', 1, '1', 'yes', 'y', 'on'];

        foreach ($values as $value) {
            $validation = $this->validator->make(
                ['foo' => $value, 'bar' => 'imhotep'],
                ['foo' => 'accepted_if:bar,imhotep']
            );
            $this->assertTrue($validation->passes());
        }


        $validation = $this->validator->make(
            ['foo' => false, 'bar' => 'php'],
            ['foo' => 'accepted_if:bar,imhotep']
        );
        $this->assertTrue($validation->passes());
    }
    public function testInvalidValue()
    {
        $values = [false, 'no', 0];

        foreach ($values as $value) {
            $validation = $this->validator->make(
                ['foo' => $value, 'bar' => 'imhotep'],
                ['foo' => 'accepted_if:bar,imhotep']
            );
            $this->assertFalse($validation->passes());
            $this->assertSame(['accepted_if'], $validation->errors()->all());
        }
    }
}