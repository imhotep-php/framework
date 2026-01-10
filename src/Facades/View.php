<?php declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static \Imhotep\View\View make(string $view, array $data = [])
 * @method static \Imhotep\View\Engines\Engine getEngine(array $file)
 * @method static void share(string|array $key, mixed $value = null)
 * @method static array getShare(string $key, mixed $default = null)
 * @method static array getShared()
 * @method static bool exists(string $view)
 *
 * @see \Imhotep\View\Factory
 */

class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}