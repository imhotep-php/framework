<?php declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static \Imhotep\Contracts\Validation\IValidator make(array $data, array $rules, array $messages = [], array $customAttributes = [])
 * @method static array validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
 *
 * @see \Imhotep\Validation\Factory
 */

class Validator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'validator';
    }
}