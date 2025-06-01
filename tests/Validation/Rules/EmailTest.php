<?php

namespace Imhotep\Tests\Validation\Rules;

use Imhotep\Support\Str;
use Imhotep\Validation\Factory;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    protected Factory $validator;

    protected function setUp(): void
    {
        $this->validator = new Factory();
    }

    public function testEmptyValue()
    {
        $validation = $this->validator->make(['foo' => ''], ['foo' => 'email']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => ''], ['foo' => 'required|email']);
        $this->assertFalse($validation->passes());
    }

    public function testNullValue()
    {
        $validation = $this->validator->make(['foo' => null], ['foo' => 'email']);
        $this->assertFalse($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'nullable|email']);
        $this->assertTrue($validation->passes());

        $validation = $this->validator->make(['foo' => null], ['foo' => 'required|email']);
        $this->assertFalse($validation->passes());
    }

    public function testBasic()
    {
        $values = [
            'ImhoTep@тест.рф',
            'ImhoTep@gmail.com',
            'ImhoTep@foo.bar',
            'ImhoTep1213@foo.bar.baZ',
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(['email' => $value], ['email' => 'email']);

            $this->assertTrue($validation->passes());
            $this->assertSame(Str::lower($value), $validation->validated()->get('email'));
        }
    }

    public function testValidWithFilterAndIdn()
    {
        $values = [
            'ImhoTep@тест.рф',
            'ImhoTep@gmail.com',
            'ImhoTep@foo.bar',
            'ImhoTep1213@foo.bar.baZ',
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(['email' => $value], ['email' => 'email:filter,idn']);

            $this->assertTrue($validation->passes());
            $this->assertSame(Str::lower($value), $validation->validated()->get('email'));
        }
    }

    public function testInvalidWithFilterWithoutIdn()
    {
        $values = [
            'имхотеп@тест.рф', // idn is false
            'imho tep@gmail.com',
            'imhotep@gmail',
            'imhotep.gmail.com',
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(['email' => $value], ['email' => 'email:filter']);

            $this->assertFalse($validation->passes());
            $this->assertSame(['email'], $validation->errors()->all());
        }
    }

    public function testValidWithDns()
    {
        $values = [
            'Imhotep@объясняем.рф',
            'Imhotep@gmail.com',
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(['email' => $value], ['email' => 'email:dns,idn']);

            $this->assertTrue($validation->passes());
            $this->assertSame(Str::lower($value), $validation->validated()->get('email'));
        }
    }

    public function testInvalidWithDns()
    {
        $values = [
            'imhotep@несуществующийдомен.рф',
            'imhotep@foo.bar'
        ];

        foreach ($values as $value) {
            $validation = $this->validator->make(['email' => $value], ['email' => 'email:dns,idn']);

            $this->assertFalse($validation->passes());
            $this->assertSame(['email'], $validation->errors()->all());
        }
    }
}