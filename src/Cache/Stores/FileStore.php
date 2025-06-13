<?php declare(strict_types=1);

namespace Imhotep\Cache\Stores;

use FilesystemIterator;
use Imhotep\Contracts\Cache\ICacheStore;

class FileStore implements ICacheStore
{
    protected array $config;

    protected string $directory;

    protected int $dirPermission = 0755;

    protected int $filePermission = 0664;

    public function __construct(string $path, ?int $filePermission = null, ?int $dirPermission = null)
    {
        $this->directory = rtrim($path, '/');

        if (! is_null($filePermission)) {
            $this->filePermission = $filePermission;
        }

        if (! is_null($dirPermission)) {
            $this->dirPermission = $dirPermission;
        }
    }

    public function get(string $key): mixed
    {
        return $this->getPayload($key)['value'];
    }

    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function set(string $key, float|array|bool|int|string $value, int $ttl): bool
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

    public function setMany(array $values, int $ttl): bool
    {
        $state = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $state = false;
            }
        }

        return $state;
    }

    public function increment(string $key, int $value = 1, int $ttl = 0): int|bool
    {
        $raw = $this->getPayload($key);

        $raw['value'] = is_null($raw['value']) ? $value : intval($raw['value']) + $value;

        $this->set($key, $raw['value'], $raw['ttl'] ?? $ttl);

        return $raw['value'];
    }

    public function decrement(string $key, int $value = 1, int $ttl = 0): int|bool
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

    protected function resolveExpireAt(int $ttl): string
    {
        if ($ttl === 0) return '0000000000';

        $time = time() + abs($ttl);

        return ($time > 9999999999) ? '9999999999' : (string)$time;
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getPayload(string $key): array
    {
        if (! file_exists($path = $this->path($key))) {
            return $this->emptyPayload();
        }

        if( !($content = file_get_contents($path)) ) {
            return $this->emptyPayload();
        }

        $expireAt = intval(substr($content, 0, 10));
        $ttl = ($expireAt > 0) ? $expireAt - time() : 0;

        if ($ttl < 0) {
            $this->delete($key);

            return $this->emptyPayload();
        }

        try {
            $value = unserialize(substr($content, 10));
        }
        catch (\Throwable $e) {
            $this->delete($key);

            return $this->emptyPayload();
        }

        return compact('value', 'ttl');
    }

    /**
     * @return array
     */
    protected function emptyPayload(): array
    {
        return ['value' => null, 'ttl' => null];
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
}