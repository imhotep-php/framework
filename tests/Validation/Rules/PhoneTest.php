<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'phone']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|phone']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'phone']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|phone']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|phone']);
        $this->assertFalse($validation->passes());
    }

    public function testValidValue(): void
    {
        $values = [
            '8 123 045 06 07',
            '8 (123) 045-06-07',
            '7 (123) 045-06-07',
            '+7 (123) 045-06-07',
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(
                ['foo' => $value], ['foo' => 'phone']
            );

            $this->assertTrue($validation->passes());
            $this->assertSame('71230450607', $validation->validated()->get('foo'));
        }
    }

    public function testInvalidValue(): void
    {
        $values = [
            '23 045 06 07',
            '045-06-07',
            71230450607,
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(
                ['foo' => $value], ['foo' => 'required|phone']
            );

            $this->assertFalse($validation->passes());
            $this->assertSame(['phone'], $validation->errors()->all());
        }
    }
}