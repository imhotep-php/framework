<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Closure;
use Imhotep\Contracts\Validation\IValidator;

/**
 * @method static IValidator make(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static array validate(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static extend(string $rule, string|Closure $extension)
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