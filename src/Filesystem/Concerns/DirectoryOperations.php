<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Concerns;

trait DirectoryOperations
{
    public function allDirectories(?string $path = null): array
    {
        return $this->directories($path, true);
    }

    public function ensureDirectoryExists(string $path, bool $recursive = true): bool
    {
        if (! $this->isDirectory($path)) {
            return $this->makeDirectory($path, recursive: $recursive);
        }

        return false;
    }

    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite) {
            if ($this->isDirectory($to) && ! $this->deleteDirectory($to)) {
                return false;
            }
        }
        elseif ($this->isDirectory($to)) {
            return false;
        }

        return $this->move($from, $to) === true;
    }

    public function copyDirectory(string $from, string $to): bool
    {
        if (($items = $this->list($from)) === false) {
            return false;
        }

        $this->ensureDirectoryExists($to);

        foreach ($items as $item) {
            $target = $to.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target)) {
                    return false;
                }
            }

            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /*
    public function cleanDirectory(string $path): bool
    {
        $files = $this->list($path);

        foreach ($files as $file) {
            if ($file->isFile()) {
                $this->delete($file->getPathname());
            }
            elseif ($file->isDir()) {
                $this->deleteDirectory($file->getPathname());
            }
        }

        return true;

        //return $this->driver->cleanDirectory($this->resolvePath($path));
    }
    */
}