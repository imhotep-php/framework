<?php

declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Container\Container;
use Imhotep\Support\MimeTypes;
use Imhotep\Support\Str;

class UploadedFile extends \SplFileInfo
{
    public function __construct(
        protected string $path,
        protected string $name,
        protected string $mimeType,
        protected int $error,
        protected bool $test = false,
    )
    {
        parent::__construct($path);
    }

    public function originalName(): string
    {
        return $this->name;
    }

    public function originalExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function originalMimeType(): string
    {
        return $this->mimeType;
    }

    public function path(): string
    {
        return $this->getRealPath();
    }

    public function mimeType(): ?string
    {
        return MimeTypes::guessMimeType($this->path);
    }

    public function name(): string
    {
        return $this->getBasename();
    }

    public function extension(): ?string
    {
        return MimeTypes::getExtension($this->mimeType() ?? '');
    }

    public function isValid(): bool
    {
        $isError = ($this->error !== 0);

        return ($this->test) ? $isError : $isError && is_uploaded_file($this->path);
    }

    public function isValidImage(): bool
    {
        $isValid = $this->isValid();

        if ($this->test) {
            return $isValid;
        }

        if (! exif_imagetype($this->path)) {
            return false;
        }

        if ($info = getimagesize($this->path)) {
            if ($info['width'] > 0 && $info['height'] > 0) {
                return true;
            }
        }

        return false;
    }

    public function store(string $path, string|array $options = null): bool|string
    {
        return $this->storeAs($path, $this->hashName(), $options);
    }

    public function storeAs(string $path, string $name, string $options = null): bool
    {
        if( is_string($options)) {
            $options = ['disk' => $options];
        }

        $disk = $options['disk'] ?? null;
        unset($options['disk']);

        $path = rtrim($path, '/').'/'.$name;

        return Container::getInstance()->make('filesystem')->disk($disk)->putFile($path, $this->path, $options);
    }

    protected ?string $hashName = null;

    protected function hashName(): string
    {
        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        if ($extension = $this->getExtension()) {
            return $hash.'.'.$extension;
        }

        return $hash;
    }
}