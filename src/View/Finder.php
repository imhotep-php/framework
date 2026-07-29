<?php declare(strict_types=1);

namespace Imhotep\View;

use FilesystemIterator;
use Imhotep\Filesystem\Filesystem;
use Imhotep\Support\Str;

class Finder
{
    protected Filesystem $files;

    protected array $paths = [];

    protected array $namespaces = [];

    protected array $extensions = ['blade.php', 'moon.php', 'php', 'html', 'css', 'scss', 'js'];

    public array $views = [];

    public function __construct(Filesystem $files, string|array $paths)
    {
        $this->files = $files;

        $paths = (array)$paths;

        foreach ($paths as $path) {
            $this->paths[] = $this->resolvePath($path);
        }
    }

    public function exists(string $view): bool
    {
        if ($data = $this->find($view)) {
            $this->views[$view] = $data;

            return true;
        }

        return false;
    }

    public function find(string $view): ?array
    {
        if (isset($this->views[$view])) {
            return $this->views[$view];
        }

        if (str_contains($view, '::')) {
            list($ns, $name) = explode("::", $view);

            return $this->views[$view] = $this->findInPaths($name, $this->namespaces[$ns]);
        }

        return $this->views[$view] = $this->findInPaths($view, $this->paths);
    }

    protected function findInPaths(string $view, array $paths): ?array
    {
        $names = $this->getPossibleViewFiles($view);

        foreach ($paths as $path) {
            if ($result = $this->findInPath($view, $path, $names) ) {
                return $result;
            }
        }

        return null;
    }

    protected function findInPath(string $view, string $path, array $names, string $prefix = ''): ?array
    {
        $path = $this->resolvePath($path);

        if (! is_dir($path)) {
            return null;
        }

        $files = $this->files->allFiles($path);

        $substrLength = strlen($path) + 1;

        foreach ($files as $file) {
            $name = str_replace('/', '.', substr($file->getRealPath(), $substrLength));

            if (! in_array($name, $names)) {
                continue;
            }

            $extension = substr($name, strlen($view) + 1);

            return [
                'path' => $file->getRealPath(),
                'name' => (empty($prefix)?'':$prefix.'.').$file->getBasename('.'.$extension),
                'extension' => $extension
            ];
        }

        return null;
    }

    protected function getPossibleViewFiles(string $view): array
    {
        return array_map(fn ($extension) => $view.'.'.$extension, $this->extensions);
    }


    // Work with path
    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setPaths(array $paths): static
    {
        $this->paths = $paths;

        return $this;
    }

    public function addPath(string $path, string $prefix = '', string $namespace = ''): static
    {
        $path = $this->resolvePath($path);

        $this->scanPath($this->paths[] = $path, $prefix, $namespace);

        return $this;
    }

    public function prependPath(string $path): static
    {
        array_unshift($this->paths, $this->resolvePath($path));

        return $this;
    }


    // Work with namespace
    public function addNamespace(string $namespace, string|array $paths, bool $prepend = false): static
    {
        $paths = (array)$paths;

        if (isset($this->namespaces[$namespace])) {
            $paths = ($prepend) ? array_merge($paths, $this->namespaces[$namespace])
                : array_merge($this->namespaces[$namespace], $paths);
        }

        $this->namespaces[$namespace] = $paths;

        return $this;
    }

    public function prependNamespace(string $namespace, string|array $paths): static
    {
        return $this->addNamespace($namespace, $paths, true);
    }

    public function replaceNamespace(string $namespace, string|array $paths): static
    {
        $this->namespaces[$namespace] = (array)$paths;

        return $this;
    }

    public function setNamespaces(array $namespaces): static
    {
        $this->namespaces = $namespaces;

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
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

    protected function resolvePath(string $path): string
    {
        return realpath($path) ?: $path;
    }

    protected function resolveFile(string $path): ?array
    {
        foreach ($this->extensions as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                return [
                    'name' => basename($path, '.'.$ext),
                    'extension' => $ext
                ];
            }
        }

        return null;
    }


    // Work with Vue
    public function findVueComponent(string $view): ?array
    {
        if (str_contains($view, '::')) {
            list($ns, $name) = explode("::", $view);

            $ns = strtolower($ns);

            if (! isset($this->namespaces[$ns])) {
                return null;
            }

            $name = $this->normalizeVueName($name);
            $view = "$ns::$name";
            $paths = $this->namespaces[$ns];
        }
        else {
            $view = $name = $this->normalizeVueName($view);
            $paths = $this->paths;
        }

        if (isset($this->views['vue::'.$view])) {
            return $this->views['vue::'.$view];
        }

        return $this->views['vue::'.$view] = $this->findVueComponentInPaths($name, $paths, $view);
    }

    protected function findVueComponentInPaths(string $name, array $paths, string $view): ?array
    {
        foreach ($paths as $path) {
            if ($result = $this->findVueComponentInPath($name, $path.'/vue/components', $view) ) {
                return $result;
            }
        }

        return null;
    }

    protected function findVueComponentInPath(string $name, string $path, string $view, ?string $canonicalName = null): ?array
    {
        if (is_null($canonicalName)) {
            $canonicalName = Str::studly(str_replace('.', ' ', $name));
        }

        $path = $this->resolvePath($path);

        if (! is_dir($path)) {
            return null;
        }

        $files = new FilesystemIterator($path);

        foreach ($files as $file) {
            if ($file->isFile()) {
                // Проверяем расширение: *.vue, *.js
                if (! in_array($file->getExtension(), ['vue', 'js'])) {
                    continue;
                }

                $filename = $file->getBasename('.' . $file->getExtension());
                $filename = $this->normalizeVueName($filename);

                if ($filename === $name) {
                    return [
                        'view' => $view,
                        'path' => $file->getRealPath(),
                        'name' => $canonicalName,
                        'extension' => $file->getExtension(),
                    ];
                }
            }

            if ($file->isDir()) {
                $filename = $file->getBasename('.' . $file->getExtension());
                $filename = $this->normalizeVueName($filename);

                // Если у нас файл дублирует имя папки: Header/Header.vue
                if ($name === $filename) {
                    $spaceName = str_replace('.', ' ', $name);
                    $snakeName = Str::snake($spaceName, '-');
                    $studlyName = Str::studly($spaceName);

                    $variants = [
                        $file->getPathname().'/'.$snakeName.'.vue',
                        $file->getPathname().'/'.$snakeName.'.js',
                        $file->getPathname().'/'.$studlyName.'.vue',
                        $file->getPathname().'/'.$studlyName.'.js',
                    ];

                    foreach ($variants as $variant) {
                        if (file_exists($variant)) {
                            return [
                                'view' => $view,
                                'path' => $variant,
                                'name' => $canonicalName,
                                'extension' => pathinfo($variant, PATHINFO_EXTENSION),
                            ];
                        }
                    }
                }

                // Имя файла содержит название директории, углубляем поиск
                if (str_starts_with($name, $filename)) {
                    $newName = trim(substr($name, strlen($filename)), '.');

                    if ($found = $this->findVueComponentInPath($newName, $file->getPathname(), $view, $canonicalName)) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    protected function normalizeVueName(string $name): string
    {
        $name = preg_replace('/([A-Z])/', '-$1', $name);
        $name = preg_replace('/\-+/', '.', strtolower($name));
        return trim($name, '.');
    }
}