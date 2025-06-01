<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;
use stdClass;

class RequiredTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testValidValue(): void
    {
        $values = ['imhotep', 0, 0.1, false, '0', [0], new stdClass()];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required']);
            $this->assertTrue($validation->passes());
        }
    }

    public function testInvalidValue(): void
    {
        $values = [null, '', []];

        foreach ($values as $value) {
            $validation = $this->validator->make(['foo' => $value], ['foo' => 'required']);
            $this->assertFalse($validation->passes());
        }
    }
}