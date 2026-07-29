<?php declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileHelpers
{
    protected function compileDd($arguments)
    {
        return "<?php dd{$arguments}; ?>";
    }

    protected function compileDump($arguments)
    {
        return "<?php dump{$arguments}; ?>";
    }
}