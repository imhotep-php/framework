<?php

declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use Exception;
use FilesystemIterator;
use Generator;
use Imhotep\Contracts\Filesystem\Driver;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Contracts\Filesystem\FilesystemException;
use SplFileObject;
use Throwable;

class LocalDriver implements Driver
{
    protected bool $throwed = true;

    public function __construct(array $config = [])
    {
        if (isset($config['throw']) && is_bool($config['throw'])) {
            $this->throwed = $config['throw'];
        }
    }


    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    public function files(string $path, bool $hidden = false): array
    {
        $result = [];

        $flags = (!$hidden) ? 0 : FilesystemIterator::SKIP_DOTS;

        $items = new FilesystemIterator($path, $flags);

        foreach ($items as $item) {
            if ($item->isFile()) $result[] = $item;
        }

        usort($result, function ($a, $b) {
            return strnatcmp($a->getBasename(), $b->getBasename());
        });

        return $result;
    }

    public function allFiles(string $path): array
    {
        $result = [];

        $items = iterator_to_array(new FilesystemIterator($path));

        usort($items, function ($a, $b) {
            if ($a->isFile() && $b->isDir()) {
                return 1;
            }
            if ($b->isFile() && $a->isDir()) {
                return -1;
            }

            return strnatcmp($a->getRealPath(), $b->getRealPath());
        });

        foreach ($items as $item) {
            if ($item->isFile()) {
                $result[] = $item;
            }

            if ($item->isDir()) {
                foreach ($this->allFiles($item->getPathname()) as $subItem) {
                    $result[] = $subItem;
                }
            }
        }

        return $result;
    }

    public function directories(string $path): array
    {
        $result = [];

        $items = new FilesystemIterator($path);

        foreach ($items as $item) {
            if ($item->isDir()) $result[] = $item->getPathname();
        }

        usort($result, function ($a, $b) {
            return strnatcmp($a, $b);
        });

        return $result;
    }

    public function allDirectories(string $path): array
    {
        $results = [];

        foreach ($this->directories($path) as $dir) {
            $results[] = $dir;

            foreach ($this->directories($dir) as $subDir) {
                $results[] = $subDir;
            }
        }

        return $results;
    }


    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    public function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    public function get(string $path, array $options = []): string|false
    {
        $lock = (isset($options['lock']) && $options['lock'] === true);

        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        return $this->handleException(
            new FileNotFoundException("File does not exist at path {$path}")
        );
    }

    public function sharedGet(string $path): string
    {
        $content = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $content = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $content;
    }

    public function lines(string $path, bool $skipEmpty = false): Generator
    {
        if (! $this->isFile($path)) {
            throw new FileNotFoundException("File does not exist at path {$path}");
        }

        $file = new SplFileObject($path);

        $file->setFlags(SplFileObject::DROP_NEW_LINE);

        while (! $file->eof()) {
            $line = $file->fgets();

            if ($skipEmpty && empty($line)) continue;

            yield $line;
        }
    }

    public function put(string $path, mixed $content, bool $lock = false): int|bool
    {
        return file_put_contents($path, $content, $lock ? LOCK_EX : 0);
    }

    public function putFile(string $path, string $source, bool $lock = false): int|bool
    {
        return $this->put($path, $this->get($source), $lock);
    }

    public function putFileAs(string $path, string $source, string $name, bool $lock = false): int|bool
    {
        return $this->putFile(rtrim($path, '/').$name, $source, $lock);
    }

    public function append(string $path, mixed $content, bool $lock = false): int|bool
    {
        return file_put_contents($path, $content, ($lock ? LOCK_EX|FILE_APPEND : FILE_APPEND) );
    }

    public function replace(string $path, mixed $content)
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    public function size(string $path): int|false
    {
        return filesize($path);
    }

    public function hash(string $path, string $algo = 'md5'): string|false
    {
        return hash_file($algo, $path);
    }

    public function hasSameHash(string $firstPath, string $secondPath): bool
    {
        $hash = @md5_file($firstPath);

        return $hash && $hash === @md5_file($secondPath);
    }

    public function lastModified(string $path): int|false
    {
        return filemtime($path);
    }

    public function type(string $path): string|false
    {
        return filetype($path);
    }

    public function mimeType(string $path): string|false
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    public function name(string $path): string|array
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function basename(string $path): string|array
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function dirname(string $path): string|array
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public function extension(string $path): string|array
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function chmod(string $path, int $mode = null): string|bool
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        clearstatcache(true, $path);

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    public function delete(string|array $paths, array $options = []): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (@unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (\ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    public function getRequire(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
               extract($__data, EXTR_SKIP);

               return require $__path;
            })();
        }

        return $this->handleException(new FilesystemException("File does not exist at path {$path}"));
    }


    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true)
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    public function copyDirectory(string $directory, string $destination, array $options = null): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }


    public function handleException(Throwable $e): bool
    {
        if ($this->throwed) {
            throw $e;
        }

        return false;
    }

    public function __call(string $method, array $properties)
    {
        throw new Exception("Method [{$method}] not supported in driver.");
    }
}