<?php

namespace Imhotep\Debug;

use Imhotep\Debug\Dumper\CliDumper;
use Imhotep\Debug\Dumper\HtmlDumper;
use Imhotep\Debug\Dumper\VarCloner;

class VarDumper
{
    private static mixed $handler = null;

    public static function dump(mixed $var): mixed
    {
        if (is_null(static::$handler)) {
            static::register();
        }

        return (static::$handler)($var);
    }

    public function setHandler(callable $callable = null): ?callable
    {
        $prevHandler = static::$handler;

        // Prevent replacing the handler with expected format as soon as the env var was set:
        if (isset($_SERVER['VAR_DUMPER_FORMAT'])) {
            return $prevHandler;
        }

        static::$handler = $callable;

        return $prevHandler;
    }

    public static function register(): void
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'])) {
            $dumper = new CliDumper();
        }
        else {
            $dumper = new HtmlDumper();
        }

        $cloner = new VarCloner();

        static::$handler = function ($var) use ($dumper, $cloner) {
            $dumper->dump($cloner->cloneVar($var));
        };
    }
}