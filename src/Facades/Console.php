<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Console\Command\ClosureCommand;
use Imhotep\Contracts\Console\Input;
use Imhotep\Contracts\Console\Kernel;
use Imhotep\Contracts\Console\Output;

/**
 * @method static int handle(Input $input, Output $output = null)
 * @method static int call(string $command, array $parameters, Output $output = null)
 * @method static int callSilent(string $command, array $parameters)
 * @method static ClosureCommand command(string $signature, callable $callback)
 * @method static void whenCommandLongerThan(int $threshold, callable $handler)
 * @method static void terminate(Input $input, int $status)
 *
 * @see \Imhotep\Framework\Console\Kernel
 */
class Console extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Kernel::class;
    }
}