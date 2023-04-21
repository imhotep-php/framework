<?php

declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileLayout
{
    public function compileExtends($name): string
    {
        $name = $this->stripBrackets($name);

        $this->layouts[] = "<?php echo \$__env->make({$name})->render(); ?>";

        return '';
    }

    public function compileSection($name): string
    {
        $name = $this->stripBrackets($name);
        return "<?php \$__env->startSection({$name}); ?>";
    }

    public function compileEndsection(): string
    {
        return "<?php \$__env->stopSection(); ?>";
    }

    public function compileOverwrite(): string
    {
        return "<?php \$__env->stopSection(true); ?>";
    }

    public function compileShow(): string
    {
        return "<?php echo \$__env->yieldSection(); ?>";
    }

    public function compileYield($name): string
    {
        $name = $this->stripBrackets($name);

        return "<?php echo \$__env->yieldContent({$name}); ?>";
    }
}