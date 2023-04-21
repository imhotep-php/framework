<?php

declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileIncludes
{
    protected function compileInclude($expression): string
    {
        $expression = $this->stripBrackets($expression);

        $expression = str_replace('/', '.', $expression);

        return "<?php echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), array_flip(['__data', '__path'])))->render(); ?>";
    }
}