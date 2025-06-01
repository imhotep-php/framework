<?php declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static string name()
 * @method static string algo()
 * @method static array options()
 * @method static array info(string $hash)
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hash, array $options = [])
 * @method static bool needsRehash(string $hash, array $options = [])
 *
 * @see \Imhotep\Hash\HashManager
 */

class Hash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hash';
    }
}