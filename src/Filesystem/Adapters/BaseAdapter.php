<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Adapters;

use BadMethodCallException;
use Generator;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Contracts\Filesystem\IFilesystemDriver;
use Imhotep\Filesystem\Concerns\DirectoryOperations;
use Imhotep\Filesystem\Concerns\ExistenceChecks;
use Imhotep\Filesystem\Concerns\FileHashing;
use Imhotep\Http\UploadedFile;
use Imhotep\Support\File;
use Imhotep\Support\MimeTypes;
use Throwable;

class BaseAdapter implements IFilesystem
{
    use ExistenceChecks, DirectoryOperations, FileHashing;

    protected IFilesystemDriver $driver;

    protected bool $throwed = false;

    protected string $root = '';

    public function __construct(IFilesystemDriver $driver, string $root = '/')
    {
        $this->driver = $driver;

        $this->root = '/'.trim($root, '/');
    }

    protected function resolvePath(array|string $path): array|string
    {
        $resolve = fn($path) => $this->root.'/'.ltrim($path, '/');

        if (is_array($path)) {
            return array_map($resolve, $path);
        }

        return $resolve($path);
    }

    protected function handleException(Throwable $e): false
    {
        if ($this->throwed) {
            throw $e;
        }

        return false;
    }

    protected function methodNotSupported(string $method): void
    {
        throw new BadMethodCallException("Method [{$method}] not supported in " . static::class);
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists($this->driver, $method)) {
            return $this->driver->{$method}(...$parameters);
        }

