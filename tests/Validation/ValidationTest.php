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

    protected function validator(array $data, array $rules): Validator
    {
        return (new Factory())->make($data, $rules);
    }
}