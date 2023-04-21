<?php

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\MakeCommand;
use Imhotep\Console\Input\InputOption;

class ProviderMakeCommand extends MakeCommand
{
    public static string $defaultName = 'make:provider';

    public static string $defaultDescription = 'Create a new provider file';

    protected string $type = 'Provider';

    protected function getStub(): string
    {
        $stub = '/stubs/provider.stub';

        return file_exists($customPath = $this->app->basePath($stub))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultClassNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Providers';
    }

    public function getOptions(): array
    {
        return [
            new InputOption('force', 'f', 0, 'Create the class even if the provider already exists'),
        ];
    }
}