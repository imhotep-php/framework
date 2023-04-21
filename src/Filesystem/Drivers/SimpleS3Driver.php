<?php

declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use Exception;
use Imhotep\Contracts\Filesystem\Driver;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\SimpleS3\S3Client;

/**
 * @throws Exception
 */
class SimpleS3Driver implements Driver
{
    protected S3Client $client;

    protected array $config;

    protected string $bucket = '';

    public function __construct(array $config)
    {
        $this->config = $config;

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

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }



    /**
     * @throws Exception
     */
    public function files(string $directory, bool $hidden = false): array
    {
        $files = [];

        try {
            $result = $this->client->listObjectsV2($this->bucket, [$directory]);

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
            throw new FileNotFoundException();
        }

        $result = $this->client->copyObject($this->bucket, $from, $to, $options);

        return ($result->statusCode < 300);
    }

    public function move(string $from, string $to, array $options = []): bool
    {
        if ($this->missing($from)) {
            throw new FileNotFoundException();
        }

        if (! $this->copy($from, $to)) {
            return false;
        }

        $result = $this->client->deleteObject($this->bucket, $from, $options);

        return ($result->statusCode < 300);
    }

    public function get(string $path, array $options = []): string|bool
    {
        $result = $this->client->getObject($this->bucket, $path, $options);

        if ($result->statusCode < 300) {
            return $result->getData();
        }

        return false;
    }

    public function put(string $path, string $content, array $options = []): bool
    {
        $result = $this->client->putObject($this->bucket, $path, $content, $options);

        return ($result->statusCode < 300);
    }

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

    public function size(string $path): int|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            return $result->getMeta('content-length');
        }

        return false;
    }

    public function contentType(string $path): string|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            return $result->getMeta('content-type');
        }

        return false;
    }

    public function cacheControl(string $path): string|false
    {
        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            return $result->getMeta('cache-control');
        }

        return false;
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

    public function hash(string $path, string $algo = 'md5'): string|false
    {
        if ($algo !== 'md5') {
            throw new Exception('This driver supported only [md5] file hash.');
        }

        $result = $this->client->headObject($this->bucket, $path);
        if ($result->statusCode < 300) {
            return $result->getMeta('etag');
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



    /**
     * @param Exception $e
     * @throws Exception
     */
    protected function handleException(Exception $e): void
    {
        if ($this->config['throw']) {
            throw $e;
        }
    }

    protected function methodNotSupported($method): void
    {
        throw new Exception("Method [{$method}] not supported in cloud disk.");
    }

    public function __call(string $method, array $properties)
    {
        if (method_exists($this->client, $method)) {
            return $this->client->$method(...$properties);
        }

        $this->methodNotSupported($method);
    }


}