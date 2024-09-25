<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Filesystem\Filesystem;

class FileLoader
{
    protected Filesystem $files;

    protected array $paths = [];

    protected array $namespaces = [];

    public function __construct(Filesystem $files, string|array $path)
    {
        $this->files = $files;
        $this->paths = (array)$path;
    }

    public function addNamespace(string $namespace, string|array $path): static
    {
        if (isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = array_merge($this->namespaces[$namespace], (array)$path);
        }
        else {
            $this->namespaces[$namespace] = (array)$path;
        }

        return $this;
    }

    public function load(string $locale, string $group = '*', string $namespace = '*'): array
    {
        $loaded = [];

        if ($namespace !== '*' && isset($this->namespaces[$namespace])) {
            foreach ($this->namespaces[$namespace] as $path) {
                if ($group === '*') {
                    $this->loadFile($loaded, "{$path}/{$locale}");
                    $this->loadFile($loaded, "{$path}/{$locale}", 'json');
                }
                else {
                    $this->loadFile($loaded, "{$path}/{$locale}/{$group}");
                }
            }
        }

        foreach ($this->paths as $path) {
            if ($namespace === '*' && $group === '*') {
                $this->loadFile($loaded, "{$path}/{$locale}");
                $this->loadFile($loaded, "{$path}/{$locale}", 'json');
            }
            elseif ($namespace === '*' && $group !== '*') {
                $this->loadFile($loaded, "{$path}/{$locale}/{$group}");
            }
            elseif ($namespace !== '*' && $group === '*'){
                $this->loadFile($loaded, "{$path}/vendor/{$namespace}/{$locale}");
                $this->loadFile($loaded, "{$path}/vendor/{$namespace}/{$locale}", 'json');
            }
            elseif ($namespace !== '*' && $group !== '*') {
                $this->loadFile($loaded, "{$path}/vendor/{$namespace}/{$locale}/{$group}");
            }
        }

        return $loaded;
    }

    protected function loadFile(array &$loaded, string $path, string $ext = 'php'): void
    {
        if (! $this->files->exists($path = "{$path}.{$ext}")) {
            return;
        }

        // Load from php file
        if ($ext === 'php') {
            $data = $this->files->getRequire($path);

            if (! is_array($data)) {
                throw new \RuntimeException('Localization file [$path] return an invalid PHP array.');
            }

            $loaded = array_replace_recursive($loaded, $data);

            return;
        }

        // Load from json file
        $json = json_decode($this->files->get($path), true);

        if (is_null($json) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Localization file [$path] contains an invalid JSON.');
        }

        $loaded = array_replace_recursive($loaded, $json);
    }
}