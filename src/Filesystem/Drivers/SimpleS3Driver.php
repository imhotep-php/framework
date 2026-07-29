<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use Exception;
use Generator;
use Imhotep\Contracts\Filesystem\IFilesystemDriver;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\SimpleS3\S3Client;
use Throwable;

class SimpleS3Driver extends BaseDriver
{
    protected S3Client $client;

    protected array $config;

    protected string $bucket = '';

    public function __construct(array $config)
    {
        parent::__construct($config['throw'] ?? true);

        $this->bucket = $config['bucket'];

        $this->client = new S3Client(
            $config['access_key'],
            $config['secret_key'],
            $config['endpoint']
        );
    }


    public function exists(string $path): bool
    {
        $info = $this->client->headObject($this->bucket, $path);

        return ($info->statusCode < 300);
    }


    public function files(string $path, bool $recursive = false, bool $hidden = false): array
    {
        $files = [];

        try {
            $result = $this->client->listObjectsV2($this->bucket, [$path]);

            array_map(function ($object) use (&$files) {
                $files[] = $object['Key'];
            }, $result->get('Contents'));
        }
        catch (Exception $e) {
            $this->handleException($e);
        }

        return $files;
    }



    public function isFile(string $path): bool
    {
        return $this->exists($path);
    }

    public function copy(string $from, string $to, array $options = []): bool
    {
        if ($this->missing($from)) {
            throw new FileNotFoundException($from);
        }

        $result = $this->client->copyObject($this->bucket, $from, $to, $options);

        return ($result->statusCode < 300);
    }

    public function move(string $from, string $to, array $options = []): bool
    {
        if ($this->missing($from)) {
            throw new FileNotFoundException($from);
        }

        if (! $this->copy($from, $to)) {
            return false;
        }

        $result = $this->client->deleteObject($this->bucket, $from, $options);

        return ($result->statusCode < 300);
    }

    public function get(string $path, array $options = []): string|false
    {
        $result = $this->client->getObject($this->bucket, $path, $options);

        if ($result->statusCode < 300) {
            return $result->getData();
        }

        return false;
    }

    public function put(string $path, mixed $content, array|bool $options = []): bool
    {
        $result = $this->client->putObject($this->bucket, $path, $content, $options);

        return ($result->statusCode < 300);
    }

    /*
    public function putFile(string $path, string $source, array $options = []): bool
    {
        if (is_file($source)) {
            return $this->put($path, file_get_contents($source), $options);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    public function putFileAs(string $path, string $source, string $name, array $options = []): bool
    {
        return $this->putFile(rtrim($path, '/').$name, $source, $options);
    }
    */

    public function size(string $path): int|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            if ($contentLength = $result->getMeta('content-length')) {
                return (int)$contentLength;
            }
        }

        return false;
    }

    public function contentType(string $path): string|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            if ($contentType = $result->getMeta('content-type')) {
                return $contentType;
            }
        }

        return false;
    }

    public function cacheControl(string $path): string|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            if ($cacheControl = $result->getMeta('cache-control')) {
                return $cacheControl;
            }
        }

        return false;
    }

    public function lastModified(string $path): int|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            $lastModified = $result->getMeta('last-modified');
            if ($lastModified) {
                return strtotime($lastModified);
            }
        }

        return false;
    }

    public function hash(string $path, string $algo = 'md5'): string|false
    {
        if ($algo !== 'md5') {
            throw new Exception('This driver supported only [md5] file hash.');
        }

        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            if ($etag = $result->getMeta('etag')) {
                return $etag;
            }
        }

        return false;
    }

    public function delete(string|array $paths, array $options = []): bool
    {
        if (is_string($paths) ) {
            $result = $this->client->deleteObject($this->bucket, $paths, $options);
        }
        else {
            $result = $this->client->deleteObjects($this->bucket, $paths, true, $options);
        }

        return ($result->statusCode < 300);
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists($this->client, $method)) {
            return $this->client->$method(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    public function append(string $path, mixed $content, bool $lock = false): int|bool
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function lines(string $path, bool $skipEmpty = false): Generator
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function mimeType(string $path): string|false
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function isDirectory(string $path): bool
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function type(string $path): string|false
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function directories(string $path, bool $recursive = false): array
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        $this->methodNotSupported(__FUNCTION__);
    }

    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        $this->methodNotSupported(__FUNCTION__);
    }
}