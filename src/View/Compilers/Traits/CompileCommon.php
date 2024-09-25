<?php declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileCommon
{
    protected function compilePhp(string $expression = null): string
    {
        if (! is_null($expression)) {
            return "<?php {$expression}; ?>";
        }

        return '<?php ';
    }

    protected function compileEndphp(): string
    {
        return ' ?>';
    }

    protected function compileIf($expression): string
    {
        return "<?php if {$expression}: ?>";
    }

    protected function compileUnless($expression): string
    {
        return "<?php if (! {$expression}): ?>";
    }

    protected function compileElseif($expression): string
    {
        return "<?php elseif {$expression}: ?>";
    }

    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    protected function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEndunless(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileWhile($expression): string
    {
        return "<?php while {$expression}: ?>";
    }

    protected function compileEndwhile(): string
    {
        return '<?php endwhile; ?>';
    }

    protected function compileFor($expression): string
    {
        return "<?php for {$expression}: ?>";
    }

    protected function compileEndfor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compileForeach($expression): string
    {
        return "<?php foreach {$expression}: ?>";
    }

    protected function compileEndforeach(): string
    {
        return '<?php endforeach; ?>';
    }

    protected function compileSwitch($expression): string
    {
        return "<?php switch {$expression}: ?>";
    }

    protected function compileCase($expression): string
    {
        return "<?php case {$expression}: ?>";
    }

    protected function compileDefault()
    {
        return '<?php default: ?>';
    }

    protected function compileEndswitch(): string
    {
        return '<?php endswitch; ?>';
    }

    protected function compileIsset($expression): string
    {
        return "<?php if(isset{$expression}):  ?>";
    }

    protected function compileEndisset(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEmpty($expression): string
    {
        return "<?php if(empty{$expression}):  ?>";
    }

    protected function compileEndempty(): string
    {
        return '<?php endif; ?>';
    }
}