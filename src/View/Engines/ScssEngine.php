<?php

declare(strict_types=1);

namespace Imhotep\View\Engines;

use ScssPhp\ScssPhp\Compiler;
use Throwable;

class ScssEngine extends Engine
{
    public function get(string $path, array $data = []): string
    {
        return $this->evaluatePath($path, $data);
    }

    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        try {
            $this->require($path, $data);
        }
        catch(Throwable $e) {
            $this->handleException($e, $obLevel);
        }

        return trim(ob_get_clean());
    }

    protected function require(string $__path, array $__data): void
    {
        $scss = new Compiler();
        $scss->addImportPath(dirname($__path));

        echo "<style>".$scss->compileString(files()->get($__path))->getCss()."</style>";

        /*
        (function () use ($__path, $__data) {
            extract($__data, EXTR_SKIP);

            require $__path;
        })();
        */
    }

    protected function handleException(Throwable $e, $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}