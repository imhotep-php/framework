<?php

declare(strict_types=1);

namespace Imhotep\Database\Migrations;

class MigrationCreator
{
    public function __construct()
    {

    }

    public function create(string $name, string $path, string $table = '', bool $create = false): string
    {
        $stub = $this->getStub($table, $create);

        $path = $this->getPath($name, $path);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $stub = str_replace(['DummyTable', '{{ table }}', '{{table}}'], $table, $stub);

        file_put_contents($path, $stub);

        return $path;
    }

    public function guessTable($migration): array
    {
        if (preg_match("/create_([a-z0-9_]+)/", $migration, $match)) {
            $table = preg_replace('/(_table)$/', '', $match[1]);
            return [$table, $create = true];
        }

        if (preg_match("/(to|from|in)_([a-z0-9_]+)/", $migration, $match)) {
            $table = preg_replace('/(_table)$/', '', $match[1]);
            return [$table, $create = true];
        }

        return ['', $create = false];
    }

    protected function getStub(string $table, bool $create): string
    {
        if (empty($table)) {
            $stub = __DIR__.'/stubs/migration.stub';
        }
        elseif ($create) {
            $stub = __DIR__.'/stubs/migration.create.stub';
        }
        else {
            $stub = __DIR__.'/stubs/migration.update.stub';
        }

        return file_get_contents($stub);
    }

    protected function getPath(string $name, string $path): string
    {
        return $path.'/'.date("Y_m_d_His").'_'.$name.'.php';
    }
}