<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Filesystem\FileInfo;
use Imhotep\SimpleS3\S3Client;
use Throwable;

class CloudDriver extends BaseDriver
{
    protected object $client;

    protected string $bucket = '';

    public function __construct(IConfigRepository $config, object $S3client)
    {
        parent::__construct($config->bool('throw', true));

        $this->bucket = $config->stringOrFail('bucket');

        $this->client = $S3client;
    }

    public function exists(string $path): bool
    {
        return $this->type($path) !== false;
    }

    public function isDirectory(string $path): bool
    {
        return $this->type($path) === 'dir';
    }

    public function isFile(string $path): bool
    {
        return $this->type($path) === 'file';
    }

    public function type(string $path): string|false
    {
        try {
            $name = basename($path);
            $path = trim(dirname($path), '/');
            if ($path === '.' || $path === '/') $path = '';
            elseif ($path !== '') $path.= '/';

            $result = $this->client->listObjects($this->bucket, ['prefix' => $path, 'delimiter' => '/']);

            $objects = $result->get('CommonPrefixes', []);
            foreach ($objects as $object) {
                if (basename($object['Prefix']) === $name) {
                    return 'dir';
                }
            }

            $objects = $result->get('Contents', []);
            foreach ($objects as $object) {
                if (basename($object['Key']) === $name) {
                    return 'file';
                }
            }
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }

        return false;
    }


    public function list(string $path, bool $hidden = false): array|false
    {
        $files = [];

        try {
            $path = trim($path, '/').'/';
            if ($path === '/') $path = '';

            $result = $this->client->listObjects($this->bucket, ['prefix' => $path, 'delimiter' => '/']);

            array_map(function ($object) use (&$files, $path) {
                $files[] = new FileInfo(rtrim($object['Prefix'], '/'), 'dir');
            }, $result->get('CommonPrefixes', []));

            array_map(function ($object) use (&$files, $path) {
                if (! str_ends_with($object['Key'], '/')) {
                    $files[] = new FileInfo($object['Key'], 'file', $object['Size'], strtotime($object['LastModified']));
                }
            }, $result->get('Contents', []));
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }

        return $files;
    }

    public function directories(string $path, bool $recursive = false): array|false
    {
        $files = [];

        try {
            $path = trim($path, '/').'/';

            $result = $this->client->listObjects($this->bucket, ['prefix' => $path, 'delimiter' => '/']);

            array_map(function ($object) use (&$files, $path) {
                $files[] = new FileInfo(rtrim($object['Prefix'], '/'), 'dir');
            }, $result->get('CommonPrefixes', []));
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }

        return $files;
    }

    public function files(string $path, bool $recursive = false, bool $hidden = false): array|false
    {
        $files = [];

        try {
            $path = trim($path, '/').'/';

            $result = $this->client->listObjects($this->bucket, ['prefix' => $path, 'delimiter' => '/']);

            array_map(function ($object) use (&$files, $path) {
                if (! str_ends_with($object['Key'], '/')) {
                    $files[] = new FileInfo($object['Key'], 'file', $object['Size'], strtotime($object['LastModified']));
                }
            }, $result->get('Contents', []));
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }

        return $files;
    }


    public function makeDirectory(string $path, bool $recursive = true): bool
    {
        $path = trim($path, '/').'/';

        try {
            $result = $this->client->putObject($this->bucket, $path, '');

            return $result->statusCode < 300;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function cleanDirectory(string $path): bool
    {
        $path = trim($path, '/').'/';

        try {
            do {
                $result = $this->client->listObjects($this->bucket, ['prefix' => $path]);

                $objects = array_map(
                    fn($object) => $object['Key'],
                    $result->get('Contents', [])
                );

                if (empty($objects)) {
                    return true;
                }

                $result = $this->client->deleteObjects($this->bucket, $objects, true);

                if ($result->statusCode >= 300) {
                    return false;
                }
            } while(true);
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function deleteDirectory(string $path): bool
    {
        $path = trim($path, '/').'/';

        $this->cleanDirectory($path);

        try {
            $result = $this->client->deleteObject($this->bucket, $path);

            return $result->statusCode < 300;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }


    public function get(string $path): string|false
    {
        try {
            $result = $this->client->getObject($this->bucket, $path);

            return $result->statusCode < 300 ? $result->getData() : false;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function put(string $path, mixed $content): int|bool
    {
        try {
            $result = $this->client->putObject($this->bucket, $path, $content);

            return ($result->statusCode < 300) ? strlen($content) : false;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function readStream(string $path): mixed
    {
        return false;
    }

    public function writeStream(string $path, mixed $resource): bool
    {
        return false;
    }

    public function copy(string $from, string $to): bool
    {
        try {
            $result = $this->client->copyObject($this->bucket, $from, $to);

            return $result->statusCode < 300;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function move(string $from, string $to): bool
    {
        if (! $this->copy($from, $to)) {
            return false;
        }

        try {
            $result = $this->client->deleteObject($this->bucket, $from);

            return $result->statusCode < 300;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(string|array $paths): bool
    {
        try {
            if (is_string($paths)) {
                $result = $this->client->deleteObject($this->bucket, $paths);
            }
            else {
                $result = $this->client->deleteObjects($this->bucket, $paths, true);
            }

            return $result->statusCode < 300;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }


    public function size(string $path): int|false
    {
        try {
            $result = $this->client->headObject($this->bucket, $path);

            if ($result->statusCode < 300) {
                if ($contentLength = $result->meta('Content-Length')) {
                    return (int)$contentLength;
                }
            }
        } catch (Throwable $e) {
            return $this->handleException($e);
        }

        return false;
    }

    public function lastModified(string $path): int|false
    {
        $path = ltrim($path, '/');

        try {
            $result = $this->client->headObject($this->bucket, $path);

            if ($result->statusCode < 300) {
                if ($lastModified = $result->getMeta('Last-Modified')) {
                    return strtotime($lastModified);
                }
            }

            // Header Last-Modified not exists. Find in listObjects:
            $name = basename($path);
            $path = trim(dirname($path), '/');
            if ($path === '.' || $path === '/') $path = '';
            elseif ($path !== '') $path.= '/';

            $result = $this->client->listObjects($this->bucket, ['prefix' => $path, 'delimiter' => '/']);
            foreach ($result['Contents'] as $object) {
                if (basename($object['Key']) === $name && isset($object['LastModified'])) {
                    return strtotime($object['LastModified']);
                }
            }
        } catch (Throwable $e) {
            return $this->handleException($e);
        }

        return false;
    }

    public function permissions(string $path): string|false
    {
        return false;
    }

    public function setPermissions(string $path, int $mode): bool
    {
        return false;
    }
}