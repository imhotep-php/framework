<?php

declare(strict_types=1);

namespace Imhotep\Filesystem\Adapters;

use Imhotep\Contracts\Filesystem\Filesystem;
use Imhotep\Contracts\Filesystem\FilesystemException;
use Imhotep\Contracts\Filesystem\Driver;
use Imhotep\Filesystem\StreamedResponse;
use Imhotep\Http\UploadedFile;
use Imhotep\Support\File;

class LocalAdapter implements Filesystem
{
    protected Driver $driver;

    protected array $config;

    protected string $root = '';

    protected array $permissions = [
        'file' => [
            'public' => 0664,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0775,
            'private' => 0700,
        ]
    ];

    public function __construct(Driver $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;

        if (! isset($this->config['root']) && $this->driver->missing($this->config['root'])) {
            throw new FilesystemException('Property [root] incorrect in config.');
        }

        $this->root = rtrim($this->config['root'], '/');

        if (isset($config['permissions'])) {
            if (isset($config['permissions']['file'])) {
                $pfile = $config['permissions']['file'];

                $this->permissions['file']['public'] = $this->fixPermission($pfile['public'], $this->permissions['file']['public']);
                $this->permissions['file']['private'] = $this->fixPermission($pfile['private'], $this->permissions['file']['private']);
            }

            if (isset($config['permissions']['dir'])) {
                $pdir = $config['permissions']['dir'];

                $this->permissions['file']['public'] = $this->fixPermission($pdir['public'], $this->permissions['file']['public']);
                $this->permissions['file']['private'] = $this->fixPermission($pdir['private'], $this->permissions['file']['private']);
            }
        }
    }

    protected function fixPermission(string|int $chmod, string $default): string
    {
        $chmod = (int)$chmod;

        if ($chmod > 600 && $chmod < 800) {
            return '0'.$chmod;
        }

        return $default;
    }

    protected function fixPath(string $path = null): string
    {
        if (empty($path)) {
            return $this->root;
        }

        return $this->root . '/' . trim($path, '/');
    }

    public function isFile(string $path): bool
    {
        return $this->driver->isFile($this->fixPath($path));
    }

    public function isDirectory(string $path): bool
    {
        return $this->driver->isDirectory($this->fixPath($path));
    }

    public function exists(string $path): bool
    {
        return $this->driver->exists($this->fixPath($path));
    }

    public function missing(string $path): bool
    {
        return $this->driver->missing($this->fixPath($path));
    }

    public function fileExists(string $path): bool
    {
        return $this->isFile($path);
    }

