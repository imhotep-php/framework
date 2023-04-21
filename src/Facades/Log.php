<?php

declare(strict_types=1);

namespace Imhotep\Facades;

/**
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log(string|int $level, string|\Stringable $message, array $context = [])
 *
 * @see \Imhotep\Log\LogManager
 */
class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'log';
    }
}