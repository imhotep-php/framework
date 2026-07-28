<?php

namespace Imhotep\Tests\Validation;

use Imhotep\Validation\Factory;
use Imhotep\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function test_data_without_rules()
    {
        $validator = $this->validator(['id' => '', 'parent_id' => null], []);

        $this->assertTrue($validator->passes());
        $this->assertSame(0, $validator->errors()->count());
    }

    public function test_data_dont_implicit()
    {
        $validator = $this->validator(
            ['id' => '', 'parent_id' => null],
            ['id' => 'int', 'parent_id' => 'int']
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(1, $validator->errors()->count());


        $validator = $this->validator(
            ['id' => '', 'parent_id' => null],
            ['id' => 'int', 'parent_id' => 'nullable|int']
        );

        $this->assertTrue($validator->passes());
        $this->assertSame(0, $validator->errors()->count());
    }

    public function test_extend_with_closure()
    {
        $ruleClosure = function ($attribute, $value, $fail) {
            if (! str_contains($value, 'Imhotep')) {
                $fail("Text 'Imhotep' does not exist");
            }
        };

        // Add global rule as "check_imhotep"
        $this->factory()->extend('check_imhotep', $ruleClosure);


        // Validate success
        $validator = $this->validator(
            ['title' => 'Imhotep Framework'],
            ['title' => ['required', $ruleClosure]]
        );

        $this->assertTrue($validator->passes());
        $this->assertSame(0, $validator->errors()->count());


        // Validate fails
        $validator = $this->validator(
            ['title' => 'PHP Framework'],
            ['title' => 'required|check_imhotep']
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        $this->assertSame(1, $validator->errors()->count());
        $this->assertEquals("Text 'Imhotep' does not exist", $validator->errors()->first('title'));
    }


    protected function factory(): Factory
    {
        return new Factory();
    }

    protected function validator(array $data, array $rules): Validator
    {
        return $this->factory()->make($data, $rules);
    }
}