<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Drivers;

use DateTime;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Filesystem\FileInfo;
use Imhotep\Support\Str;
use RuntimeException;
use Throwable;

class FtpDriver extends BaseDriver
{
    protected IConfigRepository $config;

    protected mixed $curlHandle = null;

    protected string $root = '';

    protected string $baseUrl = '';

    public function __construct(IConfigRepository $config)
    {
        parent::__construct($config->bool('throw', true));

        $this->config = $config;
        $this->root = $this->normalizePath($this->config->string('root', ''));

        if (!extension_loaded('curl')) {
            throw new RuntimeException('cURL extension is required for FtpDriver');
        }
    }

    public function __destruct()
    {
        if ($this->curlHandle !== null) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }

    protected function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    protected function buildFullPath(string $path): string
    {
        $path = $this->normalizePath($path);

        if (empty($this->root)) {
            return $path;
        }

        if (str_starts_with($path, $this->root)) {
            return $path;
        }

        return ltrim($this->root . '/' . $path, '/');
    }

    protected function getCurlHandle(): mixed
    {
        if ($this->curlHandle !== null) {
            return $this->curlHandle;
        }

        $this->curlHandle = curl_init();

        $host = $this->config->stringOrFail('host', 'FTP host [:key] is not configured.');
        $port = $this->config->int('port', 21);
        $username = $this->config->string('username', '');
        $password = $this->config->string('password', '');
        $timeout = $this->config->int('timeout', 30);
        $ssl = $this->config->bool('ssl', false);
        $passive = $this->config->bool('passive', true);

        $this->baseUrl = ($ssl ? 'ftps://' : 'ftp://') . $host . ':' . $port;

        $options = [
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FTP_USE_EPSV => $passive,
            CURLOPT_FTP_SKIP_PASV_IP => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FTP_CREATE_MISSING_DIRS => true, // Автоматическое создание директорий
        ];

        if (!empty($username) && !empty($password)) {
            $options[CURLOPT_USERPWD] = $username . ':' . $password;
        }

        curl_setopt_array($this->curlHandle, $options);

        return $this->curlHandle;
    }

    protected function execute(string $path, array $options = []): mixed
    {
        $ch = $this->getCurlHandle();

        $fullPath = $this->buildFullPath($path);
        $url = $this->baseUrl . '/' . ltrim($fullPath, '/');

        // Сбрасываем опции к значениям по умолчанию
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, null);
        curl_setopt($ch, CURLOPT_FTPLISTONLY, false);
        curl_setopt($ch, CURLOPT_UPLOAD, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_QUOTE, []);
        curl_setopt($ch, CURLOPT_READDATA, null);
        curl_setopt($ch, CURLOPT_INFILESIZE, 0);
        curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        foreach ($options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $result = curl_exec($ch);

        if ($result === false && curl_errno($ch) > 0) {
            $message = curl_error($ch);

            if (str_contains($message, 'The file does not exist')) {
                throw new FileNotFoundException($path);
            }

            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }

        return $result;
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
        $path = '/' . ltrim($path, '/');

        $name = basename($path);
        $path = dirname($path) . '/';

        try {
            $result = $this->execute($path, [
                CURLOPT_CUSTOMREQUEST => 'LIST',
            ]);

            foreach (Str::lines($result) as $line) {
                $line = trim($line);

                if (preg_match('/\s+'.preg_quote($name).'$/', $line)) {
                    return $line[0] === 'd' ? 'dir' : 'file';
                }
            }
        }
        catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Server denied')) {
                return false;
            }
            if (str_contains($e->getMessage(), 'not exist')) {
                return false;
            }

