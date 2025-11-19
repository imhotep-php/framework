<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use FilesystemIterator;
use Imhotep\Cache\Locks\StoreLock;
use Imhotep\Cache\Locks\Lock;
use Imhotep\Contracts\Cache\ICacheStore;
use Throwable;

class FileStore implements ICacheStore
{
    protected array $config;

    protected string $directory;

    protected ?string $lockDirectory = null;

    protected int $filePermission = 0664;

    protected int $dirPermission = 0755;

    public function __construct(string $path, ?string $lockPath = null, ?int $filePermission = null, ?int $dirPermission = null)
    {
        $this->directory = rtrim($path, '/');

        if ($lockPath) {
            $this->lockDirectory = rtrim($lockPath, '/');
        }

        if (! is_null($filePermission)) {
            $this->filePermission = $filePermission;
        }

        if (! is_null($dirPermission)) {
            $this->dirPermission = $dirPermission;
        }
    }

    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    public function get(string $key): mixed
    {
        return $this->getPayload($key, 'value');
    }

    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (! $this->has($key)) {
            return $this->set($key, $value, $ttl);
        }

        return false;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $path = $this->path($key);
        $directory = dirname($path);
        $content = $this->resolveExpireAt($ttl).serialize($value);

        if (! file_exists($directory)) {
            mkdir($directory, $this->dirPermission, true);
        }

        if (file_put_contents($this->path($key), $content, LOCK_EX) ) {
            chmod($path, $this->filePermission);

            return true;
        }

        return false;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        $curValue = $this->getPayload($key, 'value');

        if (is_null($curValue)) {
            $newValue = $value;
        }
        elseif (is_int($curValue) || $curValue === '0' || filter_var($curValue, FILTER_VALIDATE_INT)) {
            $newValue = intval($curValue) + $value;
        }
        else {
            return false;
        }

        if ($newValue < 0) {
            $newValue = 0;
        }

        if (is_null($ttl) && ! is_null($curValue)) {
            if (($expire = $this->getPayload($key, 'expire')) > 0) {
                $ttl = $expire - time();
            }
        }

        $this->set($key, $newValue, $ttl);

        return $newValue;
    }

    public function decrement(string $key, int $value = 1, ?int $ttl = null): int|bool
    {
        return $this->increment($key, $value * -1, $ttl);
    }

    public function delete(string $key): bool
    {
        if (file_exists($path = $this->path($key))) {
            return @unlink($path);
        }

        return true;
    }

    public function flush(): bool
    {
        if (! is_dir($this->directory)) return true;

        $this->deleteDirectory($this->directory, true);

        return $this->isEmptyDirectory($this->directory);
    }


    protected function resolveExpireAt(?int $ttl): string
    {
        if (is_null($ttl)) {
            return '0000000000';
        }

        if ($ttl <= 0) {
            return (string)(time() - 1);
        }

        return (string)(time() + $ttl);
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getPayload(string $key, ?string $param = null): mixed
    {
        if (! file_exists($path = $this->path($key))) {
            return $this->emptyPayload($param);
        }

        if(! ($content = file_get_contents($path)) ) {
            return $this->emptyPayload($param);
        }

        $expire = intval(substr($content, 0, 10));
        $ttl = ($expire > 0) ? $expire - time() : 0;

        if ($ttl < 0) {
            $this->delete($key);

            return $this->emptyPayload($param);
        }

        try {
            $value = unserialize(substr($content, 10));
        }
        catch (Throwable) {
            $this->delete($key);

            return $this->emptyPayload($param);
        }

        $result = compact('value', 'expire', 'ttl');

        return ($param) ? $result[$param] : $result;
    }

    /**
     * @return array
     */
    protected function emptyPayload(?string $param): ?array
    {
        return $param ? null : ['value' => null, 'expire' => null, 'ttl' => null];
    }

    protected function path(string $key): string
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.substr($hash, 4);
    }

    protected function deleteDirectory(string $directory, bool $preserve = false): void
    {
        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }
            else {
                @unlink($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }
    }

    protected function isEmptyDirectory(string $directory): bool
    {
        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir() || $item->isFile()) {
                return false;
            }
        }

        return true;
    }

    public function lock(string $name, int $timeout = 0, string $owner = ''): Lock
    {
        $lockStore = new static(
            $this->lockDirectory ?: $this->directory, null,
            $this->filePermission, $this->dirPermission
        );

        return new StoreLock($lockStore, $name, $timeout, $owner);
    }

    public function restoreLock(string $name, string $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }
}