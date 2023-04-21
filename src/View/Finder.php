<?php

declare(strict_types=1);

namespace Imhotep\View;

class Finder
{
    protected array $paths = [];

    protected array $extensions = ['blade.php', 'moon.php', 'php', 'html', 'css', 'scss'];

    // ['name' => 'path', ...]
    protected array $cache = [];

    public function __construct(string|array $paths)
    {
        $this->paths = is_array($paths) ? $paths : [$paths];

        foreach ($this->paths as $path) $this->scanPath($path);
    }

    public function exists(string $name): bool
    {
        $name = str_replace('/', '.', $name);

        return isset($this->cache[ $name ]);
    }

    public function find(string $name): ?array
    {
        $name = str_replace('/', '.', $name);

        if (isset($this->cache[ $name ])) {
            return $this->cache[ $name ];
        }

        throw new ViewException("View [$name] not found.");
    }

    public function addPath(string $path, string $prefix = '', string $namespace = ''): void
    {
        if (! is_dir($path)) {
            return;
        }

        $this->scanPath($this->paths[] = $path, $prefix, $namespace);
    }

    public function addNamespace(string $path, string $namespace = ''): void
    {
        if (! is_dir($path)) {
            return;
        }

        $this->scanPath($this->paths[] = $path, '', $namespace);
    }

    protected function scanPath(string $path, string $prefix = '', string $namespace = ''): void
    {
        $filenames = array_diff(scandir($path), ['.','..']);

        foreach ($filenames as $filename) {
            if (is_dir($path.'/'.$filename)) {
                $this->scanPath(
                    $path.'/'.$filename,
                    (empty($prefix) ? '' : $prefix.'.') . $filename,
                    $namespace
                );
                continue;
            }

            if ($file = $this->resolveFilename($filename)) {
                $view = $prefix . (empty($prefix)?'':'.') . $file['name'];

                if (! empty($namespace)) {
                    $view = $namespace .'::'. $view;
                }

                $this->cache[ $view ] = array_merge($file, [
                    'path' => realpath($path.'/'.$filename)
                ]);
            }
        }
    }

    protected function resolveFilename($filename): ?array
    {
        foreach ($this->extensions as $ext) {
            if (str_ends_with($filename, '.'.$ext)) {
                return [
                    'name' => basename($filename, '.'.$ext),
                    'extension' => $ext
                ];
            }
        }

        return null;
    }
}