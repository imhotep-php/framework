<?php

declare(strict_types=1);

namespace Imhotep\View\Engines;

use Imhotep\View\Compilers\MoonCompiler;
use Imhotep\View\Factory;

class MoonEngine extends PhpEngine
{
    protected MoonCompiler $compiler;

    public function __construct()
    {
        $this->compiler = new MoonCompiler();
    }

    public function setFactory(Factory $factory): void
    {
        $this->compiler->setFactory($factory);
    }

    public function setCachePath(string $cachePath): void
    {
        $this->compiler->setCachePath($cachePath);
    }

    public function get(string $path, array $data = []): string
    {
        $this->compiler->compile($path);

        //return "<pre>".htmlspecialchars(file_get_contents($this->compiler->getCompiledPath($path)))."</pre>";

        return $this->evaluatePath(
            $this->compiler->getCompiledPath($path),
            $data
        );
    }
}