            return $this->handleException($e);
        }

        return false;
    }


    public function list(string $path, bool $hidden = false): array|false
    {
        $path = $this->normalizePath($path);
        $path = rtrim($path, '/') . '/';

        try {
            $result = $this->execute($path, [
                CURLOPT_CUSTOMREQUEST => 'LIST'.($hidden ? ' -al' : ''),
            ]);

            if ($result === false) {
                return false;
            }

            $lines = explode("\n", trim($result));
            $items = [];

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                if (preg_match('/^([\-ld])([rwxst\-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w+\s+\d+\s+[\d:]+)\s+(.+)$/', $line, $matches)) {
                    $name = $matches[8];

                    if ($name === '.' || $name === '..') {
                        continue;
                    }

                    if (!$hidden && str_starts_with($name, '.')) {
                        continue;
                    }

                    $type = $matches[1] === 'd' ? 'dir' : 'file';
                    $fullPath = $path ? $path.$name : $name;

                    $items[] = new FileInfo($fullPath, $type, (int)$matches[6], strtotime($matches[7]));

                    /*
                    path: $fullPath,
                    basename: $name,
                    type: $type,
                    size: (int)$matches[6],
                    lastModified: $this->parseDate($matches[7]),
                    //permissions: $matches[2],
                    //owner: $matches[4],
                    //group: $matches[5],
                    links: (int)$matches[3]
                    */
                }
            }

            return $items;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function directories(string $path, bool $recursive = false): array|false
    {
        if (($items = $this->list($path, true)) === false) {
            return false;
        }

        $directories = [];

        foreach ($items as $item) {
            if ($item->isDir()) {
                $directories[] = $item;

                if ($recursive) {
                    $directories = array_merge(
                        $directories,
                        $this->directories($item->getPathname(), true)
                    );
                }
            }
        }

        return $directories;
    }

    public function files(string $path, bool $recursive = false, bool $hidden = false): array|false
    {
        if (($items = $this->list($path, $hidden)) === false) {
            return false;
        }

        $files = [];

        foreach ($items as $item) {
            if ($item->isFile()) {
                $files[] = $item;
            }
            elseif ($recursive && $item->isDir()) {
                $files = array_merge(
                    $files,
                    $this->files($item->getPathname(), true, $hidden)
                );
            }
        }

        return $files;
    }


    public function makeDirectory(string $path, bool $recursive = true): bool
    {
        try {
            $path = rtrim($path, '/').'/';

            $result = $this->execute($path, [
                CURLOPT_FTP_CREATE_MISSING_DIRS => $recursive,
                //CURLOPT_QUOTE => ['MKD ' . $path],
                CURLOPT_NOBODY => true,
                //CURLOPT_VERBOSE => true
            ]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function cleanDirectory(string $path): bool
    {
        if (($files = $this->list($path, true)) === false) {
            return false;
        }

        foreach ($files as $file) {
            if ($file->isFile()) {
                $this->delete($file->getPathname());
            }
            elseif ($file->isDir()) {
                $this->deleteDirectory($file->getPathname());
            }
        }

        return true;
    }

    public function deleteDirectory(string $path): bool
    {
        $this->cleanDirectory($path);

        try {
            $path = trim($path, '/');

            $result = $this->execute('/', [
                CURLOPT_QUOTE => ['RMD ' . $path],
                CURLOPT_NOBODY => true
            ]);

            return true;
        } catch (Throwable $e) {
            dump($e->getMessage());
            return false;
        }
    }


    public function get(string $path): string|false
    {
        try {
            return $this->execute($path, [
                CURLOPT_RETURNTRANSFER => true,
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function put(string $path, mixed $content): int|bool
    {
        try {
            $temp = tmpfile();
            if ($temp === false) {
                throw new RuntimeException('Could not create temporary file');
            }

            if (is_resource($content)) {
                stream_copy_to_stream($content, $temp);
            } else {
                fwrite($temp, (string)$content);
            }

            fseek($temp, 0);
            $size = fstat($temp)['size'];

            $result = $this->execute($path, [
                CURLOPT_UPLOAD => true,
                CURLOPT_READDATA => $temp,
                CURLOPT_INFILESIZE => $size,
            ]);

            fclose($temp);

            return $result !== false ? $size : false;

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function readStream(string $path, array $options = []): mixed
    {
        try {
            $tempStream = fopen('php://temp', 'r+');

            $result = $this->execute($path, [
                CURLOPT_FILE => $tempStream,
            ]);

            rewind($tempStream);

            return $tempStream;
        }
        catch (Throwable $e) {
            fclose($tempStream);

            return $this->handleException($e);
        }
    }

    public function writeStream(string $path, mixed $resource, array $options = []): bool
    {
        // TODO: Implement writeStream() method.
    }

    public function copy(string $from, string $to): bool
    {
        $content = $this->get($from);

        if ($content === false) {
            return false;
        }

        return $this->put($to, $content) !== false;
    }

    public function move(string $from, string $to): bool
    {
        try {
            $fromFull = $this->buildFullPath($from);
            $toFull = $this->buildFullPath($to);

            $result = $this->execute('/', [
                CURLOPT_QUOTE => [
                    'RNFR ' . $fromFull,
                    'RNTO ' . $toFull
                ],
            ]);

            return $result !== false;

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(array|string $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $success = true;

        foreach ($paths as $path) {
            try {
                $fullPath = $this->buildFullPath($path);

                $result = $this->execute('/', [
                    CURLOPT_QUOTE => ['DELE ' . $fullPath],
                ]);

                if ($result === false) {
                    $result = $this->execute('/', [
                        CURLOPT_QUOTE => ['RMD ' . $fullPath],
                    ]);

                    if ($result === false) {
                        $success = false;
                    }
                }

            } catch (Throwable $e) {
                $success = false;
            }
        }

        return $success;
    }


    public function size(string $path): int|false
    {
        try {
            $result = $this->execute($path, [
                CURLOPT_NOBODY => true,
                CURLOPT_HEADER => true,
            ]);

            if ($result === false) {
                return false;
            }

            $size = curl_getinfo($this->curlHandle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            return $size >= 0 ? (int)$size : false;
        }
        catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function lastModified(string $path): int|false
    {
        try {
            $result = $this->execute($path, [
                CURLOPT_FILETIME => true,
                CURLOPT_NOBODY => true,
                CURLOPT_HEADER => true,
            ]);

            if ($result === false) {
                return false;
            }

            $filetime = curl_getinfo($this->curlHandle, CURLINFO_FILETIME);

            return $filetime > 0 ? $filetime : false;

        } catch (Throwable $e) {
            return false;
        }
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
