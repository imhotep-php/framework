<?php declare(strict_types=1);

namespace Imhotep\Filesystem;

use BadMethodCallException;
use FilesystemIterator;
use Generator;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Filesystem\Concerns\DirectoryOperations;
use Imhotep\Filesystem\Concerns\ExistenceChecks;
use Imhotep\Filesystem\Concerns\FileHashing;
use Imhotep\Filesystem\Concerns\PathHelpers;
use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\Support\Traits\DeprecatedGetters;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;

class Filesystem
{
    use PathHelpers, ExistenceChecks, DirectoryOperations, DeprecatedGetters;

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
            $result[] = $item;
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
                $result[] = $item->getPathname();
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
            if ($item->isFile()) $result[] = $item;
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

    public function put(string $path, mixed $content, bool $lock = false): int|bool
    {
        return file_put_contents($path, $content, $lock ? LOCK_EX : 0);
    }

    public function readStream(string $path): mixed
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        return $handle;
    }

    public function writeStream(string $path, mixed $resource, bool $lock = false): bool
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            return false;
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            return false;
        }

        try {
            if ($lock) {
                flock($handle, LOCK_EX);
            }

            rewind($resource);

            $result = stream_copy_to_stream($resource, $handle);

            if ($lock) {
                flock($handle, LOCK_UN);
            }

            return $result !== false;
        }
        finally {
            fclose($handle);
        }
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



    public function allFiles(string $path, bool $hidden = false): array
    {
        return $this->files($path, true, $hidden);
    }

    public function getWithLock(string $path): string|false
    {
        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $content = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);

                    return $content;
                }
            } finally {
                fclose($handle);
            }
        }

        return false;
    }

    public function putWithLock(string $path, mixed $content): int|false
    {
        return file_put_contents($path, $content, LOCK_EX);
    }

    public function json(string $path, int $depth = 512, int $flags = 0): mixed
    {
        return json_decode($this->get($path), true, $depth, $flags);
    }

    public function require(string $path, array $data = []): mixed
    {
        if (is_file($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require $__path;
            })();
        }

        throw new FileNotFoundException($path);
    }

    public function requireOnce(string $path, array $data = []): mixed
    {
        if (is_file($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require_once $__path;
            })();
        }

        throw new FileNotFoundException($path);
    }

    public function lines(string $path, bool $skipEmpty = false): Generator
    {
        if (! is_file($path)) {
            throw new FileNotFoundException($path);
        }

        $file = new SplFileObject($path);

        $file->setFlags(SplFileObject::DROP_NEW_LINE);

        while (! $file->eof()) {
            $line = $file->fgets();

            if ($skipEmpty && empty($line)) continue;

            yield $line;
        }
    }

    public function append(string $path, mixed $content, ?string $separator = null, bool $lock = false): int|false
    {
        if ($separator !== null && $this->exists($path) && $this->get($path) !== '') {
            $content = $separator.$content;
        }

        return file_put_contents($path, $content, ($lock ? LOCK_EX|FILE_APPEND : FILE_APPEND) );
    }

    public function prepend(string $path, mixed $content, ?string $separator = null, bool $lock = false): int|false
    {
        if ($this->exists($path)) {
            $current = $this->get($path);

            // Добавляем разделитель только если файл не пуст и разделитель указан
            if ($separator !== null && $current !== '') {
                $content .= $separator . $current;
            } else {
                // Если файл пуст или разделитель не указан
                $content .= $current;
            }
        }

        return $this->put($path, $content, $lock);
    }

    public function replace(string $path, mixed $content): bool
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        return rename($tempPath, $path);
    }

    public function hash(string $path, string $algo = 'md5'): string|false
    {
        return @hash_file($algo, $path);
    }

    public function hasSameHash(string $firstPath, string $secondPath): bool
    {
        $hash = $this->hash($firstPath);

        return $hash && $hash === $this->hash($secondPath);
    }

    public function mimeType(string $path): string|false
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}