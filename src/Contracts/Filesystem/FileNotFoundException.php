<?php declare(strict_types=1);

namespace Imhotep\Contracts\Filesystem;

class FileNotFoundException extends FilesystemException
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = '/'.ltrim($path, '/');

        parent::__construct("File does not exist at path {$this->path}");
    }

    public function path(): string
    {
        return $this->path;
    }
}
