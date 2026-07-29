<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Adapters;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Filesystem\IFilesystemDriver;
use InvalidArgumentException;

class LocalAdapter extends BaseAdapter
{
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

    public function __construct(IFilesystemDriver $driver, IConfigRepository $config)
    {
        parent::__construct($driver);

        $this->root = $config->string('root', '/');
        $this->root = rtrim($this->root, '/').'/';

        if (! empty($this->root) && ! is_dir($this->root)) {
            throw new InvalidArgumentException('Property [root] incorrect in config. Root folder ['.$this->root.'] not exists.');
        }

        if ($config->has('permissions.file')) {
            $this->permissions['file']['public'] = $this->fixPermission(
                $config->int('permissions.file.public'),
                $this->permissions['file']['public']
            );

            $this->permissions['file']['private'] = $this->fixPermission(
                $config->int('permissions.file.private'),
                $this->permissions['file']['private']
            );
        }

        if ($config->has('permissions.dir')) {
            $this->permissions['dir']['public'] = $this->fixPermission(
                $config->int('permissions.dir.public'),
                $this->permissions['dir']['public']
            );

            $this->permissions['dir']['private'] = $this->fixPermission(
                $config->int('permissions.dir.private'),
                $this->permissions['dir']['private']
            );
        }
    }

    protected function fixPermission(string|int|null $chmod, string $default): string
    {
        if (empty($chmod)) {
            return $default;
        }

        $chmod = (int)$chmod;

        if ($chmod > 600 && $chmod < 800) {
            return '0'.$chmod;
        }

        return $default;
    }

    protected function resolvePath(array|string $path): array|string
    {
        $resolve = fn($path) => $this->root.ltrim($path, '/');

        if (is_array($path)) {
            return array_map($resolve, $path);
        }

        return $resolve($path);
    }

    public function mimeType(string $path): string|false
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->resolvePath($path));
    }

    public function visibility(string $path): string|false
    {
        $path = $this->resolvePath($path);

        if ($this->driver->isFile($path)) {
            $chmod = intval($this->driver->permissions($path), 8);

            if ($chmod === $this->permissions['file'][self::VISIBILITY_PUBLIC]) {
                return self::VISIBILITY_PUBLIC;
            }

            if ($chmod === $this->permissions['file'][self::VISIBILITY_PRIVATE]) {
                return self::VISIBILITY_PRIVATE;
            }

            return false;
        }

        if ($this->driver->isDirectory($path)) {
            $chmod = intval($this->driver->permissions($path), 8);

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

        $path = $this->resolvePath($path);

        $permission = null;

        if ($this->driver->isFile($path)) {
            $permission = $this->permissions['file'][$visibility];
        }
        elseif ($this->driver->isDirectory($path)) {
            $permission = $this->permissions['dir'][$visibility];
        }

        return is_null($permission) ? false : $this->driver->setPermissions($path, $permission);
    }
}