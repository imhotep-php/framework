<?php declare(strict_types=1);

namespace Imhotep\Filesystem\Concerns;

trait FileHashing
{
    public function hash(string $path, string $algo = 'md5'): string|false
    {
        $content = $this->get($path);

        if ($content === false) {
            return false;
        }

        return hash($algo, $content);
    }

    public function hasSameHash(string $firstPath, string $secondPath): bool
    {
        $hash = $this->hash($firstPath);

        return $hash && $hash === $this->hash($secondPath);
    }
}