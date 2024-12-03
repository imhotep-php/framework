<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\MakeCommand;
use Imhotep\Console\Input\InputOption;

class ControllerMakeCommand extends MakeCommand
{
    public static string $defaultName = 'make:controller';

    public static string $defaultDescription = 'Create a new controller';

    protected string $type = 'Controller';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/controller.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        $customPath = base_path($stub);
        $defaultPath = __DIR__.$stub;

        return file_exists($customPath) ? $customPath : $defaultPath;
    }

    protected function getDefaultClassNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Http\Controllers';
    }

    public function getOptions(): array
    {
        return [
            InputOption::builder('model', 'm')->valueOptional()
                ->description('Generate a resource controller for the given model')->build(),
        ];
    }
}