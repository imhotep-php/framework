<?php declare(strict_types=1);

namespace Imhotep\Session\Handlers;

use FilesystemIterator;
use Imhotep\Filesystem\Filesystem;
use SessionHandlerInterface;
use SplFileInfo;

class FileHandler implements SessionHandlerInterface
{
    public function __construct(
        protected Filesystem $files,
        protected string $path,
        protected int $lifetime
    ){ }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->files->delete($this->getPath($id));

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $countDeleted = 0;

        $files = new FilesystemIterator($this->path, FilesystemIterator::SKIP_DOTS);

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile()) continue;

            if ($file->getMTime() < time() - $this->lifetime) {
                $this->files->delete($file->getRealPath());
                $countDeleted++;
            }
        }

        return $countDeleted;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $path = $this->getPath($id);

        if ($this->files->isFile($path) && $this->files->lastModified($path) >= (time() - $this->lifetime)) {
            return $this->files->sharedGet($path);
        }

        return false;
    }

    public function write(string $id, string $data): bool
    {
        $path = $this->getPath($id);

        $this->files->put($path, $data, true);

        return true;
    }

    protected function getPath(string $id): string
    {
        return rtrim($this->path, '/').'/'.$id;
    }
}