<?php declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileConditions
{
    protected function compileGuest(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php if(auth{$guard}->guest()): ?>";
    }

    protected function compileElseGuest(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php elseif(auth{$guard}->guest()): ?>";
    }

    protected function compileEndGuest(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileAuth(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php if(auth{$guard}->check()): ?>";
    }

    protected function compileElseAuth(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php elseif(auth{$guard}->check()): ?>";
    }

    protected function compileEndAuth(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileCsrf(): string
    {
        return "<?php echo '<input type=\"hidden\" name=\"_csrf\" value=\"'.csrf().'\">'; ?>";
    }

    protected function compileError($expression): string
    {
        $key = $this->stripBrackets($expression);

        return '<?php if ($errors->has('.$key.')): ' .
            'if (isset($message)) $__message = $message; ' .
            '$message = $errors->first('.$key.'); ?>';
    }

    protected function compileEndError(): string
    {
        return '<?php unset($message); ' .
            'if (isset($__message)) $message = $__message; ' .
            'endif; ?>';
    }
}