    public function fileMissing(string $path): bool
    {
        return ! $this->isFile($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->isDirectory($path);
    }

    public function directoryMissing(string $path): bool
    {
        return ! $this->isDirectory($path);
    }

    public function getVisibility(string $path): string|false
    {
        $path = $this->fixPath($path);

        if ($this->driver->isFile($path)) {
            $chmod = intval($this->driver->chmod($path), 8);

            if ($chmod === $this->permissions['file'][self::VISIBILITY_PUBLIC]) {
                return self::VISIBILITY_PUBLIC;
            }

            if ($chmod === $this->permissions['file'][self::VISIBILITY_PRIVATE]) {
                return self::VISIBILITY_PRIVATE;
            }

            return false;
        }

        if ($this->driver->isDirectory($path)) {
            $chmod = intval($this->driver->chmod($path), 8);

            if ($chmod === $this->permissions['dir'][self::VISIBILITY_PUBLIC]) {
                return self::VISIBILITY_PUBLIC;
            }

            if ($chmod === $this->permissions['dir'][self::VISIBILITY_PRIVATE]) {
                return self::VISIBILITY_PRIVATE;
            }
        }

        return false;
    }

    public function setVisibility(string $path, string $visibility): bool
    {
        if (! in_array($visibility, [static::VISIBILITY_PUBLIC, static::VISIBILITY_PRIVATE])) {
            return false;
        }

        $path = $this->fixPath($path);

        $permission = null;

        if ($this->driver->isFile($path)) {
            $permission = $this->permissions['file'][$visibility];
        }
        elseif ($this->driver->isDirectory($path)) {
            $permission = $this->permissions['dir'][$visibility];
        }

        return is_null($permission) ? false : $this->driver->chmod($path, $permission);
    }

    public function visibility(string $path, string $visibility = null): string|bool
    {
        if (is_null($visibility)) {
            return $this->getVisibility($path);
        }

        return $this->setVisibility($path, $visibility);
    }

    public function allFiles(string $path = null): array
    {
        return $this->driver->allFiles($this->fixPath($path));
    }

    public function files(string $path = null): array
    {
        return $this->driver->files($this->fixPath($path));
    }

    public function get(string $path, array $options = []): string|false
    {
        return $this->driver->get($this->fixPath($path), $options);
    }

    public function sharedGet(string $path): string
    {
        return $this->driver->sharedGet($this->fixPath($path));
    }

    public function lines(string $path, bool $skipEmpty = false)
    {
        return $this->driver->lines($this->fixPath($path), $skipEmpty);
    }

    public function put(string $path, mixed $contents, array|string $options = []): int|false
    {
        if (is_string($options)) {
            $options = ['visibility' => $options];
        }

        if (! isset($options['lock'])) $options['lock'] = false;
        $lock = is_bool($options['lock']) && $options['lock'];

        $length = $this->driver->put($this->fixPath($path), $contents, $lock);

        if (isset($options['visibility']) &&
                in_array($options['visibility'], [static::VISIBILITY_PUBLIC, static::VISIBILITY_PRIVATE])) {
            $this->setVisibility($path, $options['visibility']);
        }

        return $length;
    }

    public function putFile(string $path, File|UploadedFile|string $file, array|string $options = []): string|false
    {
        if (is_string($file)) {
            $file = new File($file, true);
        }

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    public function putFileAs(string $path, File|UploadedFile|string $file, string $name, array|string $options = []): string|false
    {
        if (is_string($file)) {
            $file = new File($file, true);
        }

        $path = (empty($path) || $path === '/') ? '' : trim($path,'/').'/';
        $path.= trim($name, '/');

        $stream = fopen($file->getRealPath(), 'r');

        $result = $this->put($path, $stream, $options);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result !== false ? $path : false;
    }

    public function prepend(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $data.$separator.$this->get($path));
        }

        return $this->put($path, $data);
    }

    public function append(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $this->get($path).$separator.$data);
        }

        return $this->put($path, $data);
    }

    public function copy(string $from, string $to): bool
    {
       return $this->driver->copy($this->fixPath($from), $this->fixPath($to));
    }

    public function move(string $from, string $to): bool
    {
        return $this->driver->move($this->fixPath($from), $this->fixPath($to));
    }

    public function delete(array|string $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $key => $path) {
            $paths[$key] = $this->fixPath($path);
        }

        return $this->driver->delete($paths);
    }

    public function lastModified(string $path): int|false
    {
        return $this->driver->lastModified($this->fixPath($path));
    }

    public function size(string $path): int|false
    {
        return $this->driver->size($this->fixPath($path));
    }

    public function path(string $path): string
    {
        return $this->fixPath($path);
    }

    public function mimeType(string $path): string|false
    {
        return $this->driver->mimeType($this->fixPath($path));
    }

    public function allDirectories(string $path = null): array
    {
        return $this->driver->allDirectories($this->fixPath($path));
    }

    public function directories(string $path = null, bool $recursive = false): array
    {
        return $this->driver->directories($this->fixPath($path));
    }

    public function ensureDirectoryExists(string $path, bool $recursive = true): void
    {
        $this->driver->ensureDirectoryExists($this->fixPath($path), $this->permissions['dir']['public'], $recursive);
    }

    public function makeDirectory(string $path): bool
    {
        return $this->driver->makeDirectory($this->fixPath($path), $this->permissions['dir']['public'], true);
    }

    public function moveDirectory(string $from, string $to): bool
    {
        return $this->driver->moveDirectory($this->fixPath($from), $this->fixPath($to), true);
    }

    public function copyDirectory(string $from, string $to): bool
    {
        return $this->driver->copyDirectory($this->fixPath($from), $this->fixPath($to));
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->driver->deleteDirectory($this->fixPath($path));
    }

    public function cleanDirectory(string $path = null): bool
    {
        return $this->driver->cleanDirectory($this->fixPath($path), true);
    }

    public function temporaryUrl(string $path, int $expiration, array $options = []): string|false
    {
        return false;
    }

    public function url(string $path): string|false
    {
        return $this->config['url'].'/'.$path;
    }

    public function download(): ?StreamedResponse
    {
        return null;
    }
}