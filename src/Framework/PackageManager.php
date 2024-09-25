<?php

namespace Imhotep\Framework;

use Imhotep\Filesystem\Filesystem;

class PackageManager
{
    protected string $vendorPath;

    protected ?array $cache = null;

    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
        protected string $cachePath
    )
    {
        $this->vendorPath = $this->basePath.'/vendor';
    }

    public function providers(): array
    {
        return $this->config('providers');
    }

    public function aliases(): array
    {
        return $this->config('aliases');
    }

    public function config(string $key): array
    {
        $packages = $this->packages();

        $result = [];
        foreach ($packages as $package) {
            if (! empty($package[$key])) $result = array_merge($result, $package[$key]);
        }

        return $result;
    }

    protected function packages()
    {
        if (! is_null($this->cache)) {
            return $this->cache;
        }

        //if (! is_file($this->cachePath)) {
            return $this->build();
        //}

        if (is_file($this->cachePath)) {
            $this->cache = $this->files->getRequire($this->cachePath);
        }

        return $this->cache ?? [];
    }

    public function build(): array
    {
        $packages = [];

        if (file_exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        if (in_array('*', $ignore = $this->packagesToIgnore())) {
            return [];
        }


        foreach ($packages as $key => $package) {
            unset($packages[$key]); $key = $package['name'];

            $extra = $package['extra']['imhotep'] ?? null;

            if (is_null($extra)) continue;

            $packages[$key] = $extra;
            $ignore = array_merge($ignore, $extra['dont-discover'] ?? []);
        }

        foreach ($packages as $name => $package) {
            if (in_array($name, $ignore)) unset($packages[$name]);
        }

        $this->write($packages);

        return $packages;
    }

    protected function packagesToIgnore()
    {
        if (! is_file($this->basePath.'/composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
                $this->basePath.'/composer.json'
            ), true)['extra']['imhotep']['dont-discover'] ?? [];
    }

    protected function write(array $packages): void
    {
        $this->files->ensureDirectoryExists(dirname($this->cachePath));

        if (! is_writable($dirname = dirname($this->cachePath))) {
            throw new \Exception("The [{$dirname}] directory must be present and writable.");
        }

        $this->files->replace($this->cachePath, '<?php return '.var_export($packages, true).';');
    }
}