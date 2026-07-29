<?php declare(strict_types=1);

namespace Imhotep\Contracts\Filesystem;

interface IFilesystemDriver
{
    // Проверка
    public function exists(string $path): bool;
    public function isDirectory(string $path): bool;
    public function isFile(string $path): bool;
    public function type(string $path): string|false;

    // Листинг
    public function list(string $path, bool $hidden = false): array|false;
    public function directories(string $path, bool $recursive = false): array|false;
    public function files(string $path, bool $recursive = false, bool $hidden = false): array|false;

    // Операции с директориями
    public function makeDirectory(string $path, bool $recursive = true): bool;
    public function cleanDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;

    // Операции с файлами
    public function get(string $path): string|false;
    public function put(string $path, mixed $content): int|bool;
    public function readStream(string $path): mixed;
    public function writeStream(string $path, mixed $resource): bool;
    public function copy(string $from, string $to): bool;
    public function move(string $from, string $to): bool;
    public function delete(string|array $paths): bool;

    // Метаданные
    public function size(string $path): int|false;
    public function lastModified(string $path): int|false;
    public function permissions(string $path): string|false;
    public function setPermissions(string $path, int $mode): bool;
}