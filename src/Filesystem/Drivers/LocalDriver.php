<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use FilesystemIterator;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Filesystem\FileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class LocalDriver extends BaseDriver
{
    public function exists(string $path): bool
    {
        return file_exists(rtrim($path, '/'));
    }

    public function isDirectory(string $path): bool
    {
        return is_dir(rtrim($path, '/'));
    }

    public function isFile(string $path): bool
    {
        return is_file(rtrim($path, '/'));
    }

    public function type(string $path): string|false
    {
        return filetype(rtrim($path, '/'));
    }


    public function list(string $path, bool $hidden = false): array|false
    {
        $result = [];

        $flags = FilesystemIterator::CURRENT_AS_FILEINFO;
        $flags |= $hidden ? 0 : FilesystemIterator::SKIP_DOTS;

        $items = new FilesystemIterator($path, $flags);

        foreach ($items as $item) {
            $result[] = $this->toFileInfo($item);
        }

        return $result;
    }

    public function directories(string $path, bool $recursive = false): array|false
    {
        $result = [];

        $iterator = $recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            )
            : new FilesystemIterator($path);

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $result[] = $this->toFileInfo($item);
            }
        }

        return $result;
    }

    public function files(string $path, bool $recursive = false, bool $hidden = false): array|false
    {
        $result = [];

        $flags = (!$hidden) ? 0 : FilesystemIterator::SKIP_DOTS;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, $flags),
                RecursiveIteratorIterator::SELF_FIRST
            )
            : new FilesystemIterator($path, $flags);

        foreach ($iterator as $item) {
            if ($item->isFile()) $result[] = $this->toFileInfo($item);
        }

        return $result;
    }


    public function makeDirectory(string $path, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return false;
        }

        return mkdir($path, 0755, $recursive);
    }

    public function cleanDirectory(string $path): bool
    {
        if (! $this->isDirectory($path)) {
            return false;
        }

        $items = new FilesystemIterator($path);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }
            else {
                $this->delete($item->getPathname());
            }
        }

        return true;
    }

    public function deleteDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        $this->cleanDirectory($path);

        return rmdir($path);
    }


    public function get(string $path): string|false
    {
        return file_get_contents($path);
    }

    public function put(string $path, mixed $content): int|bool
    {
        return file_put_contents($path, $content);
    }

    public function readStream(string $path): mixed
    {
        return fopen($path, 'r');
    }

    public function writeStream(string $path, mixed $resource): bool
    {
        if (! is_resource($resource)) {
            return false;
        }

        $stream = fopen($path, 'w');
        if ($stream === false) {
            return false;
        }

        $result = stream_copy_to_stream($resource, $stream);

        fclose($stream);

        return $result !== false;
    }

    public function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (Throwable $e) {
                $success = false;
            }
        }

        return $success;
    }


    public function size(string $path): int|false
    {
        return filesize($path);
    }

    public function lastModified(string $path): int|false
    {
        return filemtime($path);
    }

    public function permissions(string $path): string|false
    {
        clearstatcache(true, $path);

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    public function setPermissions(string $path, int $mode): bool
    {
        return chmod($path, $mode);
    }

    protected function toFileInfo(SplFileInfo $info): FileInfo
    {
        $size = $info->getSize();
        $lastModified = $info->getMTime();

        return new FileInfo(
            $info->getPathname(),
            $info->getType(),
            $size === false ? null : $size,
            $lastModified === false ? null : $lastModified,
        );
    }
}