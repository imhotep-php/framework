<?php

declare(strict_types=1);

namespace Imhotep\Console\Command;

use Imhotep\Console\Input\InputArgument;
use Imhotep\Contracts\Console\ConsoleException;

abstract class MakeCommand extends Command
{
    protected string $type;

    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

    public function handle(): void
    {
        try{
            $name = $this->getClassName();
            $namespace = $this->getClassNamespace();
            $path = $this->getClassPath($namespace, $name);

            $this->makeClassDirectory($path);

            file_put_contents($path, $this->buildClass($name, $namespace));
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }

    }

    public function getArguments(): array
    {
        return [
            InputArgument::builder('name')->required()->description('The name of the class')->build(),
        ];
    }

    abstract protected function getStub();

    protected function getClassName(): string
    {
        $name = trim($this->input->getArgument('name'));

        $name = str_replace('/', '\\', $name);

        $name = array_reverse(explode("\\", $name))[0];

        if (!preg_match("/^([A-z])([A-z\d]+)$/", $name)) {
            throw new ConsoleException("The class name [$name] incorrect");
        }

        return $name;
    }

    protected function getClassNamespace(): string
    {
        $classNS = trim($this->input->getArgument('name'));
        $classNS = str_replace('/', '\\', $classNS);
        $classNS = trim($classNS, '\\');
        $classNS = implode('\\', array_slice(explode('\\', $classNS), 0, -1));

        $defaultNS = trim($this->getDefaultClassNamespace($this->getRootNamespace()), '\\');

        if ($classNS !== '') {
            return $defaultNS.'\\'.$classNS;
        }

        return $defaultNS;
    }

    protected function getClassPath(string $namespace, string $name)
    {
        $path = str_replace($this->getRootNamespace(), '', $namespace);
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');

        return $this->container->path( $path.'/'.$name.'.php' );
    }

    protected function makeClassDirectory($path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
    }

    protected function buildClass($name, $namespace): string
    {
        $stub = file_get_contents($this->getStub());

        $stub = $this->replaceStubName($stub, 'class', $name);
        $stub = $this->replaceStubName($stub, 'namespace', $namespace);

        return $stub;
    }

    protected function replaceStubName($stub, $key, $val): string
    {
        return preg_replace("/{{(\s+)?(Dummy)?".$key."(\s+?)?}}/i", $val, $stub);
    }

    protected function getRootNamespace(): string
    {
        return trim($this->container->getNamespace(), '\\');
    }

    protected function getDefaultClassNamespace(string $rootNamespace): string
    {
        return $rootNamespace;
    }


}