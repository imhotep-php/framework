<?php declare(strict_types=1);

namespace Imhotep\Localization;

use Imhotep\Contracts\Localization\ILocalizationLoader;
use Imhotep\Filesystem\Filesystem;
use RuntimeException;

class FileLoader implements ILocalizationLoader
{
    protected Filesystem $files;

    protected array $paths = [];

    protected array $namespaces = [];

    protected array $loadedFiles = [];

    public function __construct(
        Filesystem $files,
        string|array $paths,
    )
    {
        $this->files = $files;
        $this->paths = (array)$paths;
    }

    public function paths(): array
    {
        return $this->paths;
    }

    public function addPath(array|string $path): void
    {
        $this->paths = array_unique(array_filter(array_merge($this->paths, (array)$path)));
    }

    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function addNamespace(string $ns, string|array $paths): static
    {
        if (! isset($this->namespaces[$ns])) {
            $this->namespaces[$ns] = [];
        }

        $this->namespaces[$ns] = array_unique(array_filter(array_merge(
            $this->namespaces[$ns], (array)$paths
        )));

        return $this;
    }

    /**
     * Загрузка основных переводов по namespace и группе.
     *
     * @param string $locale
     * @param string $ns
     * @param string $group
     * @return array
     */
    public function load(string $locale, string $ns = '*', string $group = '*'): array
    {
        // No namespace provided
        if ($ns === '*') {
            return $group === '*'
                ? $this->loadPaths($this->paths, fn($path) => "{$path}/{$locale}.json")
                : $this->loadPaths($this->paths, fn($path) => "{$path}/{$locale}/{$group}.php");
        }

        // Namespace provided with group
        if ($group === '*') {
            $lines = $this->loadPaths($this->namespaces[$ns], fn($path) => "{$path}/{$locale}.json");
            $overrides = $this->loadPaths($this->paths, fn($path) => "{$path}/vendor/{$ns}/{$locale}.json");

            return array_replace_recursive($lines, $overrides);
        }

        // Namespace provided without group
        $lines = $this->loadPaths($this->namespaces[$ns], fn($path) => "{$path}/{$locale}/{$group}.php");
        $overrides = $this->loadPaths($this->paths, fn($path) => "{$path}/vendor/{$ns}/{$locale}/{$group}.php");

        return array_replace_recursive($lines, $overrides);
    }

    protected function loadPaths(array $paths, callable $pathResolver): array
    {
        $loaded = [];

        foreach ($paths as $path) {
            $this->loadFile($pathResolver($path), $loaded);
        }

        return $loaded;
    }

    protected function loadFile(string $file, array &$loaded): void
    {
        if (! $this->shouldLoadFile($file)) {
            return;
        }

        if (str_ends_with($file, '.json')) {
            $this->loadJsonFile($file, $loaded);
        }
        elseif (str_ends_with($file, '.php')) {
            $this->loadPhpFile($file, $loaded);
        }
    }

    protected function loadJsonFile(string $file, array &$loaded): void
    {
        $json = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Localization file [$file] contains invalid JSON: " . json_last_error_msg());
        }

        if (is_array($json)) {
            $loaded = array_replace_recursive($loaded, $json);
        }
    }

    protected function loadPhpFile(string $file, array &$loaded): void
    {
        $data = $this->files->require($file);

        if (is_array($data)) {
            $loaded = array_replace_recursive($loaded, $data);
            return;
        }

        if ($data !== false) {
            throw new RuntimeException("Localization file [$file] must return an array.");
        }
    }

    protected function shouldLoadFile(string $file): bool
    {
        if (isset($this->loadedFiles[$file])) {
            return false;
        }

        $this->loadedFiles[$file] = true;

        return $this->files->exists($file);
    }
}