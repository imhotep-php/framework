<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Filesystem;

use Imhotep\Filesystem\StreamedResponse;
use Imhotep\Http\UploadedFile;
use Imhotep\Support\File;

interface Filesystem
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    public function exists(string $path): bool;
    public function missing(string $path): bool;
    public function getVisibility(string $path): string|false;
    public function setVisibility(string $path, string $visibility): bool;
    public function visibility(string $path, string $visibility = null): string|bool;

    public function allFiles(string $path = null): array;
    public function files(string $path = null): array;
    public function get(string $path, array $options = []): string;
    public function put(string $path, string $contents, array|string $options = []): int|false;
    public function putFile(string $path, string|File|UploadedFile $file, array|string $options = []): string|false;
    public function putFileAs(string $path, string|File|UploadedFile $file, string $name, array|string $options = []): string|false;
    public function prepend(string $path, string $data, string $separator = ''): bool;
    public function append(string $path, string $data, string $separator = ''): bool;
    public function copy(string $from, string $to): bool;
    public function move(string $from, string $to): bool;
    public function delete(string|array $paths): bool;
    public function lastModified(string $path): int|false;
    public function size(string $path): int|false;
    public function path(string $path): string;
    public function mimeType(string $path): string|false;

    public function allDirectories(string $path = null): array;
    public function directories(string $path = null, bool $recursive = false): array;
    public function ensureDirectoryExists(string $path, bool $recursive = true): void;
    public function makeDirectory(string $path): bool;
    public function moveDirectory(string $from, string $to): bool;
    public function copyDirectory(string $from, string $to): bool;
    public function deleteDirectory(string $path): bool;
    public function cleanDirectory(string $path): bool;

    public function temporaryUrl(string $path, int $expiration, array $options = []): string|false;
    public function url(string $path): string|false;
    public function download(): ?StreamedResponse;
}