        $this->methodNotSupported($method);
    }

    public function driver(): IFilesystemDriver
    {
        return $this->driver;
    }


    public function exists(string $path): bool
    {
        return $this->driver->exists($this->resolvePath($path));
    }

    public function isDirectory(string $path): bool
    {
        return $this->driver->isDirectory($this->resolvePath($path));
    }

    public function isFile(string $path): bool
    {
        return $this->driver->isFile($this->resolvePath($path));
    }

    public function type(string $path): string|false
    {
        return $this->driver->type($this->resolvePath($path));
    }



    public function list(string $path = '/', bool $hidden = false): array|false
    {
        return $this->driver->list($this->resolvePath($path), $hidden);
    }

    public function directories(string $path = '/', bool $recursive = false): array
    {
        return $this->driver->directories($this->resolvePath($path), $recursive);
    }

    public function files(string $path = '/', bool $recursive = false, bool $hidden = false): array
    {
        return $this->driver->files($this->resolvePath($path), $recursive, $hidden);
    }

    public function allFiles(?string $path = null, bool $hidden = false): array
    {
        return $this->files($path, true, $hidden);
    }


    public function makeDirectory(string $path, bool $recursive = true): bool
    {
        return $this->driver->makeDirectory($this->resolvePath($path), $recursive);
    }

    public function cleanDirectory(string $path): bool
    {
        return $this->driver->cleanDirectory($this->resolvePath($path));
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->driver->deleteDirectory($this->resolvePath($path));
    }


    public function get(string $path, array $options = []): string|false
    {
        if ($this->isFile($path)) {
            return $this->driver->get($this->resolvePath($path), $options);
        }

        return $this->handleException(new FileNotFoundException($path));
    }

    public function put(string $path, mixed $contents, bool|string|array $options = []): int|false
    {
        if (is_string($options)) {
            $options = ['visibility' => $options];
        }

        if (is_bool($options)) {
            $options = ['lock' => true];
        }

        $length = $this->driver->put($this->resolvePath($path), $contents, $options);

        if (isset($options['visibility']) &&
            in_array($options['visibility'], [static::VISIBILITY_PUBLIC, static::VISIBILITY_PRIVATE])) {
            $this->setVisibility($path, $options['visibility']);
        }

        return $length;
    }

    public function putFile(string $path, string|File|UploadedFile $file, bool|string|array $options = []): string|false
    {
        if (is_string($file)) {
            $file = new File($file, true);
        }

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    public function putFileAs(string $path, string|File|UploadedFile $file, string $name, bool|string|array $options = []): string|false
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

        return $result ? $path : false;
    }

    public function readStream(string $path): mixed
    {
        return $this->driver->readStream($this->resolvePath($path));
    }

    public function writeStream(string $path, mixed $resource): bool
    {
        return $this->driver->writeStream($this->resolvePath($path), $resource);
    }

    public function copy(string $from, string $to): bool
    {
        return $this->driver->copy($this->resolvePath($from), $this->resolvePath($to));
    }

    public function move(string $from, string $to): bool
    {
        return $this->driver->move($this->resolvePath($from), $this->resolvePath($to));
    }

    public function delete(array|string $paths): bool
    {
        return $this->driver->delete($this->resolvePath($paths));
    }


    /*
    public function prepend(string $path, string $content, string $separator = '', bool $lock = false): int|false
    {
        $oldContent = $this->get($path);

        if ($oldContent === false) {
            return $this->put($path, $content);
        }

        return $this->put($path, $content.$separator.$oldContent, $lock);
    }

    public function append(string $path, string $content, string $separator = '', bool $lock = false): int|false
    {
        $oldContent = $this->get($path);

        if ($oldContent === false || $oldContent === '') {
            return $this->put($path, $content);
        }

        return $this->put($path, $oldContent.$separator.$content, $lock);
    }

    public function replace(string $path, mixed $content): bool
    {
        if ($this->exists($path)) {
            $this->delete($path);
        }

        return $this->put($path, $content) !== false;
    }
    */


    public function size(string $path): int|false
    {
        return $this->driver->size($this->resolvePath($path));
    }

    public function lastModified(string $path): int|false
    {
        return $this->driver->lastModified($this->resolvePath($path));
    }

    public function mimeType(string $path): string|false
    {
        if ($mimeType = MimeTypes::getMimeType(pathinfo($path, PATHINFO_EXTENSION))) {
            return $mimeType;
        }

        return false;
    }



    public function path(string $path): string
    {
        return $this->resolvePath($path);
    }

    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public function dirname(string $path): string
    {
        return pathinfo($this->resolvePath($path), PATHINFO_DIRNAME);
    }

    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }



    /*
    public function directories(?string $path = null, bool $recursive = false): array
    {
        return $this->driver->directories($this->resolvePath($path), $recursive);
    }

    public function allDirectories(?string $path = null): array
    {
        return $this->directories($path, true);
    }

    public function ensureDirectoryExists(string $path, bool $recursive = true): bool
    {
        if (! $this->isDirectory($path)) {
            return $this->makeDirectory($path, $recursive);
        }

        return false;
    }

    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite) {
            if ($this->isDirectory($to) && ! $this->deleteDirectory($to)) {
                return false;
            }
        }
        elseif ($this->isDirectory($to)) {
            return false;
        }

        return $this->driver->move($this->resolvePath($from), $this->resolvePath($to)) === true;
    }

    public function copyDirectory(string $from, string $to): bool
    {
        if (($items = $this->list($from)) === false) {
            return false;
        }

        $this->ensureDirectoryExists($to);

        foreach ($items as $item) {
            $target = $to.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target)) {
                    return false;
                }
            }

            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    public function cleanDirectory(string $path): bool
    {
        return $this->deleteDirectory($path, true);
    }
    */

    /*
    public function visibility(string $path): string|false
    {
        // TODO: Implement getVisibility() method.
    }

    public function setVisibility(string $path, string $visibility): bool
    {
        // TODO: Implement setVisibility() method.
    }

    public function temporaryUrl(string $path, int $expiration, array $options = []): string|false
    {
        // TODO: Implement temporaryUrl() method.
    }

    public function url(string $path): string|false
    {
        // TODO: Implement url() method.
    }

    public function download(): mixed
    {
        // TODO: Implement download() method.
    }
    */
}