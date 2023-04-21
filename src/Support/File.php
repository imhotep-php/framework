<?php

declare(strict_types=1);

namespace Imhotep\Support;

use Imhotep\Contracts\Filesystem\FileNotFoundException;

class File extends \SplFileInfo
{
    protected ?string $hashName = null;

    public function __construct(string $path, bool $checkPath = false)
    {
        if ($checkPath && ! is_file($path)) {
            throw new FileNotFoundException($path);
        }

        parent::__construct($path);
    }

    public function mimeType(): ?string
    {
        return MimeTypes::guessMimeType($this->getPathname());
    }

    public function extension(): string
    {
        $extension = MimeTypes::getExtension($this->mimeType() ?? '');

        if (is_null($extension)) {
            $extension = $this->getExtension();
        }

        return $extension;
    }

    public function hashName(): string
    {
        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        if ($extension = $this->extension()) {
            return $hash.'.'.$extension;
        }

        return $hash;
    }
}