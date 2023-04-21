<?php

namespace Imhotep\Debug;

use Imhotep\Debug\Dumper\HtmlDumper;
use Imhotep\Debug\Dumper\VarCloner;

class VarDumper
{
    private static $handler = null;

    public static function dump(mixed $var): mixed
    {
        if (is_null(self::$handler)) {
            self::register();
        }

        return (self::$handler)($var);
    }

    public function setHandler(callable $callable = null): ?callable
    {
        $prevHandler = self::$handler;

        // Prevent replacing the handler with expected format as soon as the env var was set:
        if (isset($_SERVER['VAR_DUMPER_FORMAT'])) {
            return $prevHandler;
        }

        self::$handler = $callable;

        return $prevHandler;
    }

    public static function register(): void
    {
        $dumper = new HtmlDumper();
        $cloner = new VarCloner();

        self::$handler = function ($var) use ($dumper, $cloner) {
            $dumper->dump($cloner->cloneVar($var));
        };
    }
}