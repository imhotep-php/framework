<?php declare(strict_types=1);

namespace Imhotep\Contracts\Filesystem;

use Generator;
use Imhotep\Http\UploadedFile;
use Imhotep\Support\File;

interface IFilesystem
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    public function exists(string $path): bool;
    //public function missing(string $path): bool;

    public function list(string $path): array|false;
    public function directories(string $path, bool $recursive = false): array;
    //public function allDirectories(string $path = null): array;
    public function files(string $path, bool $recursive = false): array;
    //public function allFiles(string $path = null): array;

    public function makeDirectory(string $path): bool;
    public function cleanDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;


    /*
    public function get(string $path, array $options = []): string|false;
    public function put(string $path, string $contents, bool|string|array $options = []): int|false;
    public function copy(string $from, string $to): bool;
    public function move(string $from, string $to): bool;
    public function delete(string|array $paths): bool;


    public function lines(string $path, bool $skipEmpty = false): Generator;
    public function putFile(string $path, string|File|UploadedFile $file, bool|string|array $options = []): string|false;
    public function putFileAs(string $path, string|File|UploadedFile $file, string $name, bool|string|array $options = []): string|false;
    public function prepend(string $path, string $content, string $separator = '', bool $lock = false): int|false;
    public function append(string $path, string $content, string $separator = '', bool $lock = false): int|false;
    public function replace(string $path, mixed $content): bool;




    public function size(string $path): int|false;
    public function lastModified(string $path): int|false;
    public function mimeType(string $path): string|false;


    public function ensureDirectoryExists(string $path, bool $recursive = true): bool;

    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool;
    public function copyDirectory(string $from, string $to): bool;
    public function deleteDirectory(string $path): bool;


    public function visibility(string $path): string|false;
    public function setVisibility(string $path, string $visibility): bool;
    */

    //public function temporaryUrl(string $path, int $expiration, array $options = []): string|false;
    //public function url(string $path): string|false;
    //public function download(): mixed;
}