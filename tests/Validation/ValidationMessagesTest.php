<?php

namespace Imhotep\Tests\Validation;

use Imhotep\Filesystem\Filesystem;
use Imhotep\Localization\FileLoader;
use Imhotep\Localization\Localizator;
use Imhotep\Validation\Factory;
use Imhotep\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationMessagesTest extends TestCase
{
    public function test_default_localization()
    {
        $validator = $this->validator(
            [
                'id' => '',
                'title' => 'Imhotep Framework'
            ],
            [
                'id' => 'required|int',
                'title' => 'required|string|min:50'
            ]
        );

        $this->assertFalse($validator->passes());
        $this->assertSame([
            'The id is required.',
            'The title must be at least 50 characters.'
        ], $validator->errors()->all());
    }

    protected function validator(array $data, array $rules): Validator
    {
        return (new Factory($this->localizator()))->make($data, $rules);
    }

    protected function localizator(): Localizator
    {
        return (new Localizator(
            new FileLoader(new Filesystem(), __DIR__.'/Lang'),
            'en', 'en')
        );
    }
}