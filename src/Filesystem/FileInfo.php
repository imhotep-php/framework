<?php declare(strict_types=1);

namespace Imhotep\Filesystem;

class FileInfo
{
    public function __construct(
        protected string $path,
        protected string $type,
        protected ?int $size = null,
        protected ?int $lastModified = null,
        //protected ?int $mode = null,
        //protected ?int $links = null
    ) {}

    public function getPathname(): string
    {
        return $this->path;
    }

    public function getBasename(): string
    {
        return basename($this->path);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isDir(): bool
    {
        return $this->type === 'dir';
    }

    public function size(): ?int
    {
        return $this->size;
    }

    public function lastModified(): ?int
    {
        return $this->lastModified;
    }
}