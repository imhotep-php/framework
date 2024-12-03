<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\MakeCommand;
use Imhotep\Contracts\Console\ConsoleException;

class CommandMakeCommand extends MakeCommand
{
    public static string $defaultName = 'make:command';

    public static string $defaultDescription = 'Create a new console command';

    protected string $type = 'Command';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/command.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        $customPath = base_path($stub);
        $defaultPath = __DIR__.$stub;

        return file_exists($customPath) ? $customPath : $defaultPath;
    }

    protected function buildClass($name, $namespace): string
    {
        $stub = parent::buildClass($name, $namespace);

        $stub = $this->replaceStubName($stub, 'command', $this->getCommandName());

        return $stub;
    }

    protected function getCommandName(): string
    {
        $command = str_replace('Command', '', lcfirst($this->getClassName()));
        $command = preg_split('/(?=[A-Z])/', $command, 2);

        $command = array_map(fn ($value) => strtolower($value), $command);

        return implode(':', $command);
    }

    protected function getDefaultClassNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Console\Commands';
    }
}